<?php

namespace Database\Seeders;

use App\Enums\ActorRole;
use App\Enums\EventCode;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Seeder;

/**
 * Generates realistic load test data for UI and performance testing.
 *
 * Run with: php artisan db:seed --class=LoadTestSeeder
 */
class LoadTestSeeder extends Seeder
{
    private const MATTER_COUNT = 150;

    private const ADDITIONAL_TASK_COUNT = 50;

    private array $countries = ['US', 'EP', 'FR', 'DE', 'GB', 'JP', 'CN', 'KR', 'AU', 'CA', 'BR', 'IN'];

    private array $categories = ['PAT', 'TM', 'DSG', 'UM'];

    private Faker $faker;

    public function run(): void
    {
        $this->faker = \Faker\Factory::create();
        $this->command->info('Creating load test data...');

        // Create clients and agents
        $clients = $this->createActors(20, 'client');
        $agents = $this->createActors(10, 'agent');
        $inventors = $this->createActors(30, 'inventor');

        $this->command->info('Created '.count($clients).' clients, '.count($agents).' agents, '.count($inventors).' inventors');

        // Get existing user logins for assignment (trim CHAR column padding)
        $userLogins = User::pluck('login')
            ->map(fn ($login) => trim($login))
            ->toArray();

        if (empty($userLogins)) {
            $userLogins = ['phpipuser'];
        }

        // Create matters with events and tasks
        $this->command->info('Creating '.self::MATTER_COUNT.' matters with events and tasks...');

        $bar = $this->command->getOutput()->createProgressBar(self::MATTER_COUNT);

        for ($i = 0; $i < self::MATTER_COUNT; $i++) {
            $this->createMatterWithRelations(
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
        $this->createAdditionalTasks($userLogins, self::ADDITIONAL_TASK_COUNT);

        $this->command->info('Load test data created successfully!');
        $this->printStats();
    }

    private function createActors(int $count, string $type): array
    {
        $actors = [];

        for ($i = 0; $i < $count; $i++) {
            $uniqueSuffix = '-'.uniqid();
            $name = $type === 'inventor'
                ? substr($this->faker->lastName(), 0, 20).$uniqueSuffix
                : substr($this->faker->company(), 0, 20).$uniqueSuffix;
            $actor = Actor::create([
                'name' => substr($name, 0, 30),
                'first_name' => $type === 'inventor' ? substr($this->faker->firstName(), 0, 30) : null,
                'display_name' => $type === 'inventor' ? null : substr($name, 0, 30),
                'phy_person' => $type === 'inventor',
                'address' => substr($this->faker->streetAddress()."\n".$this->faker->postcode().' '.$this->faker->city(), 0, 256),
                'country' => $this->faker->randomElement($this->countries),
                'email' => substr($this->faker->email(), 0, 45),
                'phone' => substr($this->faker->phoneNumber(), 0, 20),
                'notes' => $this->faker->optional(0.3)->sentence(),
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
        $category = $this->faker->randomElement($this->categories);
        $country = $this->faker->randomElement($this->countries);

        // Create matter
        $matter = Matter::factory()
            ->state([
                'category_code' => $category,
                'country' => $country,
                'responsible' => $this->faker->randomElement($userLogins),
                'dead' => $this->faker->boolean(10), // 10% dead
            ])
            ->create();

        // Attach client
        ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $this->faker->randomElement($clients)->id,
            'role' => ActorRole::CLIENT->value,
            'shared' => false,
        ]);

        // Attach agent (70% chance)
        if ($this->faker->boolean(70)) {
            ActorPivot::create([
                'matter_id' => $matter->id,
                'actor_id' => $this->faker->randomElement($agents)->id,
                'role' => ActorRole::AGENT->value,
                'shared' => false,
            ]);
        }

        // Attach inventors for patents and utility models (1-3)
        if (in_array($category, ['PAT', 'UM'])) {
            $inventorCount = $this->faker->numberBetween(1, 3);
            $selectedInventors = $this->faker->randomElements($inventors, $inventorCount);
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
        $filingDate = $this->faker->dateTimeBetween('-10 years', '-6 months');
        $filingEvent = Event::create([
            'matter_id' => $matter->id,
            'code' => EventCode::FILING->value,
            'event_date' => $filingDate->format('Y-m-d'),
            'detail' => $this->faker->optional(0.5)->numerify('##/###,###'),
        ]);

        // Create publication event (80% chance, 6-18 months after filing)
        if ($this->faker->boolean(80)) {
            $pubDate = (clone $filingDate)->modify('+'.$this->faker->numberBetween(6, 18).' months');
            if ($pubDate < new \DateTime) {
                Event::create([
                    'matter_id' => $matter->id,
                    'code' => EventCode::PUBLICATION->value,
                    'event_date' => $pubDate->format('Y-m-d'),
                    'detail' => $this->faker->numerify('??'.$filingDate->format('Y').'/#######'),
                ]);
            }
        }

        // Create grant event (50% chance, 2-5 years after filing)
        if ($this->faker->boolean(50) && ! $matter->dead) {
            $grantDate = (clone $filingDate)->modify('+'.$this->faker->numberBetween(2, 5).' years');
            if ($grantDate < new \DateTime) {
                Event::create([
                    'matter_id' => $matter->id,
                    'code' => EventCode::GRANT->value,
                    'event_date' => $grantDate->format('Y-m-d'),
                    'detail' => $this->faker->numerify('########'),
                ]);

                // Create renewal tasks for granted patents and utility models
                if (in_array($category, ['PAT', 'UM'])) {
                    $this->createRenewalTasks($filingEvent, $grantDate, $userLogins);
                }
            }
        }

        // Create some general tasks
        $this->createGeneralTasks($filingEvent, $userLogins, $this->faker->numberBetween(0, 3));

        return $matter;
    }

    private function createRenewalTasks(
        Event $filingEvent,
        \DateTime $grantDate,
        array $userLogins
    ): void {
        // Calculate which renewal years should have tasks
        $yearsGranted = (int) $grantDate->diff(new \DateTime)->y;
        $currentYear = min($yearsGranted + 3, 20); // Current maintenance year

        // Create 2-3 upcoming renewal tasks
        for ($year = $currentYear; $year <= min($currentYear + 2, 20); $year++) {
            $dueDate = (clone $grantDate)->modify('+'.$year.' years');

            if ($dueDate > new \DateTime) {
                Task::create([
                    'trigger_id' => $filingEvent->id,
                    'code' => EventCode::RENEWAL->value,
                    'detail' => ['en' => "Year {$year}", 'fr' => "Année {$year}", 'zh' => "第{$year}年"],
                    'due_date' => $dueDate->format('Y-m-d'),
                    'assigned_to' => $this->faker->randomElement($userLogins),
                    'done' => false,
                    'cost' => $this->faker->randomFloat(2, 200, 2000),
                    'fee' => $this->faker->randomFloat(2, 300, 3000),
                ]);
            }
        }
    }

    private function createGeneralTasks(
        Event $triggerEvent,
        array $userLogins,
        int $count
    ): void {
        $taskTypes = [
            ['code' => 'REP', 'en' => 'Response deadline', 'fr' => 'Délai de réponse', 'zh' => '回复截止日期'],
            ['code' => 'EXA', 'en' => 'Examination request', 'fr' => "Demande d'examen", 'zh' => '审查请求'],
            ['code' => 'OPP', 'en' => 'Opposition deadline', 'fr' => "Délai d'opposition", 'zh' => '异议截止日期'],
        ];

        for ($i = 0; $i < $count; $i++) {
            $taskType = $this->faker->randomElement($taskTypes);
            $isDone = $this->faker->boolean(40);
            $isOverdue = ! $isDone && $this->faker->boolean(20);

            $dueDate = $isOverdue
                ? $this->faker->dateTimeBetween('-3 months', '-1 day')
                : $this->faker->dateTimeBetween('+1 day', '+6 months');

            Task::create([
                'trigger_id' => $triggerEvent->id,
                'code' => $taskType['code'],
                'detail' => ['en' => $taskType['en'], 'fr' => $taskType['fr'], 'zh' => $taskType['zh']],
                'due_date' => $dueDate->format('Y-m-d'),
                'assigned_to' => $this->faker->randomElement($userLogins),
                'done' => $isDone,
                'done_date' => $isDone ? $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d') : null,
                'cost' => $this->faker->optional(0.5)->randomFloat(2, 50, 500),
                'fee' => $this->faker->optional(0.5)->randomFloat(2, 100, 800),
            ]);
        }
    }

    private function createAdditionalTasks(array $userLogins, int $count): void
    {
        $this->command->info("Creating {$count} additional open tasks for dashboard testing...");

        // Get random existing events to attach tasks to
        $events = Event::inRandomOrder()->limit($count)->get();

        foreach ($events as $event) {
            $isRenewal = $this->faker->boolean(60);
            $isOverdue = $this->faker->boolean(30);

            $dueDate = $isOverdue
                ? $this->faker->dateTimeBetween('-2 months', '-1 day')
                : $this->faker->dateTimeBetween('+1 day', '+3 months');

            $year = $this->faker->numberBetween(3, 15);
            Task::create([
                'trigger_id' => $event->id,
                'code' => $isRenewal ? EventCode::RENEWAL->value : 'REP',
                'detail' => $isRenewal
                    ? ['en' => "Year {$year}", 'fr' => "Année {$year}", 'zh' => "第{$year}年"]
                    : ['en' => 'Response required', 'fr' => 'Réponse requise', 'zh' => '需要回复'],
                'due_date' => $dueDate->format('Y-m-d'),
                'assigned_to' => $this->faker->randomElement($userLogins),
                'done' => false,
                'cost' => $this->faker->randomFloat(2, 100, 1500),
                'fee' => $this->faker->randomFloat(2, 200, 2500),
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
