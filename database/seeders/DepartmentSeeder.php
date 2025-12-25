<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if departments already exist
        if (Department::count() > 0) {
            $this->command->info('Departments already seeded, skipping...');
            return;
        }

        $departments = [
            [
                'name' => 'Engineering',
                'description' => 'Engineering and Development',
                'sub_departments' => [
                    ['name' => 'Frontend', 'description' => 'Frontend Development Team'],
                    ['name' => 'Backend', 'description' => 'Backend Development Team'],
                    ['name' => 'DevOps', 'description' => 'DevOps and Infrastructure'],
                    ['name' => 'QA', 'description' => 'Quality Assurance Team'],
                ],
            ],
            [
                'name' => 'Sales',
                'description' => 'Sales and Business Development',
                'sub_departments' => [
                    ['name' => 'Inside Sales', 'description' => 'Inside Sales Team'],
                    ['name' => 'Field Sales', 'description' => 'Field Sales Team'],
                    ['name' => 'Business Development', 'description' => 'Business Development Team'],
                ],
            ],
            [
                'name' => 'Marketing',
                'description' => 'Marketing and Communications',
                'sub_departments' => [
                    ['name' => 'Digital Marketing', 'description' => 'Digital Marketing Team'],
                    ['name' => 'Content', 'description' => 'Content Creation Team'],
                    ['name' => 'Brand', 'description' => 'Brand Management Team'],
                ],
            ],
            [
                'name' => 'Human Resources',
                'description' => 'Human Resources Department',
                'sub_departments' => [
                    ['name' => 'Recruitment', 'description' => 'Talent Acquisition'],
                    ['name' => 'Employee Relations', 'description' => 'Employee Relations Team'],
                    ['name' => 'Payroll', 'description' => 'Payroll Management'],
                ],
            ],
            [
                'name' => 'Finance',
                'description' => 'Finance and Accounting',
                'sub_departments' => [
                    ['name' => 'Accounting', 'description' => 'Accounting Team'],
                    ['name' => 'Financial Planning', 'description' => 'Financial Planning & Analysis'],
                    ['name' => 'Treasury', 'description' => 'Treasury Management'],
                ],
            ],
        ];

        foreach ($departments as $deptData) {
            $subDepartments = $deptData['sub_departments'];
            unset($deptData['sub_departments']);

            $department = Department::create($deptData);

            foreach ($subDepartments as $subDept) {
                $department->subDepartments()->create($subDept);
            }
        }

        $this->command->info('Departments and sub-departments seeded successfully!');
    }
}
