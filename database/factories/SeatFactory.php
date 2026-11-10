<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
class SeatFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $row = fake()->randomElement(range('A', 'E'));
        $number = fake()->numberBetween(1, 30);

        return [
            'event_id' => Event::factory(),
            'label' => $row.$number,
            'row' => $row,
            'number' => $number,
            'price' => fake()->randomFloat(2, 100, 2000),
            'status' => 'available',
        ];
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'booked',
        ]);
    }
}
