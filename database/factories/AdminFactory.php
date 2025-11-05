<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash; // Use Hash facade
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => $this->faker->userName(),
            'firstname' => $this->faker->firstName(),
            'middlename' => $this->faker->lastName(),
            'lastname' => $this->faker->lastName(),
            'password' => Hash::make('password'),
            // Grant some basic permissions as JSON array
            'permissions' => json_encode([
                'dashboard',
                'analytics_dashboard',
                'live_attendance',
                'manage_guest',
                'manage_guest_tag',
                'parking_slots',
                'manage_map',
                'add_parking_area',
                'edit_parking_area',
                'violation_tracking',
                'create_report',
                'pending_reports',
                'approved_reports',
                'for_endorsement',
                'submit_approved_report',
                'users',
                'users_table',
                'vehicles_table',
                'admins_table',
                'guests_table',
                'create_user',
                'edit_user',
                'create_admin',
                'edit_admin',
                'sticker_generator',
                'generate_sticker',
                'manage_sticker',
                'activity_log',
                'system_logs',
                'entry_exit_logs',
                'unknown_tags',
            ]),
        ];
    }
}