<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\Department;
use App\Models\SubDepartment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        
        // Get month/year from request or use current
        $selectedMonth = $request->input('month', $today->month);
        $selectedYear = $request->input('year', $today->year);
        
        $selectedDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
        $startOfMonth = $selectedDate->copy()->startOfMonth();
        $endOfMonth = $selectedDate->copy()->endOfMonth();
        
        // If selected month is current month, use today as end date
        $effectiveEndDate = $endOfMonth->gt($today) ? $today : $endOfMonth;

        // Get employees based on role
        if ($user->isAdmin()) {
            $employeeIds = User::where('role', '!=', 'admin')->pluck('id')->toArray();
            $employees = User::where('role', '!=', 'admin')
                ->with(['department:id,name', 'subDepartment:id,name'])
                ->orderBy('name')
                ->get();
            $departments = Department::active()->withCount('users')->with('users')->get();
        } else {
            // Manager - only managed employees
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $employeeIds = User::whereIn('sub_department_id', $managedSubDepartmentIds)->pluck('id')->toArray();
            $employees = User::whereIn('sub_department_id', $managedSubDepartmentIds)
                ->with(['department:id,name', 'subDepartment:id,name'])
                ->orderBy('name')
                ->get();
            $departments = collect([]);
            if ($user->department_id) {
                $departments = Department::where('id', $user->department_id)->withCount('users')->with('users')->get();
            }
        }

        $totalEmployees = count($employeeIds);

        // === SUMMARY STATS (for selected month) ===
        
        // Selected Month stats
        $monthlyPresent = Attendance::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
            ->whereNotNull('clock_in')
            ->count();
        
        $monthlyLate = Attendance::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
            ->where('is_late', true)
            ->count();

        // Average working hours for selected month
        $avgWorkingMinutes = Attendance::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
            ->whereNotNull('net_minutes')
            ->avg('net_minutes');

        // === CHART DATA (for selected month) ===

        // 1. Daily Trend for selected month
        $dailyTrend = [];
        $daysInMonth = $startOfMonth->daysInMonth;
        $daysToShow = min($daysInMonth, $effectiveEndDate->day);
        
        for ($i = 1; $i <= $daysToShow; $i++) {
            $date = $startOfMonth->copy()->day($i);
            
            $present = Attendance::whereIn('user_id', $employeeIds)
                ->whereDate('date', $date)
                ->whereNotNull('clock_in')
                ->count();
            
            $late = Attendance::whereIn('user_id', $employeeIds)
                ->whereDate('date', $date)
                ->where('is_late', true)
                ->count();
            
            $dailyTrend[] = [
                'date' => $date->format('M d'),
                'day' => $date->format('D'),
                'present' => $present,
                'late' => $late,
            ];
        }

        // 2. Department-wise Distribution for selected month
        $departmentDistribution = [];
        foreach ($departments as $dept) {
            $deptEmployeeIds = $dept->users->pluck('id')->toArray();
            if (empty($deptEmployeeIds)) continue;
            
            $present = Attendance::whereIn('user_id', $deptEmployeeIds)
                ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
                ->whereNotNull('clock_in')
                ->count();
            
            $late = Attendance::whereIn('user_id', $deptEmployeeIds)
                ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
                ->where('is_late', true)
                ->count();
            
            $workingDays = $startOfMonth->diffInWeekdays($effectiveEndDate) + 1;
            $expectedAttendance = count($deptEmployeeIds) * $workingDays;
            
            $departmentDistribution[] = [
                'name' => $dept->name,
                'total' => count($deptEmployeeIds),
                'present' => $present,
                'absent' => max(0, $expectedAttendance - $present),
                'late' => $late,
                'on_time' => $present - $late,
            ];
        }

        // 3. Attendance Status Breakdown for selected month
        $statusBreakdown = [
            'on_time' => $monthlyPresent - $monthlyLate,
            'late' => $monthlyLate,
            'total_records' => $monthlyPresent,
        ];

        // 4. Top Performers for selected month
        $topPerformers = Attendance::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
            ->whereNotNull('clock_in')
            ->where('is_late', false)
            ->selectRaw('user_id, count(*) as on_time_count')
            ->groupBy('user_id')
            ->orderByDesc('on_time_count')
            ->limit(5)
            ->with('user:id,name,employee_id')
            ->get();

        // 5. Needs Attention for selected month
        $needsAttention = Attendance::whereIn('user_id', $employeeIds)
            ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
            ->where('is_late', true)
            ->selectRaw('user_id, count(*) as late_count')
            ->groupBy('user_id')
            ->orderByDesc('late_count')
            ->limit(5)
            ->with('user:id,name,employee_id')
            ->get();

        // 6. Average Hours by Day of Week for selected month
        $hoursByDayOfWeek = [];
        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        for ($day = 0; $day <= 6; $day++) {
            $avgMinutes = Attendance::whereIn('user_id', $employeeIds)
                ->whereBetween('date', [$startOfMonth, $effectiveEndDate])
                ->whereRaw('DAYOFWEEK(date) = ?', [$day + 1])
                ->whereNotNull('net_minutes')
                ->avg('net_minutes');
            
            $hoursByDayOfWeek[] = [
                'day' => substr($dayNames[$day], 0, 3),
                'hours' => round(($avgMinutes ?? 0) / 60, 1),
            ];
        }

        return Inertia::render('Reports/Index', [
            'stats' => [
                'total_employees' => $totalEmployees,
                'monthly_present' => $monthlyPresent,
                'monthly_late' => $monthlyLate,
                'avg_working_hours' => round(($avgWorkingMinutes ?? 0) / 60, 1),
            ],
            'charts' => [
                'daily_trend' => $dailyTrend,
                'department_distribution' => $departmentDistribution,
                'status_breakdown' => $statusBreakdown,
                'hours_by_day' => $hoursByDayOfWeek,
            ],
            'lists' => [
                'top_performers' => $topPerformers,
                'needs_attention' => $needsAttention,
            ],
            'filters' => [
                'month' => (int) $selectedMonth,
                'year' => (int) $selectedYear,
            ],
        ]);
    }
    
    public function export(Request $request)
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        $month = $request->input('month', $today->month);
        $year = $request->input('year', $today->year);
        $type = $request->input('type', 'csv');
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $effectiveEndDate = $endDate->gt($today) ? $today : $endDate;

        // Get available employees based on role
        if ($user->isAdmin()) {
            $employees = User::where('role', '!=', 'admin')
                ->with(['department:id,name', 'subDepartment:id,name'])
                ->orderBy('name')
                ->get();
        } else {
            // Manager - only managed employees
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            $employees = User::whereIn('sub_department_id', $managedSubDepartmentIds)
                ->with(['department:id,name', 'subDepartment:id,name'])
                ->orderBy('name')
                ->get();
        }

        // Calculate summary for each employee
        $employeeSummaries = $employees->map(function ($employee) use ($startDate, $effectiveEndDate) {
            $attendances = Attendance::where('user_id', $employee->id)
                ->whereBetween('date', [$startDate, $effectiveEndDate])
                ->get();
            
            $attendanceCount = $attendances->count();
            $lateCount = $attendances->where('is_late', true)->count();
            $totalNetMinutes = $attendances->sum('net_minutes');
            
            return [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'email' => $employee->email,
                'phone' => $employee->phone ?? '-',
                'department' => $employee->department?->name ?? '-',
                'sub_department' => $employee->subDepartment?->name ?? '-',
                'designation' => $employee->designation ?? '-',
                'attendance_count' => $attendanceCount,
                'on_time_count' => $attendanceCount - $lateCount,
                'late_count' => $lateCount,
                'total_hours' => round($totalNetMinutes / 60, 1),
                'avg_hours_per_day' => $attendanceCount > 0 ? round(($totalNetMinutes / $attendanceCount) / 60, 1) : 0,
            ];
        });

        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');
        $filename = "attendance_report_{$monthName}_{$year}." . $type;
        $format = $type === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\ReportExport($employeeSummaries, $monthName, $year),
            $filename,
            $format
        );
    }
}
