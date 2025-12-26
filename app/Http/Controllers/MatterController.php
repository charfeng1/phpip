<?php

namespace App\Http\Controllers;

use App\Enums\CategoryCode;
use App\Enums\EventCode;
use App\Http\Requests\MatterExportRequest;
use App\Http\Requests\MergeFileRequest;
use App\Http\Requests\StoreMatterRequest;
use App\Http\Requests\UpdateMatterRequest;
use App\Models\Category;
use App\Models\Country;
use App\Models\Matter;
use App\Models\MatterType;
use App\Models\User;
use App\Repositories\MatterRepository;
use App\Services\DocumentMergeService;
use App\Services\MatterExportService;
use App\Services\MatterOperationService;
use App\Services\OPSService;
use App\Services\PatentFamilyCreationService;
use App\Traits\HandlesAuditFields;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * Controller for managing intellectual property matters (patents, trademarks, etc.).
 *
 * Handles CRUD operations, family creation via EPO OPS API, document merging,
 * and exporting of matter data.
 */
class MatterController extends Controller
{
    use HandlesAuditFields;

    protected DocumentMergeService $documentMergeService;

    protected MatterExportService $matterExportService;

    protected MatterRepository $matterRepository;

    protected OPSService $opsService;

    protected PatentFamilyCreationService $patentFamilyService;

    protected MatterOperationService $matterOperationService;

    /**
     * Initialize the controller with required services.
     *
     * @param DocumentMergeService $documentMergeService Service for merging matter data into documents.
     * @param MatterExportService $matterExportService Service for exporting matters to CSV.
     * @param MatterRepository $matterRepository Repository for matter queries.
     * @param OPSService $opsService Service for interacting with EPO OPS API.
     * @param PatentFamilyCreationService $patentFamilyService Service for creating patent families from OPS.
     * @param MatterOperationService $matterOperationService Service for handling special matter creation operations.
     */
    public function __construct(
        DocumentMergeService $documentMergeService,
        MatterExportService $matterExportService,
        MatterRepository $matterRepository,
        OPSService $opsService,
        PatentFamilyCreationService $patentFamilyService,
        MatterOperationService $matterOperationService
    ) {
        $this->documentMergeService = $documentMergeService;
        $this->matterExportService = $matterExportService;
        $this->matterRepository = $matterRepository;
        $this->opsService = $opsService;
        $this->patentFamilyService = $patentFamilyService;
        $this->matterOperationService = $matterOperationService;
    }

    /**
     * Display a paginated list of matters with optional filtering and sorting.
     *
     * @param Request $request The HTTP request containing filter, sort, and pagination parameters.
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse The view or JSON response with filtered matters.
     */
    public function index(Request $request)
    {
        $filters = $request->except(
            [
                'display_with',
                'page',
                'filter',
                'value',
                'sortkey',
                'sortdir',
                'tab',
                'include_dead',
            ]);

        $query = $this->matterRepository->filter(
            $request->input('sortkey', 'matter.id'),
            $request->input('sortdir', 'desc'),
            $filters,
            $request->display_with ?? false,
            filter_var($request->include_dead, FILTER_VALIDATE_BOOLEAN)
        );

        if ($request->wantsJson()) {
            $matters = $query->with('events.info')->get();

            return response()->json($matters);
        }

        $matters = $query->paginate(config('pagination.matters'));
        $matters->withQueryString();  // Keep URL parameters in the paginator links

        return view('matter.index', compact('matters'));
    }

    /**
     * Display detailed information for a specific matter.
     *
     * Loads related data including tasks, renewals, events, family members,
     * and external priority relationships.
     *
     * @param Matter $matter The matter to display.
     * @return \Illuminate\Http\Response The view with matter details.
     */
    public function show(Matter $matter)
    {
        $this->authorize('view', $matter);
        $matter->load(['tasksPending.info', 'renewalsPending', 'events.info', 'titles', 'actors', 'classifiers', 'family', 'priorityTo', 'linkedBy']);

        // Get all family members (same caseref)
        $family = $matter->family;
        $familyIds = $family->pluck('id')->push($matter->id)->unique();

        // Get all external matters claiming priority to any family member (by PRI event)
        $externalPriorityMatters = $this->matterRepository->getExternalPriorityMatters($familyIds);

        // For retrolink: if this matter is an external matter claiming priority to a local matter, find the local matter
        $retrolink = null;
        $priEvent = $matter->events->where('code', EventCode::PRIORITY->value)->first();
        if ($priEvent && $priEvent->alt_matter_id) {
            $retrolink = $this->matterRepository->find($priEvent->alt_matter_id);
        }

        return view('matter.show', compact('matter', 'family', 'externalPriorityMatters', 'retrolink'));
    }

    /**
     * Return a JSON array with info of a matter. For use with API REST.
     *
     * @param  int  $id
     * @return Json
     **/
    public function info($id)
    {
        return Matter::with(
            ['tasksPending.info', 'renewalsPending', 'events.info', 'titles', 'actors', 'classifiers']
        )->find($id);
    }

    /**
     * Show the form for creating a new matter.
     *
     * Supports multiple creation modes: new, clone, descendant, and OPS import.
     * Prepares the parent matter data when cloning or creating descendants.
     *
     * @param Request $request The HTTP request containing operation type and parent matter ID.
     * @return \Illuminate\Http\Response The view for creating a new matter.
     */
    public function create(Request $request)
    {
        Gate::authorize('readwrite');
        $operation = $request->input('operation', 'new'); // new, clone, descendant, ops
        $category = [];
        $category_code = $request->input('category', CategoryCode::PATENT->value);
        if ($operation != 'new' && $operation != 'ops') {
            $parent_matter = Matter::with(
                'container',
                'countryInfo',
                'originInfo',
                'category',
                'type'
            )->find($request->matter_id);
            if ($operation == 'clone') {
                // Generate the next available caseref based on the prefix
                $parent_matter->caseref = Matter::where(
                    'caseref',
                    'like',
                    $parent_matter->category->ref_prefix.'%'
                )->max('caseref');
                $parent_matter->caseref++;
            }
        } else {
            $parent_matter = new Matter; // Create empty matter object to avoid undefined errors in view
            $ref_prefix = \App\Models\Category::find($category_code)['ref_prefix'];
            $category = [
                'code' => $category_code,
                'next_caseref' => Matter::where('caseref', 'like', $ref_prefix.'%')
                    ->max('caseref'),
                'name' => \App\Models\Category::find($category_code)['category'],
            ];
            $category['next_caseref']++;
        }

        $categoriesList = Category::all()
            ->sortBy(fn ($item) => strtolower((string) $item->category), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $countries = Country::all()
            ->sortBy(fn ($item) => strtolower((string) $item->name), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $matterTypes = MatterType::all()
            ->sortBy(fn ($item) => strtolower((string) $item->type), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $responsibleUsers = User::orderBy('name')->get(['login', 'name']);
        $defaultResponsible = $parent_matter->responsible ?? Auth::user()->login;

        return view(
            'matter.create',
            compact(
                'parent_matter',
                'operation',
                'category',
                'categoriesList',
                'countries',
                'matterTypes',
                'responsibleUsers',
                'defaultResponsible'
            )
        );
    }

    /**
     * Store a new matter in the database.
     *
     * Handles matter creation based on operation type (new, clone, descendant).
     * Manages unique identifier generation, priority claims copying, and actor relationships.
     *
     * @param StoreMatterRequest $request The HTTP request containing matter data.
     * @return \Illuminate\Http\JsonResponse JSON response with redirect URL to the new matter.
     */
    public function store(StoreMatterRequest $request)
    {
        // Unique UID handling
        $matters = Matter::where(
            [
                ['caseref', $request->caseref],
                ['country', $request->country],
                ['category_code', $request->category_code],
                ['origin', $request->origin],
                ['type_code', $request->type_code],
            ]
        );

        $this->mergeCreator($request);

        $idx = $matters->count();

        if ($idx > 0) {
            $request->merge(['idx' => $idx + 1]);
        }

        $new_matter = Matter::create($this->getFilteredData($request, ['operation', 'parent_id', 'priority']));

        // Handle special operations (descendant, clone, new) using MatterOperationService
        $operation = $request->operation ?? 'new';
        $this->matterOperationService->handleOperation($new_matter, $operation, [
            'parent_id' => $request->parent_id,
            'priority' => $request->priority,
        ]);

        return response()->json(['redirect' => route('matter.show', [$new_matter])]);
    }

    /**
     * Create multiple national phase entries from a PCT or EP application.
     *
     * Generates multiple matter entries for different countries, copying priority
     * claims, filing/publication/grant events from the parent matter.
     *
     * @param Request $request The HTTP request containing parent matter and country list.
     * @return \Illuminate\Http\JsonResponse JSON response with redirect URL to the created matters.
     */
    public function storeN(Request $request)
    {
        Gate::authorize('readwrite');
        $this->validate(
            $request,
            ['ncountry' => 'required|array']
        );

        $parent_id = $request->parent_id;
        $parent_matter = Matter::with('priority', 'filing', 'publication', 'grant', 'classifiersNative')
            ->find($parent_id);

        foreach ($request->ncountry as $country) {
            $request->merge(
                [
                    'country' => $country,
                    'creator' => Auth::user()->login,
                ]
            );

            $new_matter = Matter::create($request->except(['_token', '_method', 'ncountry', 'parent_id']));

            // Copy shared events from original matter
            $new_matter->priority()->createMany($parent_matter->priority->toArray());
            // $new_matter->parentFiling()->createMany($parent_matter->parentFiling->toArray());
            $new_matter->filing()->save($parent_matter->filing->replicate());
            if ($parent_matter->publication()->exists()) {
                $new_matter->publication()->save($parent_matter->publication->replicate());
            }
            if ($parent_matter->grant()->exists()) {
                $new_matter->grant()->save($parent_matter->grant->replicate());
            }

            // Insert "entered" event tracing the actual date of the step
            $new_matter->events()->create(['code' => EventCode::ENTRY->value, 'event_date' => now()]);
            // Insert "Parent filed" event tracing the filing number of the parent PCT or EP
            $new_matter->events()->create(['code' => EventCode::PCT_FILING->value, 'alt_matter_id' => $request->parent_id]);

            $new_matter->parent_id = $parent_id;
            $new_matter->container_id = $parent_matter->container_id ?? $parent_id;
            $new_matter->save();
        }

        return response()->json(['redirect' => "/matter?Ref=$request->caseref&origin=$parent_matter->country"]);
    }

    /**
     * Create a patent family from OPS (Open Patent Services) data.
     *
     * Fetches family members from the EPO OPS API and creates matter records
     * with all associated events, actors, and relationships. Handles priorities,
     * divisionals, continuations, and PCT national phases.
     *
     * @param Request $request Request containing docnum, caseref, category_code, and client_id.
     * @return \Illuminate\Http\JsonResponse JSON response with redirect URL or errors.
     */
    public function storeFamily(Request $request)
    {
        Gate::authorize('readwrite');

        $this->validate($request, [
            'docnum' => 'required',
            'caseref' => 'required',
            'category_code' => 'required',
            'client_id' => 'required',
        ]);

        $result = $this->patentFamilyService->createFromOPS(
            $request->docnum,
            $request->caseref,
            $request->category_code,
            $request->client_id
        );

        // Handle OPS API errors
        if (isset($result['errors']) || isset($result['exception'])) {
            return response()->json($result);
        }

        return response()->json(['redirect' => $result['redirect']]);
    }

    /**
     * Show the form for editing a matter.
     *
     * Determines whether country and category fields can be edited based on
     * whether the matter has country/category-specific tasks.
     *
     * @param Matter $matter The matter to edit.
     * @return \Illuminate\Http\Response The view for editing the matter.
     */
    public function edit(Matter $matter)
    {
        Gate::authorize('readwrite');
        $matter->load(
            'container',
            'parent',
            'countryInfo:iso,name',
            'originInfo:iso,name',
            'category',
            'type',
            'filing'
        );
        $country_edit = $matter->tasks()->whereHas(
            'rule',
            function (Builder $q) {
                $q->whereNotNull('for_country');
            }
        )->count() == 0;
        $cat_edit = $matter->tasks()->whereHas(
            'rule',
            function (Builder $q) {
                $q->whereNotNull('for_category');
            }
        )->count() == 0;

        return view('matter.edit', compact(['matter', 'cat_edit', 'country_edit']));
    }

    /**
     * Update a matter in the database.
     *
     * @param UpdateMatterRequest $request The HTTP request containing updated matter data.
     * @param Matter $matter The matter to update.
     * @return Matter The updated matter model.
     */
    public function update(UpdateMatterRequest $request, Matter $matter)
    {
        $this->mergeUpdater($request);
        $matter->update($this->getFilteredData($request));

        return $matter;
    }

    /**
     * Remove a matter from the database.
     *
     * @param Matter $matter The matter to delete.
     * @return Matter The deleted matter model.
     */
    public function destroy(Matter $matter)
    {
        Gate::authorize('readwrite');
        $matter->delete();

        return $matter;
    }

    /**
     * Exports Matters list.
     *
     * This method exports a list of matters based on the provided filters and returns
     * a streamed response for downloading the file in CSV format.
     *
     * @param  MatterExportRequest  $request  The request object containing the filters for exporting matters.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse The streamed response for the CSV file download.
     */
    public function export(MatterExportRequest $request)
    {
        // Extract filters from the request, excluding certain parameters.
        $filters = $request->except(
            [
                'display_with',
                'page',
                'filter',
                'value',
                'sortkey',
                'sortdir',
                'tab',
                'include_dead',
            ]
        );

        // Retrieve the filtered matters and convert them to an array.
        $export = $this->matterRepository->filter(
            $request->input('sortkey', 'caseref'),
            $request->input('sortdir', 'asc'),
            $filters,
            $request->input('display_with', false),
            filter_var($request->input('include_dead', false), FILTER_VALIDATE_BOOLEAN)
        )->get()->toArray();

        // Export the matters array to a CSV file and return the streamed response.
        return $this->matterExportService->export($export);
    }

    /**
     * Generate merged document on the fly from uploaded template
     */
    public function mergeFile(Matter $matter, MergeFileRequest $request)
    {
        $file = $request->file('file');
        $template = $this->documentMergeService
            ->setMatter($matter)
            ->merge($file->path());

        return response()->streamDownload(function () use ($template) {
            $template->saveAs('php://output');
        }, 'merged-'.$file->getClientOriginalName(), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Transfer-Encoding' => 'binary',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Get family members from OPS for a given document number
     *
     * @return array
     */
    public function getOPSfamily(string $docnum)
    {
        return $this->opsService->getFamilyMembers($docnum);
    }

    /**
     * Display all events for a matter.
     *
     * @param Matter $matter The matter whose events to display.
     * @return \Illuminate\Http\Response The view with events list.
     */
    public function events(Matter $matter)
    {
        $events = $matter->events->load('info');

        return view('matter.events', compact('events', 'matter'));
    }

    /**
     * Display all tasks (excluding renewals) for a matter.
     *
     * @param Matter $matter The matter whose tasks to display.
     * @return \Illuminate\Http\Response The view with tasks list.
     */
    public function tasks(Matter $matter)
    {
        // All events and their tasks, excepting renewals
        $events = $matter->events()->with(['tasks' => function (HasMany $query) {
            $query->where('code', '!=', EventCode::RENEWAL->value);
        }, 'info:code,name', 'tasks.info:code,name'])->get();
        $is_renewals = 0;

        return view('matter.tasks', compact('events', 'matter', 'is_renewals'));
    }

    /**
     * Display renewal tasks for a matter.
     *
     * @param Matter $matter The matter whose renewals to display.
     * @return \Illuminate\Http\Response The view with renewals list.
     */
    public function renewals(Matter $matter)
    {
        // The renewal trigger event and its renewals
        $events = $matter->events()->whereHas('tasks', function (Builder $query) {
            $query->where('code', EventCode::RENEWAL->value);
        })->with(['tasks' => function (HasMany $query) {
            $query->where('code', EventCode::RENEWAL->value);
        }, 'info:code,name', 'tasks.info:code,name'])->get();
        $is_renewals = 1;

        return view('matter.tasks', compact('events', 'matter', 'is_renewals'));
    }

    /**
     * Display actors of a specific role for a matter.
     *
     * @param Matter $matter The matter whose actors to display.
     * @param string $role The role code to filter actors.
     * @return \Illuminate\Http\Response The view with actors list.
     */
    public function actors(Matter $matter, $role)
    {
        $role_group = $matter->actors->where('role_code', $role);

        return view('matter.roleActors', compact('role_group', 'matter'));
    }

    /**
     * Display classifiers (titles, classes, etc.) for a matter.
     *
     * @param Matter $matter The matter whose classifiers to display.
     * @return \Illuminate\Http\Response The view with classifiers list.
     */
    public function classifiers(Matter $matter)
    {
        $matter->load(['classifiers']);

        return view('matter.classifiers', compact('matter'));
    }

    /**
     * Display the description/summary for a matter in a specific language.
     *
     * @param Matter $matter The matter whose description to display.
     * @param string $lang The language code for the description.
     * @return \Illuminate\Http\Response The view with the description.
     */
    public function description(Matter $matter, $lang)
    {
        $description = $matter->getDescription($lang);

        return view('matter.summary', compact('description'));
    }
}
