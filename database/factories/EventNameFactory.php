<?php

namespace Database\Factories;

use App\Models\EventName;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventNameFactory extends Factory
{
    protected $model = EventName::class;

    /**
     * Counter for generating unique test event name codes.
     */
    protected static int $testCodeCounter = 0;

    public function definition(): array
    {
        // Use TE prefix to avoid collision with real event codes
        $counter = static::$testCodeCounter++;
        $code = sprintf('TE%02d', $counter % 100);

        return [
            'code' => $code,
            'name' => json_encode([
                'en' => 'Test Event '.$counter,
                'fr' => 'Événement Test '.$counter,
            ]),
            'category' => null,
            'country' => null,
            'is_task' => false,
            'status_event' => false,
            'default_responsible' => null,
            'use_matter_resp' => false,
            'unique' => false,
            'killer' => false,
            'notes' => null,
        ];
    }

    /**
     * Filing event
     */
    public function filing(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'FIL',
            'name' => json_encode(['en' => 'Filing', 'fr' => 'Dépôt']),
            'status_event' => true,
            'unique' => true,
        ]);
    }

    /**
     * Publication event
     */
    public function publication(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PUB',
            'name' => json_encode(['en' => 'Publication', 'fr' => 'Publication']),
            'status_event' => true,
        ]);
    }

    /**
     * Grant event
     */
    public function grant(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'GRT',
            'name' => json_encode(['en' => 'Grant', 'fr' => 'Délivrance']),
            'status_event' => true,
        ]);
    }

    /**
     * Registration event (for trademarks)
     */
    public function registration(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REG',
            'name' => json_encode(['en' => 'Registration', 'fr' => 'Enregistrement']),
            'status_event' => true,
        ]);
    }

    /**
     * Renewal task
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REN',
            'name' => json_encode(['en' => 'Renewal', 'fr' => 'Annuité']),
            'is_task' => true,
        ]);
    }

    /**
     * Priority event
     */
    public function priority(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PRI',
            'name' => json_encode(['en' => 'Priority', 'fr' => 'Priorité']),
        ]);
    }
}
