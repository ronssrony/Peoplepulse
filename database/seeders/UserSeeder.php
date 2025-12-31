<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clean up existing data to avoid duplicates/conflicts
        // We disable foreign key checks to safely truncate related tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('manager_sub_departments')->truncate();
        User::truncate();
        SubDepartment::truncate();
        Department::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Existing user/department data cleared.');

        // 2. Define the employee data
        // Note: For rows 22 & 23, the raw data columns seemed shifted (Sales Executive appearing as Department).
        // I have corrected this to: Dept="Business", Sub="Sales", Desig="Sales Executive" based on context.
        $employees = [
            [
                'employee_id' => 'BDFB001',
                'name' => 'Mojtahidul Islam',
                'department' => 'Board',
                'sub_department' => 'Board',
                'designation' => 'CEO',
                'email' => 'mojtahid1000@gmail.com',
                'role' => 'admin',
            ],
            [
                'employee_id' => 'BDFB002',
                'name' => 'Sirajul Islam Jony',
                'department' => 'Board',
                'sub_department' => 'Board',
                'designation' => 'CTO',
                'email' => 'sijony@onlinetechacademy.org',
                'role' => 'admin',
            ],
            [
                'employee_id' => 'BDFB215',
                'name' => 'Md Jahidul Islam',
                'department' => 'Business',
                'sub_department' => 'Creative',
                'designation' => 'UI/UX Designer',
                'email' => 'mdjahidul306@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB201',
                'name' => 'Yeasir Arafat',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Senior Software Engineer',
                'email' => 'yeasirarafat321@gmail.com', // Also yeasirarafat@bdfunnelbuilder.org
                'role' => 'manager',
            ],
            [
                'employee_id' => 'BDFB212',
                'name' => 'Md. Mosabberul Islam',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Senior Software Engineer',
                'email' => 'mosabberulislam55@gmail.com', // Also leon@bdfunnelbuilder.org
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB205',
                'name' => 'Anik Chandra',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Software Engineer',
                'email' => 'dasssanik124102@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB213',
                'name' => 'Jannatul Ferdoush',
                'department' => 'Software Engineering',
                'sub_department' => 'SQA',
                'designation' => 'Software QA Engineer',
                'email' => 'jannatulferdoushtonima@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB203',
                'name' => 'Saurav Pal',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Software Engineer',
                'email' => 'sauravpaljoy@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB214',
                'name' => 'Ankita Paul',
                'department' => 'Software Engineering',
                'sub_department' => 'SQA',
                'designation' => 'Software QA Engineer',
                'email' => 'paulankita0715@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB208',
                'name' => 'Md. Shoeab Akter Rafi',
                'department' => 'Business',
                'sub_department' => 'Support',
                'designation' => 'Support Executive',
                'email' => 'shoeabrafi553@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB206',
                'name' => 'Md. Maruf Hossain',
                'department' => 'Business',
                'sub_department' => 'Support',
                'designation' => 'Support Executive',
                'email' => 'maruf00019@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB209',
                'name' => 'S. M. Abdulla Hil Kafi',
                'department' => 'Business',
                'sub_department' => 'Support',
                'designation' => 'Junior Support Executive',
                'email' => 'sakafi.m@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB216',
                'name' => 'Fahmid Hossain Mishkat',
                'department' => 'People & Operations',
                'sub_department' => 'People Operations',
                'designation' => 'Junior People & Operations Executive',
                'email' => 'mishkatfahmidhossain@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB202',
                'name' => 'Mohammad Sabbir Ahmed Lemon',
                'department' => 'Business',
                'sub_department' => 'Support',
                'designation' => 'Senior Support Executive',
                'email' => 'hrbdfunnel07@gmail.com', // Also sabbir@bdfunnelbuilder.org
                'role' => 'manager',
            ],
            [
                'employee_id' => 'BDFB218',
                'name' => 'MD Shoeb Sikder Pappu',
                'department' => 'Business',
                'sub_department' => 'Support',
                'designation' => 'Junior Support Executive',
                'email' => 'shoebsikder1@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB217',
                'name' => 'Anup Chandra Mondal',
                'department' => 'Software Engineering',
                'sub_department' => 'SQA',
                'designation' => 'Junior Software QA Engineer',
                'email' => 'anupmondal6970@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB221',
                'name' => 'Ziaul Hassan',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Junior Software Engineer',
                'email' => 'ziaulhasanf@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB220',
                'name' => 'Rafeeuzzaman Dihan',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Junior Software Engineer',
                'email' => 'rafeeuzzamandihan@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB222',
                'name' => 'Sheikh Sahed Hasan',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Junior Software Engineer',
                'email' => 'shahed34563456@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB223',
                'name' => 'Md Abdullah Al Mamun',
                'department' => 'Software Engineering',
                'sub_department' => 'Development',
                'designation' => 'Junior Software Engineer',
                'email' => 'ronssrony@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB224',
                'name' => 'Moontaha',
                'department' => 'Business',
                'sub_department' => 'Creative',
                'designation' => 'Content Executive',
                'email' => 'moon2024taha@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB225',
                'name' => 'Habibur Rahman',
                'department' => 'Business',
                'sub_department' => 'Sales',
                'designation' => 'Sales Executive',
                'email' => 'habibur0018@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB226',
                'name' => 'Injamul Haque Sunto',
                'department' => 'Business',
                'sub_department' => 'Sales',
                'designation' => 'Sales Executive',
                'email' => 'shantoinjamul@gmail.com',
                'role' => 'user',
            ],
            [
                'employee_id' => 'BDFB227',
                'name' => 'Towsif Sakib',
                'department' => 'People & Operations',
                'sub_department' => 'People Operations',
                'designation' => 'People & Operations Manager',
                'email' => 'towsif@bdfunnelbuilder.net',
                'role' => 'admin',
            ],
        ];

        foreach ($employees as $emp) {
            // Find or Create Department
            $department = Department::firstOrCreate(
                ['name' => $emp['department']],
                ['is_active' => true]
            );

            // Find or Create SubDepartment
            // Note: A sub-department belongs to a department. 
            // In some schemas, names might duplicate across departments, so we should check department_id + name.
            $subDepartment = SubDepartment::firstOrCreate(
                [
                    'department_id' => $department->id,
                    'name' => $emp['sub_department']
                ],
                [
                    'is_active' => true
                ]
            );

            // Create User
            $user = User::create([
                'employee_id' => $emp['employee_id'],
                'name' => $emp['name'],
                'email' => $emp['email'],
                'password' => Hash::make('password'), // Default password
                'department_id' => $department->id,
                'sub_department_id' => $subDepartment->id,
                'designation' => $emp['designation'],
                'role' => $emp['role'],
                'weekend_days' => ['friday'], // Defaulting to Friday as weekend, can be adjusted
                'email_verified_at' => now(),
            ]);

            // If Role is Manager (or Admin acting as Manager logic), assign managed SubDepartments?
            // The prompt doesn't explicitly state which managers manage which sub-departments.
            // For now, we will leave the pivot table empty unless we want to infer it.
            // Eg: Yeasir Arafat is Manager of Dev? 
            // Since the requirements didn't specify the management scope, we'll stick to basic user creation.
        }
        
        $this->command->info(count($employees) . ' users seeded successfully.');
    }
}
