<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company().' School';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'address' => $this->faker->address(),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'type' => 'primary',
            'is_active' => true,
        ];
    }
}
