<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $middleName = fake()->boolean(50) ? fake()->firstName() : null;
        $surname = fake()->lastName();
        $extension = fake()->boolean(20) ? fake()->randomElement(['Jr.', 'Sr.', 'II', 'III']) : null;

        return [
            'full_name' => User::formatFullName($firstName, $surname, $middleName, $extension),
            'surname' => $surname,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'extension' => $extension,
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'admin',
            'subdivision_id' => null,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this;
    }
}
