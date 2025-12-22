<?php

namespace App\Services;

use App\Enums\ActorRole;
use App\Enums\ClassifierType;
use App\Enums\EventCode;
use App\Models\Actor;
use App\Models\Matter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles creation of patent families from OPS (Open Patent Services) data.
 *
 * This service encapsulates the complex logic for:
 * - Fetching family members from EPO OPS API
 * - Creating matter records with proper relationships
 * - Linking actors (applicants, inventors, clients)
 * - Creating events (filing, publication, grant, priorities)
 * - Handling procedural steps (exam reports, renewals, grants)
 */
class PatentFamilyCreationService
{
    protected OPSService $opsService;

    protected ?string $creator = null;

    public function __construct(?OPSService $opsService = null, ?string $creator = null)
    {
        $this->opsService = $opsService ?? new OPSService;
        $this->creator = $creator;
    }

    /**
     * Get the current user login for audit purposes.
     */
    protected function getCreator(): string
    {
        if ($this->creator !== null) {
            return $this->creator;
        }

        return Auth::user()?->login ?? 'system';
    }

    /**
     * Create a patent family from an OPS document number.
     *
     * @param  string  $docnum  The document number to search in OPS
     * @param  string  $caseref  The case reference for the new family
     * @param  string  $categoryCode  The category code (e.g., 'PAT')
     * @param  int  $clientId  The client actor ID
     * @return array Result with 'success' and 'redirect' or 'errors'
     */
    public function createFromOPS(
        string $docnum,
        string $caseref,
        string $categoryCode,
        int $clientId
    ): array {
        // Fetch family members from OPS
        $apps = collect($this->opsService->getFamilyMembers($docnum));

        if ($apps->has('errors') || $apps->has('exception')) {
            return $apps->toArray();
        }

        try {
            return DB::transaction(function () use ($apps, $caseref, $categoryCode, $clientId) {
                $result = $this->processFamilyMembers(
                    $apps,
                    $caseref,
                    $categoryCode,
                    $clientId
                );

                return [
                    'success' => true,
                    'redirect' => "/matter?Ref=$caseref&tab=1",
                    'created' => $result['created'],
                    'skipped' => $result['skipped'],
                ];
            });
        } catch (\Exception $e) {
            // Log error and return error response
            \Log::error('Failed to create patent family from OPS', [
                'docnum' => $docnum,
                'caseref' => $caseref,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'exception' => 'Database error occurred while creating patent family',
                'message' => 'Failed to create patent family: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process all family members and create matters.
     *
     * @param  Collection  $apps  The family members from OPS
     * @param  string  $caseref  The case reference
     * @param  string  $categoryCode  The category code
     * @param  int  $clientId  The client actor ID
     * @return array Statistics about created/skipped matters
     */
    protected function processFamilyMembers(
        Collection $apps,
        string $caseref,
        string $categoryCode,
        int $clientId
    ): array {
        $containerId = null;
        $matterIdMap = [];
        $created = 0;
        $skipped = 0;

        // Check for existing family members
        $existingFamily = Matter::where('caseref', $caseref)->get();
        if ($existingFamily->count()) {
            $container = $existingFamily->where('container_id', null)->first();
            $containerId = $container?->id;
            foreach ($existingFamily as $existing) {
                $matterIdMap[$existing->filing->cleanNumber()] = $existing->id;
            }
        }

        foreach ($apps as $key => $app) {
            // Skip if member already exists
            if (array_key_exists($app['app']['number'], $matterIdMap)) {
                $skipped++;

                continue;
            }

            $matterData = $this->buildMatterData($app, $caseref, $categoryCode);
            $matter = $this->createMatter($matterData);
            $matterIdMap[$app['app']['number']] = $matter->id;

            if ($key === 0) {
                // First member becomes the container
                $containerId = $matter->id;
                $this->processContainerMember($matter, $app, $clientId);
            } else {
                // Subsequent members link to container
                $matter->container_id = $containerId;
                $this->processNonContainerMember($matter, $app, $matterIdMap);
            }

            // Process common elements
            $this->processCommonElements($matter, $app, $matterIdMap, $apps);

            // Save all changes
            $matter->push();
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Build matter data array from OPS app data.
     *
     * @param  array  $app  The OPS application data
     * @param  string  $caseref  The case reference
     * @param  string  $categoryCode  The category code
     * @param  int|null  $existingCount  Pre-calculated count of existing matters with same UID components.
     *                                    When provided, bypasses database query and uses this value directly.
     *                                    When null, executes database query to calculate the count.
     *                                    Used primarily for testing to avoid database dependencies.
     */
    protected function buildMatterData(
        array $app,
        string $caseref,
        string $categoryCode,
        ?int $existingCount = null
    ): array {
        $data = [
            'caseref' => $caseref,
            'country' => $app['app']['country'],
            'category_code' => $categoryCode,
            'creator' => $this->getCreator(),
        ];

        // Set type code based on application kind
        if ($app['app']['kind'] === 'P') {
            $data['type_code'] = 'PRO';
        }

        // Set origin for PCT national phase entries
        if ($app['pct'] !== null) {
            $data['origin'] = 'WO';
        }

        // Handle divisionals and continuations
        if ($app['div'] !== null) {
            $data['type_code'] = 'DIV';
        }
        if ($app['cnt'] !== null) {
            $data['type_code'] = 'CNT';
        }

        // Calculate index for unique UID
        if ($existingCount !== null) {
            $data['idx'] = $existingCount > 0 ? $existingCount + 1 : null;
        } else {
            $data['idx'] = $this->calculateMatterIndex($data);
        }

        return $data;
    }

    /**
     * Calculate the index for matters with same UID components.
     */
    protected function calculateMatterIndex(array $data): ?int
    {
        $count = Matter::where([
            ['caseref', $data['caseref']],
            ['country', $data['country']],
            ['category_code', $data['category_code']],
            ['origin', $data['origin'] ?? null],
            ['type_code', $data['type_code'] ?? null],
        ])->count();

        return $count > 0 ? $count + 1 : null;
    }

    /**
     * Create a new matter record.
     */
    protected function createMatter(array $data): Matter
    {
        return Matter::create($data);
    }

    /**
     * Process the container (first) member of the family.
     */
    protected function processContainerMember(Matter $matter, array $app, int $clientId): void
    {
        // Create priority filings for non-self priorities
        if (!array_key_exists('pri', $app)) {
            return;
        }

        foreach ($app['pri'] as $pri) {
            if ($pri['number'] !== $app['app']['number']) {
                $matter->events()->create([
                    'code' => EventCode::PRIORITY->value,
                    'detail' => $pri['country'].$pri['number'],
                    'event_date' => $pri['date'],
                ]);
            }
        }

        // Add title
        if (array_key_exists('title', $app)) {
            $matter->classifiersNative()->create([
                'type_code' => ClassifierType::TITLE->value,
                'value' => $app['title'],
            ]);
        }

        // Link client
        $matter->actorPivot()->create([
            'actor_id' => $clientId,
            'role' => ActorRole::CLIENT->value,
            'shared' => 1,
        ]);

        // Process applicants
        if (array_key_exists('applicants', $app)) {
            $this->processApplicants($matter, $app['applicants'], $clientId);
        }

        // Process inventors
        if (array_key_exists('inventors', $app)) {
            $this->processInventors($matter, $app['inventors']);
        }
    }

    /**
     * Process applicants for the container matter.
     */
    protected function processApplicants(Matter $matter, array $applicants, int $clientId): void
    {
        // Return early if applicants array is empty to prevent null pointer exception
        if (empty($applicants)) {
            return;
        }

        $client = Actor::find($clientId);

        // Check if first applicant matches client
        if ($client && strtolower($applicants[0]) === strtolower($client->name)) {
            $matter->actorPivot()->create([
                'actor_id' => $clientId,
                'role' => ActorRole::APPLICANT->value,
                'shared' => 1,
            ]);
        }

        foreach ($applicants as $applicantName) {
            $applicantName = $this->cleanName($applicantName);
            $actor = $this->findOrCreateActor($applicantName, ActorRole::APPLICANT, false, $matter->id);

            $matter->actorPivot()->firstOrCreate([
                'actor_id' => $actor->id,
                'role' => ActorRole::APPLICANT->value,
                'shared' => 1,
            ]);
        }

        $matter->notes = 'Applicants: '.collect($applicants)->implode('; ');
    }

    /**
     * Process inventors for the container matter.
     */
    protected function processInventors(Matter $matter, array $inventors): void
    {
        foreach ($inventors as $inventorName) {
            $inventorName = $this->cleanName($inventorName);
            $actor = $this->findOrCreateActor($inventorName, ActorRole::INVENTOR, true, $matter->id);

            $matter->actorPivot()->firstOrCreate([
                'actor_id' => $actor->id,
                'role' => ActorRole::INVENTOR->value,
                'shared' => 1,
            ]);
        }

        $inventorNote = 'Inventors: '.collect($inventors)->implode(' - ');
        $matter->notes = $matter->notes ? $matter->notes."\n".$inventorNote : $inventorNote;
    }

    /**
     * Clean actor name (remove trailing comma).
     */
    protected function cleanName(string $name): string
    {
        return rtrim($name, ',');
    }

    /**
     * Find an existing actor by phonetic match or create a new one.
     */
    protected function findOrCreateActor(
        string $name,
        ActorRole $role,
        bool $isPhysicalPerson,
        int $matterId
    ): Actor {
        // Search for phonetically equivalent actor
        $actor = Actor::whereRaw('name SOUNDS LIKE ?', [$name])->first();

        if ($actor) {
            return $actor;
        }

        return Actor::create([
            'name' => $name,
            'default_role' => $role->value,
            'phy_person' => $isPhysicalPerson ? 1 : 0,
            'notes' => "Inserted by OPS family create tool for matter ID $matterId",
        ]);
    }

    /**
     * Process non-container family members.
     */
    protected function processNonContainerMember(Matter $matter, array $app, array $matterIdMap): void
    {
        if (!array_key_exists('pri', $app)) {
            return;
        }

        foreach ($app['pri'] as $pri) {
            // Skip self-priority
            if ($pri['number'] === $app['app']['number']) {
                continue;
            }

            if (array_key_exists($pri['number'], $matterIdMap)) {
                // Priority application is in the family
                $matter->events()->create([
                    'code' => EventCode::PRIORITY->value,
                    'alt_matter_id' => $matterIdMap[$pri['number']],
                ]);
            } else {
                $matter->events()->create([
                    'code' => EventCode::PRIORITY->value,
                    'detail' => $pri['country'].$pri['number'],
                    'event_date' => $pri['date'],
                ]);
            }
        }
    }

    /**
     * Process elements common to all family members.
     */
    protected function processCommonElements(
        Matter $matter,
        array $app,
        array $matterIdMap,
        Collection $apps
    ): void {
        $parentNum = $app['div'] ?? $app['cnt'];

        // Handle PCT origin
        if ($app['pct'] !== null) {
            $matter->parent_id = $matterIdMap[$app['pct']] ?? null;
            $matter->events()->create([
                'code' => EventCode::PCT_FILING->value,
                'alt_matter_id' => $matter->parent_id,
            ]);
        }

        // Handle divisional/continuation parent
        if ($parentNum) {
            $matter->events()->create([
                'code' => EventCode::ENTRY->value,
                'event_date' => $app['app']['date'],
                'detail' => 'Descendant filing date',
            ]);

            $matter->parent_id = $matterIdMap[$parentNum] ?? null;
        }

        // Filing event
        $matter->events()->create([
            'code' => EventCode::FILING->value,
            'event_date' => $app['app']['date'],
            'detail' => $app['app']['number'],
        ]);

        // Publication event
        if (array_key_exists('pub', $app)) {
            $matter->events()->create([
                'code' => EventCode::PUBLICATION->value,
                'event_date' => $app['pub']['date'],
                'detail' => $app['pub']['number'],
            ]);
        }

        // Grant event
        if (array_key_exists('grt', $app)) {
            $matter->events()->create([
                'code' => EventCode::GRANT->value,
                'event_date' => $app['grt']['date'],
                'detail' => $app['grt']['number'],
            ]);
        }

        // Procedural steps
        if (array_key_exists('procedure', $app)) {
            $this->processProceduralSteps($matter, $app['procedure']);
        }
    }

    /**
     * Process procedural steps from OPS data.
     */
    protected function processProceduralSteps(Matter $matter, array $steps): void
    {
        foreach ($steps as $step) {
            switch ($step['code']) {
                case 'EXRE':
                    $this->processExamReport($matter, $step);
                    break;
                case 'RFEE':
                    $this->processRenewalFee($matter, $step);
                    break;
                case 'IGRA':
                    $this->processIntentionToGrant($matter, $step);
                    break;
                case 'EXAM52':
                    $this->processFilingRequest($matter, $step);
                    break;
            }
        }
    }

    /**
     * Process examination report step.
     */
    protected function processExamReport(Matter $matter, array $step): void
    {
        if (! array_key_exists('dispatched', $step)) {
            return;
        }

        $exa = $matter->events()->create([
            'code' => EventCode::EXAMINATION->value,
            'event_date' => $step['dispatched'],
        ]);

        if (array_key_exists('replied', $step) && $exa->event_date < now()->subMonths(4)) {
            $exa->tasks()->create([
                'code' => EventCode::REPLY->value,
                'due_date' => $exa->event_date->addMonths(4),
                'done_date' => $step['replied'],
                'done' => 1,
                'detail' => 'Exam Report',
            ]);
        }
    }

    /**
     * Process renewal fee step.
     */
    protected function processRenewalFee(Matter $matter, array $step): void
    {
        if (! array_key_exists('ren_year', $step) || ! array_key_exists('ren_paid', $step)) {
            return;
        }

        $filing = $matter->filing;
        if (! $filing) {
            return;
        }

        $filing->tasks()->updateOrCreate(
            ['code' => EventCode::RENEWAL->value, 'detail' => $step['ren_year']],
            [
                'due_date' => $filing->event_date->addYears($step['ren_year'] - 1)->lastOfMonth(),
                'done_date' => $step['ren_paid'],
                'done' => 1,
            ]
        );
    }

    /**
     * Process intention to grant step.
     */
    protected function processIntentionToGrant(Matter $matter, array $step): void
    {
        $grt = null;

        if (array_key_exists('dispatched', $step)) {
            $grt = $matter->events()->create([
                'code' => EventCode::ALLOWANCE->value,
                'event_date' => $step['dispatched'],
            ]);
        }

        if ($grt && array_key_exists('grt_paid', $step) && $grt->event_date < now()->subMonths(4)) {
            $grt->tasks()->create([
                'code' => EventCode::PAYMENT->value,
                'due_date' => $grt->event_date->addMonths(4),
                'done_date' => $step['grt_paid'],
                'done' => 1,
                'detail' => 'Grant Fee',
            ]);
        }
    }

    /**
     * Process filing request step (for divisional actual filing date).
     */
    protected function processFilingRequest(Matter $matter, array $step): void
    {
        if ($matter->type_code !== 'DIV') {
            return;
        }

        if (! array_key_exists('request', $step)) {
            return;
        }

        $entryEvent = $matter->events->where('code', EventCode::ENTRY->value)->first();
        if ($entryEvent) {
            $entryEvent->event_date = $step['request'];
            $entryEvent->save();
        }
    }
}
