<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Based on your User model's $fillable array
            'student_id' => $this->faker->unique()->numerify('22100####'),
            'employee_id' => null,
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'rfid_tag' => null,
            'firstname' => $this->faker->firstName(),
            'middlename' => $this->faker->lastName(),
            'lastname' => $this->faker->lastName(),
            'program' => $this->faker->randomElement(['BSIT', 'BSCS', 'BSEE']),
            'department' => $this->faker->randomElement(['CCS', 'CEA']),
            'license_number' => null,
            'profile_picture' => null,
            'serial_number' => null,
            'year_section' => $this->faker->randomElement(['4A', '4B', '3A']),
            'address' => $this->faker->address(),
            'contact_number' => $this->faker->phoneNumber(),
            'expiration_date' => null,
        ];
    }
}