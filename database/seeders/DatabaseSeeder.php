<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Admin',
                'username' => 'admin',
                'email' => 'admin@ithelpdesk.test',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Support Manager',
                'username' => 'manager',
                'email' => 'manager@ithelpdesk.test',
                'role' => UserRole::Manager,
            ],
            [
                'name' => 'IT Support Agent',
                'username' => 'agent',
                'email' => 'agent@ithelpdesk.test',
                'role' => UserRole::SupportAgent,
            ],
            [
                'name' => 'Employee User',
                'username' => 'employee',
                'email' => 'employee@ithelpdesk.test',
                'role' => UserRole::Employee,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'password' => 'Password123!',
                    'email_verified_at' => now(),
                ],
            );
        }

        $categories = [
            [
                'name' => 'Hardware',
                'description' => 'Desktops, laptops, monitors, accessories, and physical device issues.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Software',
                'description' => 'Installed applications, licensing, operating systems, and tool configuration.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Network',
                'description' => 'Internet access, VPN, connectivity, and shared infrastructure problems.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Email & Accounts',
                'description' => 'Mailbox access, password issues, and internal account permissions.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Printer & Peripherals',
                'description' => 'Printers, scanners, headsets, docking stations, and office peripherals.',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                    'sort_order' => $category['sort_order'],
                ],
            );
        }
    }
}
