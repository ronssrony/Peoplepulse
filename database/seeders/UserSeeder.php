<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if users already exist
        if (User::count() > 0) {
            $this->command->info('Users already seeded, skipping...');
            return;
        }

        // Get departments and sub-departments
        $engineering = Department::where('name', 'Engineering')->first();
        $hr = Department::where('name', 'Human Resources')->first();
        $finance = Department::where('name', 'Finance')->first();

        if (!$engineering || !$hr || !$finance) {
            $this->command->error('Departments not found! Please run DepartmentSeeder first.');
            return;
        }

        $backend = SubDepartment::where('name', 'Backend')->first();
        $frontend = SubDepartment::where('name', 'Frontend')->first();
        $recruitment = SubDepartment::where('name', 'Recruitment')->first();
        $qa = SubDepartment::where('name', 'QA')->first();
        $devops = SubDepartment::where('name', 'DevOps')->first();

        // Admin User
        $admin = User::create([
            'employee_id' => 'EMP001',
            'name' => 'Admin User',
            'email' => 'admin@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => null,
            'sub_department_id' => null,
            'designation' => 'System Administrator',
            'role' => 'admin',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);

        // Manager - Engineering (manages Backend and Frontend)
        $managerEngineering = User::create([
            'employee_id' => 'EMP002',
            'name' => 'John Manager',
            'email' => 'john.manager@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'sub_department_id' => null,
            'designation' => 'Engineering Manager',
            'role' => 'manager',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);
        // Assign sub-departments to this manager
        $managerEngineering->managedSubDepartments()->attach([$backend->id, $frontend->id, $qa->id]);

        // Manager - HR (manages Recruitment and Employee Relations)
        $managerHR = User::create([
            'employee_id' => 'EMP003',
            'name' => 'Sarah HR Manager',
            'email' => 'sarah.hr@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $hr->id,
            'sub_department_id' => null,
            'designation' => 'HR Manager',
            'role' => 'manager',
            'weekend_days' => ['friday', 'saturday'],
            'email_verified_at' => now(),
        ]);
        $managerHR->managedSubDepartments()->attach([$recruitment->id]);

        // Regular Users - Engineering Backend
        User::create([
            'employee_id' => 'EMP004',
            'name' => 'Alice Developer',
            'email' => 'alice@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'sub_department_id' => $backend->id,
            'designation' => 'Senior Developer',
            'role' => 'user',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);

        User::create([
            'employee_id' => 'EMP005',
            'name' => 'Bob Developer',
            'email' => 'bob@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'sub_department_id' => $backend->id,
            'designation' => 'Junior Developer',
            'role' => 'user',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);

        // Regular Users - Engineering Frontend
        User::create([
            'employee_id' => 'EMP006',
            'name' => 'Carol Frontend',
            'email' => 'carol@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'sub_department_id' => $frontend->id,
            'designation' => 'Frontend Developer',
            'role' => 'user',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);

        // Regular Users - Engineering QA
        User::create([
            'employee_id' => 'EMP009',
            'name' => 'Frank QA',
            'email' => 'frank@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'sub_department_id' => $qa->id,
            'designation' => 'QA Engineer',
            'role' => 'user',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);

        // Regular Users - Engineering DevOps
        User::create([
            'employee_id' => 'EMP010',
            'name' => 'George DevOps',
            'email' => 'george@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $engineering->id,
            'sub_department_id' => $devops->id,
            'designation' => 'DevOps Engineer',
            'role' => 'user',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);

        // Regular Users - HR
        User::create([
            'employee_id' => 'EMP007',
            'name' => 'Diana HR',
            'email' => 'diana@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $hr->id,
            'sub_department_id' => $recruitment->id,
            'designation' => 'HR Executive',
            'role' => 'user',
            'weekend_days' => ['friday', 'saturday'],
            'email_verified_at' => now(),
        ]);

        // Regular Users - Finance
        User::create([
            'employee_id' => 'EMP008',
            'name' => 'Eve Finance',
            'email' => 'eve@peoplepulse.com',
            'password' => Hash::make('password'),
            'department_id' => $finance->id,
            'sub_department_id' => null,
            'designation' => 'Accountant',
            'role' => 'user',
            'weekend_days' => ['saturday', 'sunday'],
            'email_verified_at' => now(),
        ]);

        $this->command->info('Users seeded successfully!');
        $this->command->info('Admin login: admin@peoplepulse.com / password');
        $this->command->info('Manager login: john.manager@peoplepulse.com / password');
        $this->command->info('User login: alice@peoplepulse.com / password');
    }
}
