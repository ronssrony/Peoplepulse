<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if attendance records already exist
        if (Attendance::count() > 0) {
            $this->command->info('Attendance records already seeded, skipping...');
            return;
        }

        $users = User::where('role', '!=', 'admin')->get();
        $today = Carbon::today();
        
        // Create attendance for the last 30 days
        foreach ($users as $user) {
            for ($i = 0; $i < 30; $i++) {
                $date = $today->copy()->subDays($i);
                $dayName = $date->format('l');
                
                // Skip weekends
                if ($user->isWeekend($dayName)) {
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date,
                        'status' => 'weekend',
                        'is_late' => false,
                        'late_minutes' => 0,
                        'early_exit_minutes' => 0,
                        'break_minutes' => 60,
                    ]);
                    continue;
                }
                
                // Random sick leave (5% chance)
                if (rand(1, 100) <= 5) {
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date,
                        'status' => 'sick_leave',
                        'is_late' => false,
                        'late_minutes' => 0,
                        'early_exit_minutes' => 0,
                        'break_minutes' => 60,
                    ]);
                    continue;
                }
                
                // Random casual leave (3% chance)
                if (rand(1, 100) <= 3) {
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $date,
                        'status' => 'casual_leave',
                        'is_late' => false,
                        'late_minutes' => 0,
                        'early_exit_minutes' => 0,
                        'break_minutes' => 60,
                    ]);
                    continue;
                }
                
                // Regular attendance
                $officeStart = $date->copy()->setTime(9, 0);
                $officeEnd = $date->copy()->setTime(18, 0);
                
                // Random clock in time (8:30 to 10:00)
                $clockInHour = rand(8, 9);
                $clockInMinute = $clockInHour === 8 ? rand(30, 59) : rand(0, 59);
                $clockIn = $date->copy()->setTime($clockInHour, $clockInMinute);
                
                // Random clock out time (17:30 to 19:00)
                $clockOutHour = rand(17, 18);
                $clockOutMinute = $clockOutHour === 17 ? rand(30, 59) : rand(0, 59);
                $clockOut = $date->copy()->setTime($clockOutHour, $clockOutMinute);
                
                // Calculate if late
                $lateThreshold = $officeStart->copy()->addMinutes(15); // 9:15 AM
                $isLate = $clockIn->greaterThan($lateThreshold);
                $lateMinutes = $isLate ? $officeStart->diffInMinutes($clockIn) : 0;
                
                // Calculate early exit
                $earlyExitMinutes = $clockOut->lessThan($officeEnd) ? $clockOut->diffInMinutes($officeEnd) : 0;
                
                // Calculate work hours
                $grossMinutes = $clockIn->diffInMinutes($clockOut);
                $breakMinutes = 60;
                $netMinutes = max(0, $grossMinutes - $breakMinutes);
                
                Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'gross_minutes' => $grossMinutes,
                    'break_minutes' => $breakMinutes,
                    'net_minutes' => $netMinutes,
                    'is_late' => $isLate,
                    'late_minutes' => $lateMinutes,
                    'early_exit_minutes' => $earlyExitMinutes,
                    'status' => 'present',
                    'clock_in_ip' => '127.0.0.1',
                    'clock_out_ip' => '127.0.0.1',
                ]);
            }
        }
        
        $this->command->info('Attendance records seeded successfully!');
    }
}
