<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();
        $startOfYear = $today->copy()->startOfYear();

        // 1. Total Employees
        $totalEmployees = User::where('role', '!=', 'admin')->count();

        // 2. Monthly Attendance Rate
        // Logic: (Total Present Days / Total Possible Working Days) * 100
        // Simplification: (Count of Attendance Records) / (Total Employees * Working Days Passed)
        // Better: Just ratio of Present+Late vs Total Records if we assume records are created for absent too?
        // My system creates records for Clock-ins. Absent users might NOT have records unless I run a scheduled job or seeder.
        // But the previous `AttendanceService::getDepartmentSummary` counts absents by checking if `hasClockedIn()` is false OR no record exists.
        
        // Let's rely on actual attendance records for "Present/Late" vs "Total Business Days * Employees".
        
        $monthlyPresentCount = Attendance::whereBetween('date', [$startOfMonth, $today])
            ->whereNotNull('clock_in') // present or late
            ->count();
            
        // Estimate total working days passed in month (excluding weekends)
        // precise calc is complex, let's approximate or just use total days passed * 5/7
        $daysPassed = $today->day;
        // Total potential man-days = Employees * DaysPassed (roughly)
        // This is an estimate. For precision we'd need a holiday calendar.
        $totalPotentialMonthly = $totalEmployees * $daysPassed; 
        $monthlyRate = $totalPotentialMonthly > 0 ? round(($monthlyPresentCount / $totalPotentialMonthly) * 100, 1) : 0;


        // 3. Yearly Attendance Rate
        $yearlyPresentCount = Attendance::whereBetween('date', [$startOfYear, $today])
            ->whereNotNull('clock_in')
            ->count();
        
        $daysPassedYear = $today->dayOfYear;
        $totalPotentialYearly = $totalEmployees * $daysPassedYear;
        $yearlyRate = $totalPotentialYearly > 0 ? round(($yearlyPresentCount / $totalPotentialYearly) * 100, 1) : 0;


        // 4. Department Stats
        $departments = Department::withCount('users')->get()->map(function ($dept) use ($startOfMonth, $today) {
            $userIds = $dept->users->pluck('id');
            $presentCount = Attendance::whereIn('user_id', $userIds)
                ->whereBetween('date', [$startOfMonth, $today])
                ->whereNotNull('clock_in')
                ->count();
            
            // Average per employee
            $avgAttendance = $dept->users_count > 0 ? round($presentCount / $dept->users_count, 1) : 0;
            
            return [
                'name' => $dept->name,
                'employee_count' => $dept->users_count,
                'monthly_present_count' => $presentCount,
                'avg_attendance' => $avgAttendance,
            ];
        });

        return Inertia::render('Reports/Index', [
            'stats' => [
                'total_employees' => $totalEmployees,
                'monthly_rate' => $monthlyRate,
                'yearly_rate' => $yearlyRate,
                'monthly_present_count' => $monthlyPresentCount,
                'yearly_present_count' => $yearlyPresentCount,
            ],
            'department_stats' => $departments,
        ]);
    }
}
