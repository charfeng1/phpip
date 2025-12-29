<?php

namespace Database\Seeders;

use App\Enums\ActorRole;
use App\Enums\CategoryCode;
use App\Enums\EventCode;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Generates realistic load test data for UI and performance testing.
 *
 * Run with: php artisan db:seed --class=LoadTestSeeder
 */
class LoadTestSeeder extends Seeder
{
    private array $countries = ['US', 'EP', 'FR', 'DE', 'GB', 'JP', 'CN', 'KR', 'AU', 'CA', 'BR', 'IN'];

    private array $categories = ['PAT', 'TM', 'DSG', 'UM'];

    public function run(): void
    {
        $this->command->info('Creating load test data...');

        // Create clients and agents
        $clients = $this->createActors(20, 'client');
        $agents = $this->createActors(10, 'agent');
        $inventors = $this->createActors(30, 'inventor');

        $this->command->info('Created ' . count($clients) . ' clients, ' . count($agents) . ' agents, ' . count($inventors) . ' inventors');

        // Get existing user logins for assignment (trim CHAR column padding)
        $userLogins = DB::table('actor')
            ->whereNotNull('login')
            ->pluck('login')
            ->map(fn ($login) => trim($login))
            ->toArray();

        if (empty($userLogins)) {
            $userLogins = ['phpipuser'];
        }

        // Create matters with events and tasks
        $matterCount = 150;
        $this->command->info("Creating {$matterCount} matters with events and tasks...");

        $bar = $this->command->getOutput()->createProgressBar($matterCount);

        for ($i = 0; $i < $matterCount; $i++) {
            $matter = $this->createMatterWithRelations(
                $clients,
                $agents,
                $inventors,
                $userLogins
            );
            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();

        // Create additional open tasks for dashboard testing
        $this->createAdditionalTasks($userLogins, 50);

        $this->command->info('Load test data created successfully!');
        $this->printStats();
    }

    private function createActors(int $count, string $type): array
    {
        $actors = [];
        $faker = \Faker\Factory::create();

        for ($i = 0; $i < $count; $i++) {
            $uniqueSuffix = '-' . uniqid();
            $name = $type === 'inventor'
                ? substr($faker->lastName(), 0, 20) . $uniqueSuffix
                : substr($faker->company(), 0, 20) . $uniqueSuffix;
            $actor = Actor::create([
                'name' => substr($name, 0, 30),
                'first_name' => $type === 'inventor' ? substr($faker->firstName(), 0, 30) : null,
                'display_name' => $type === 'inventor' ? null : substr($name, 0, 30),
                'phy_person' => $type === 'inventor',
                'address' => substr($faker->streetAddress() . "\n" . $faker->postcode() . ' ' . $faker->city(), 0, 256),
                'country' => $faker->randomElement($this->countries),
                'email' => substr($faker->email(), 0, 45),
                'phone' => substr($faker->phoneNumber(), 0, 20),
                'notes' => $faker->optional(0.3)->sentence(),
            ]);
            $actors[] = $actor;
        }

        return $actors;
    }

    private function createMatterWithRelations(
        array $clients,
        array $agents,
        array $inventors,
        array $userLogins
    ): Matter {
        $faker = \Faker\Factory::create();

        $category = $faker->randomElement($this->categories);
        $country = $faker->randomElement($this->countries);

        // Create matter
        $matter = Matter::factory()
            ->state([
                'category_code' => $category,
                'country' => $country,
                'responsible' => $faker->randomElement($userLogins),
                'dead' => $faker->boolean(10), // 10% dead
            ])
            ->create();

        // Attach client
        ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $faker->randomElement($clients)->id,
            'role' => ActorRole::CLIENT->value,
            'shared' => false,
        ]);

        // Attach agent (70% chance)
        if ($faker->boolean(70)) {
            ActorPivot::create([
                'matter_id' => $matter->id,
                'actor_id' => $faker->randomElement($agents)->id,
                'role' => ActorRole::AGENT->value,
                'shared' => false,
            ]);
        }

        // Attach inventors for patents and utility models (1-3)
        if (in_array($category, ['PAT', 'UM'])) {
            $inventorCount = $faker->numberBetween(1, 3);
            $selectedInventors = $faker->randomElements($inventors, $inventorCount);
            foreach ($selectedInventors as $inventor) {
                ActorPivot::create([
                    'matter_id' => $matter->id,
                    'actor_id' => $inventor->id,
                    'role' => ActorRole::INVENTOR->value,
                    'shared' => false,
                ]);
            }
        }

        // Create filing event
        $filingDate = $faker->dateTimeBetween('-10 years', '-6 months');
        $filingEvent = Event::create([
            'matter_id' => $matter->id,
            'code' => EventCode::FILING->value,
            'event_date' => $filingDate->format('Y-m-d'),
            'detail' => $faker->optional(0.5)->numerify('##/###,###'),
        ]);

        // Create publication event (80% chance, 6-18 months after filing)
        if ($faker->boolean(80)) {
            $pubDate = (clone $filingDate)->modify('+' . $faker->numberBetween(6, 18) . ' months');
            if ($pubDate < new \DateTime()) {
                Event::create([
                    'matter_id' => $matter->id,
                    'code' => EventCode::PUBLICATION->value,
                    'event_date' => $pubDate->format('Y-m-d'),
                    'detail' => $faker->numerify('??' . $filingDate->format('Y') . '/#######'),
                ]);
            }
        }

        // Create grant event (50% chance, 2-5 years after filing)
        if ($faker->boolean(50) && ! $matter->dead) {
            $grantDate = (clone $filingDate)->modify('+' . $faker->numberBetween(2, 5) . ' years');
            if ($grantDate < new \DateTime()) {
                Event::create([
                    'matter_id' => $matter->id,
                    'code' => EventCode::GRANT->value,
                    'event_date' => $grantDate->format('Y-m-d'),
                    'detail' => $faker->numerify('########'),
                ]);

                // Create renewal tasks for granted patents and utility models
                if (in_array($category, ['PAT', 'UM'])) {
                    $this->createRenewalTasks($matter, $filingEvent, $grantDate, $userLogins);
                }
            }
        }

        // Create some general tasks
        $this->createGeneralTasks($matter, $filingEvent, $userLogins, $faker->numberBetween(0, 3));

        return $matter;
    }

    private function createRenewalTasks(
        Matter $matter,
        Event $filingEvent,
        \DateTime $grantDate,
        array $userLogins
    ): void {
        $faker = \Faker\Factory::create();

        // Calculate which renewal years should have tasks
        $yearsGranted = (int) $grantDate->diff(new \DateTime())->y;
        $currentYear = min($yearsGranted + 3, 20); // Current maintenance year

        // Create 2-3 upcoming renewal tasks
        for ($year = $currentYear; $year <= min($currentYear + 2, 20); $year++) {
            $dueDate = (clone $grantDate)->modify('+' . $year . ' years');

            if ($dueDate > new \DateTime()) {
                Task::create([
                    'trigger_id' => $filingEvent->id,
                    'code' => EventCode::RENEWAL->value,
                    'detail' => ['en' => "Year {$year}", 'fr' => "Année {$year}", 'zh' => "第{$year}年"],
                    'due_date' => $dueDate->format('Y-m-d'),
                    'assigned_to' => $faker->randomElement($userLogins),
                    'done' => false,
                    'cost' => $faker->randomFloat(2, 200, 2000),
                    'fee' => $faker->randomFloat(2, 300, 3000),
                ]);
            }
        }
    }

    private function createGeneralTasks(
        Matter $matter,
        Event $triggerEvent,
        array $userLogins,
        int $count
    ): void {
        $faker = \Faker\Factory::create();

        $taskTypes = [
            ['code' => 'REP', 'en' => 'Response deadline', 'fr' => 'Délai de réponse'],
            ['code' => 'EXA', 'en' => 'Examination request', 'fr' => "Demande d'examen"],
            ['code' => 'OPP', 'en' => 'Opposition deadline', 'fr' => "Délai d'opposition"],
        ];

        for ($i = 0; $i < $count; $i++) {
            $taskType = $faker->randomElement($taskTypes);
            $isDone = $faker->boolean(40);
            $isOverdue = ! $isDone && $faker->boolean(20);

            $dueDate = $isOverdue
                ? $faker->dateTimeBetween('-3 months', '-1 day')
                : $faker->dateTimeBetween('+1 day', '+6 months');

            Task::create([
                'trigger_id' => $triggerEvent->id,
                'code' => $taskType['code'],
                'detail' => ['en' => $taskType['en'], 'fr' => $taskType['fr'], 'zh' => $taskType['en']],
                'due_date' => $dueDate->format('Y-m-d'),
                'assigned_to' => $faker->randomElement($userLogins),
                'done' => $isDone,
                'done_date' => $isDone ? $faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d') : null,
                'cost' => $faker->optional(0.5)->randomFloat(2, 50, 500),
                'fee' => $faker->optional(0.5)->randomFloat(2, 100, 800),
            ]);
        }
    }

    private function createAdditionalTasks(array $userLogins, int $count): void
    {
        $this->command->info("Creating {$count} additional open tasks for dashboard testing...");

        $faker = \Faker\Factory::create();

        // Get random existing events to attach tasks to
        $events = Event::inRandomOrder()->limit($count)->get();

        foreach ($events as $event) {
            $isRenewal = $faker->boolean(60);
            $isOverdue = $faker->boolean(30);

            $dueDate = $isOverdue
                ? $faker->dateTimeBetween('-2 months', '-1 day')
                : $faker->dateTimeBetween('+1 day', '+3 months');

            $year = $faker->numberBetween(3, 15);
            Task::create([
                'trigger_id' => $event->id,
                'code' => $isRenewal ? EventCode::RENEWAL->value : 'REP',
                'detail' => $isRenewal
                    ? ['en' => "Year {$year}", 'fr' => "Année {$year}", 'zh' => "第{$year}年"]
                    : ['en' => 'Response required', 'fr' => 'Réponse requise', 'zh' => '需要回复'],
                'due_date' => $dueDate->format('Y-m-d'),
                'assigned_to' => $faker->randomElement($userLogins),
                'done' => false,
                'cost' => $faker->randomFloat(2, 100, 1500),
                'fee' => $faker->randomFloat(2, 200, 2500),
            ]);
        }
    }

    private function printStats(): void
    {
        $this->command->newLine();
        $this->command->info('Database Statistics:');
        $this->command->table(
            ['Table', 'Count'],
            [
                ['Matters', Matter::count()],
                ['Actors', Actor::count()],
                ['Events', Event::count()],
                ['Tasks (Total)', Task::count()],
                ['Tasks (Open)', Task::whereNull('done_date')->where('done', false)->count()],
                ['Tasks (Renewals)', Task::where('code', EventCode::RENEWAL->value)->count()],
            ]
        );
    }
}
