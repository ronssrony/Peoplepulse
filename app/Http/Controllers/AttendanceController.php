<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\AttendanceFilterRequest;
use App\Http\Requests\Attendance\OverrideAttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Main Dashboard - shows today's status and stats
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today();
        
        $todayAttendance = $this->attendanceService->getTodayAttendance($user);
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        $stats = $this->attendanceService->getAttendanceStats(
            $user,
            $monthStart->toDateString(),
            $monthEnd->toDateString()
        );

        $companyStats = null;
        // Optimization: In a real app, maybe only load this via async request or for specific roles
        // But requested features imply "Employee Dashboard" cards, so loaded for everyone or just admins/managers?
        // "in employee dasbhaord there will be another work... show me those exact user list"
        // This suggests visibility for all.
        $companyStats = $this->attendanceService->getGlobalAttendanceSummary($today->toDateString());

        return Inertia::render('Dashboard', [
            'todayAttendance' => $todayAttendance,
            'stats' => $stats,
            'companyStats' => $companyStats,
            'isWeekend' => $user->isWeekend($today->format('l')),
            'officeStartTime' => config('attendance.office_start_time'),
            'currentTime' => Carbon::now()->format('H:i:s'),
        ]);
    }

    /**
     * User Dashboard - shows personal attendance with month/year filter
     */
    public function userDashboard(Request $request): Response
    {
        $user = $request->user();
        $today = Carbon::today();
        
        $month = $request->input('month', $today->month);
        $year = $request->input('year', $today->year);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        $attendances = $this->attendanceService->getUserAttendance(
            $user,
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        // Get available years (from first attendance record to current year)
        $firstAttendance = Attendance::where('user_id', $user->id)
            ->orderBy('date', 'asc')
            ->first();
        $startYear = $firstAttendance ? Carbon::parse($firstAttendance->date)->year : $today->year;
        $availableYears = range($startYear, $today->year);

        return Inertia::render('attendance/UserDashboard', [
            'attendances' => $attendances,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Manager Dashboard - shows department attendance
     */
    public function managerDashboard(AttendanceFilterRequest $request): Response
    {
        $user = $request->user();
        
        if (!$user->isManager() && !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        // No default date filters - show all records if no filter applied
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $subDepartmentId = $request->input('sub_department');

        // Get sub-department IDs that this manager manages
        $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();

        $query = Attendance::with(['user.department', 'user.subDepartment'])
            ->whereHas('user', function ($q) use ($managedSubDepartmentIds, $subDepartmentId) {
                if ($subDepartmentId && in_array($subDepartmentId, $managedSubDepartmentIds)) {
                    $q->where('sub_department_id', $subDepartmentId);
                } elseif (!empty($managedSubDepartmentIds)) {
                    $q->whereIn('sub_department_id', $managedSubDepartmentIds);
                } else {
                    // If no managed sub-departments, show nothing
                    $q->whereRaw('1 = 0');
                }
            });

        // Apply date filter only if both dates are provided
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate(30);

        // Get department summary if manager has a department
        $departmentSummary = null;
        if ($user->department_id) {
            $departmentSummary = $this->attendanceService->getDepartmentSummary(
                $user->department_id,
                $today->toDateString()
            );
        }

        // Get sub-departments that this manager manages
        $subDepartments = $user->getManagedSubDepartments();

        return Inertia::render('attendance/ManagerDashboard', [
            'attendances' => $attendances,
            'departmentSummary' => $departmentSummary,
            'subDepartments' => $subDepartments,
            'filters' => [
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'sub_department' => $subDepartmentId,
            ],
        ]);
    }

    /**
     * Admin Dashboard - shows all attendance
     */
    public function adminDashboard(AttendanceFilterRequest $request): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        
        // No default date filters - show all records if no filter applied
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $departmentId = $request->input('department');
        $subDepartmentId = $request->input('sub_department');
        $employeeId = $request->input('employee');

        $query = Attendance::with(['user.department', 'user.subDepartment']);

        // Apply date filter only if both dates are provided
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        if ($employeeId) {
            $query->where('user_id', $employeeId);
        } elseif ($subDepartmentId) {
            $query->whereHas('user', function ($q) use ($subDepartmentId) {
                $q->where('sub_department_id', $subDepartmentId);
            });
        } elseif ($departmentId) {
            $query->whereHas('user', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $attendances = $query->orderBy('date', 'desc')->paginate(30);

        // Get all departments, sub-departments, and employees for filters
        $departments = \App\Models\Department::active()->get(['id', 'name']);
        $subDepartments = \App\Models\SubDepartment::active()->with('department:id,name')->get(['id', 'name', 'department_id']);
        $employees = User::where('role', '!=', 'admin')->with(['department:id,name', 'subDepartment:id,name'])->get(['id', 'name', 'employee_id', 'department_id', 'sub_department_id']);

        // Get company-wide summary for today
        $companySummary = $this->attendanceService->getGlobalAttendanceSummary($today->toDateString());

        return Inertia::render('attendance/AdminDashboard', [
            'attendances' => $attendances,
            'departments' => $departments,
            'subDepartments' => $subDepartments,
            'employees' => $employees,
            'companySummary' => $companySummary,
            'filters' => [
                'start_date' => $startDate ?? '',
                'end_date' => $endDate ?? '',
                'department' => $departmentId,
                'sub_department' => $subDepartmentId,
                'employee' => $employeeId,
            ],
        ]);
    }

    /**
     * Export attendance records
     */
    public function export(AttendanceFilterRequest $request)
    {
        $user = $request->user();
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $type = $request->input('type', 'csv'); // Default to csv
        
        $attendances = collect([]);

        if ($user->isAdmin()) {
             $attendances = $this->attendanceService->getAllAttendance(
                $startDate, 
                $endDate, 
                $request->input('department'), 
                $request->input('sub_department'),
                $request->input('employee')
            );
        } elseif ($user->isManager()) {
             $attendances = $this->attendanceService->getManagerVisibleAttendance(
                $user, 
                $startDate, 
                $endDate, 
                $request->input('sub_department')
            );
        }

        $filename = "attendance_export_" . date('Y-m-d_H-i') . "." . $type;
        $format = $type === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\AttendanceExport($attendances), $filename, $format);
    }

    /**
     * Clock in
     */
    public function clockIn(Request $request): RedirectResponse
    {
        try {
            $this->attendanceService->clockIn(
                $request->user(),
                $request->ip(),
                $request->userAgent()
            );

            return back()->with('success', 'Clocked in successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Clock out
     */
    public function clockOut(Request $request): RedirectResponse
    {
        try {
            $this->attendanceService->clockOut(
                $request->user(),
                $request->ip(),
                $request->userAgent()
            );

            return back()->with('success', 'Clocked out successfully!');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin override attendance
     */
    public function override(OverrideAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        try {
            $data = $request->only(['clock_in', 'clock_out', 'break_minutes', 'is_late']);
            $reason = $request->input('reason');

            $this->attendanceService->override(
                $attendance,
                $request->user(),
                $data,
                $reason,
                $request->ip()
            );

            return back()->with('success', 'Attendance record updated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get attendance details (for modal)
     */
    public function show(Attendance $attendance): Response
    {
        $this->authorize('view', $attendance);

        return Inertia::render('attendance/Show', [
            'attendance' => $attendance->load(['user', 'auditLogs.changedByUser']),
        ]);
    }

    /**
     * Manager Analytics Dashboard with charts
     */
    public function managerAnalytics(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isManager() && !$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfYear = $today->copy()->startOfYear();
        
        // Get managed employees
        $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
        $managedEmployees = User::whereIn('sub_department_id', $managedSubDepartmentIds)
            ->with(['department:id,name', 'subDepartment:id,name'])
            ->get();
        
        $managedEmployeeIds = $managedEmployees->pluck('id')->toArray();

        // Monthly attendance trend (last 6 months)
        $monthlyTrend = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $presentCount = Attendance::whereIn('user_id', $managedEmployeeIds)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->whereNotNull('clock_in')
                ->count();
            
            $lateCount = Attendance::whereIn('user_id', $managedEmployeeIds)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('is_late', true)
                ->count();
            
            $monthlyTrend[] = [
                'month' => $month->format('M Y'),
                'present' => $presentCount,
                'late' => $lateCount,
            ];
        }

        // Weekly attendance this month
        $weeklyData = [];
        $currentWeekStart = $startOfMonth->copy();
        $weekNum = 1;
        while ($currentWeekStart->lte($today)) {
            $weekEnd = $currentWeekStart->copy()->endOfWeek()->gt($today) 
                ? $today 
                : $currentWeekStart->copy()->endOfWeek();
            
            $presentCount = Attendance::whereIn('user_id', $managedEmployeeIds)
                ->whereBetween('date', [$currentWeekStart, $weekEnd])
                ->whereNotNull('clock_in')
                ->count();
            
            $weeklyData[] = [
                'week' => 'Week ' . $weekNum,
                'present' => $presentCount,
            ];
            
            $currentWeekStart->addWeek();
            $weekNum++;
        }

        // Attendance status breakdown for current month
        $monthlyPresent = Attendance::whereIn('user_id', $managedEmployeeIds)
            ->whereBetween('date', [$startOfMonth, $today])
            ->whereNotNull('clock_in')
            ->count();
        
        $monthlyLate = Attendance::whereIn('user_id', $managedEmployeeIds)
            ->whereBetween('date', [$startOfMonth, $today])
            ->where('is_late', true)
            ->count();
        
        $monthlyOnTime = $monthlyPresent - $monthlyLate;

        // Top 5 late employees
        $topLateEmployees = Attendance::whereIn('user_id', $managedEmployeeIds)
            ->whereBetween('date', [$startOfMonth, $today])
            ->where('is_late', true)
            ->selectRaw('user_id, count(*) as late_count')
            ->groupBy('user_id')
            ->orderByDesc('late_count')
            ->limit(5)
            ->with('user:id,name,employee_id')
            ->get();

        // Average working hours per employee this month
        $avgHoursData = Attendance::whereIn('user_id', $managedEmployeeIds)
            ->whereBetween('date', [$startOfMonth, $today])
            ->whereNotNull('net_minutes')
            ->selectRaw('user_id, AVG(net_minutes) as avg_minutes')
            ->groupBy('user_id')
            ->with('user:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->user->name ?? 'Unknown',
                    'hours' => round($item->avg_minutes / 60, 1),
                ];
            });

        // Sub-department breakdown
        $subDeptStats = [];
        $subDepartments = $user->getManagedSubDepartments();
        foreach ($subDepartments as $subDept) {
            $subDeptEmployeeIds = User::where('sub_department_id', $subDept->id)->pluck('id');
            $present = Attendance::whereIn('user_id', $subDeptEmployeeIds)
                ->whereDate('date', $today)
                ->whereNotNull('clock_in')
                ->count();
            $total = $subDeptEmployeeIds->count();
            
            $subDeptStats[] = [
                'name' => $subDept->name,
                'present' => $present,
                'total' => $total,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        }

        return Inertia::render('attendance/ManagerAnalytics', [
            'employees' => $managedEmployees,
            'stats' => [
                'total_employees' => count($managedEmployeeIds),
                'monthly_present' => $monthlyPresent,
                'monthly_late' => $monthlyLate,
                'monthly_on_time' => $monthlyOnTime,
            ],
            'charts' => [
                'monthly_trend' => $monthlyTrend,
                'weekly_data' => $weeklyData,
                'top_late_employees' => $topLateEmployees,
                'avg_hours_data' => $avgHoursData,
                'sub_dept_stats' => $subDeptStats,
            ],
        ]);
    }

    /**
     * Admin Analytics Dashboard with charts
     */
    public function adminAnalytics(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        $startOfMonth = $today->copy()->startOfMonth();
        $startOfYear = $today->copy()->startOfYear();
        
        // Get all employees
        $allEmployees = User::where('role', '!=', 'admin')
            ->with(['department:id,name', 'subDepartment:id,name'])
            ->get();
        
        $allEmployeeIds = $allEmployees->pluck('id')->toArray();

        // Monthly attendance trend (last 12 months)
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            
            $presentCount = Attendance::whereIn('user_id', $allEmployeeIds)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->whereNotNull('clock_in')
                ->count();
            
            $lateCount = Attendance::whereIn('user_id', $allEmployeeIds)
                ->whereBetween('date', [$monthStart, $monthEnd])
                ->where('is_late', true)
                ->count();
            
            $monthlyTrend[] = [
                'month' => $month->format('M'),
                'present' => $presentCount,
                'late' => $lateCount,
            ];
        }

        // Department-wise attendance
        $departments = \App\Models\Department::active()->get();
        $departmentStats = [];
        foreach ($departments as $dept) {
            $deptEmployeeIds = User::where('department_id', $dept->id)->pluck('id');
            $present = Attendance::whereIn('user_id', $deptEmployeeIds)
                ->whereDate('date', $today)
                ->whereNotNull('clock_in')
                ->count();
            $late = Attendance::whereIn('user_id', $deptEmployeeIds)
                ->whereDate('date', $today)
                ->where('is_late', true)
                ->count();
            $total = $deptEmployeeIds->count();
            
            $departmentStats[] = [
                'name' => $dept->name,
                'present' => $present,
                'late' => $late,
                'absent' => $total - $present,
                'total' => $total,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        }

        // Attendance status breakdown for current month
        $monthlyPresent = Attendance::whereIn('user_id', $allEmployeeIds)
            ->whereBetween('date', [$startOfMonth, $today])
            ->whereNotNull('clock_in')
            ->count();
        
        $monthlyLate = Attendance::whereIn('user_id', $allEmployeeIds)
            ->whereBetween('date', [$startOfMonth, $today])
            ->where('is_late', true)
            ->count();
        
        $monthlyOnTime = $monthlyPresent - $monthlyLate;

        // Yearly stats
        $yearlyPresent = Attendance::whereIn('user_id', $allEmployeeIds)
            ->whereBetween('date', [$startOfYear, $today])
            ->whereNotNull('clock_in')
            ->count();
        
        $yearlyLate = Attendance::whereIn('user_id', $allEmployeeIds)
            ->whereBetween('date', [$startOfYear, $today])
            ->where('is_late', true)
            ->count();

        // Top 10 late employees company-wide
        $topLateEmployees = Attendance::whereIn('user_id', $allEmployeeIds)
            ->whereBetween('date', [$startOfMonth, $today])
            ->where('is_late', true)
            ->selectRaw('user_id, count(*) as late_count')
            ->groupBy('user_id')
            ->orderByDesc('late_count')
            ->limit(10)
            ->with('user:id,name,employee_id,department_id')
            ->get();

        // Average hours per department
        $avgHoursByDept = [];
        foreach ($departments as $dept) {
            $deptEmployeeIds = User::where('department_id', $dept->id)->pluck('id');
            $avgMinutes = Attendance::whereIn('user_id', $deptEmployeeIds)
                ->whereBetween('date', [$startOfMonth, $today])
                ->whereNotNull('net_minutes')
                ->avg('net_minutes');
            
            $avgHoursByDept[] = [
                'name' => $dept->name,
                'hours' => round(($avgMinutes ?? 0) / 60, 1),
            ];
        }

        return Inertia::render('attendance/AdminAnalytics', [
            'employees' => $allEmployees,
            'stats' => [
                'total_employees' => count($allEmployeeIds),
                'monthly_present' => $monthlyPresent,
                'monthly_late' => $monthlyLate,
                'monthly_on_time' => $monthlyOnTime,
                'yearly_present' => $yearlyPresent,
                'yearly_late' => $yearlyLate,
            ],
            'charts' => [
                'monthly_trend' => $monthlyTrend,
                'department_stats' => $departmentStats,
                'top_late_employees' => $topLateEmployees,
                'avg_hours_by_dept' => $avgHoursByDept,
            ],
        ]);
    }

    /**
     * Employee Report - Generate monthly attendance summary for all employees
     */
    public function employeeReport(Request $request): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        $today = Carbon::today();
        $month = $request->input('month', $today->month);
        $year = $request->input('year', $today->year);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

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
        $employeeSummaries = $employees->map(function ($employee) use ($startDate, $endDate) {
            $attendances = Attendance::where('user_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();
            
            $attendanceCount = $attendances->count();
            $lateCount = $attendances->where('is_late', true)->count();
            $totalNetMinutes = $attendances->sum('net_minutes');
            
            return [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
                'department_name' => $employee->department?->name,
                'sub_department_name' => $employee->subDepartment?->name,
                'designation' => $employee->designation,
                'attendance_count' => $attendanceCount,
                'late_count' => $lateCount,
                'on_time_count' => $attendanceCount - $lateCount,
                'total_hours' => round($totalNetMinutes / 60, 1),
                'avg_hours_per_day' => $attendanceCount > 0 ? round(($totalNetMinutes / $attendanceCount) / 60, 1) : 0,
            ];
        });

        // Get available years (from first attendance record to current year)
        $firstAttendance = Attendance::orderBy('date', 'asc')->first();
        $startYear = $firstAttendance ? Carbon::parse($firstAttendance->date)->year : $today->year;
        $availableYears = range($startYear, $today->year);

        return Inertia::render('attendance/EmployeeReport', [
            'employees' => $employees->map(fn($e) => ['id' => $e->id, 'name' => $e->name, 'employee_id' => $e->employee_id]),
            'employeeSummaries' => $employeeSummaries,
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Export Employee Report - Monthly summary for all employees
     */
    public function exportEmployeeReport(Request $request)
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
        $employeeSummaries = $employees->map(function ($employee) use ($startDate, $endDate) {
            $attendances = Attendance::where('user_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();
            
            $attendanceCount = $attendances->count();
            $lateCount = $attendances->where('is_late', true)->count();
            $totalNetMinutes = $attendances->sum('net_minutes');
            
            return [
                'employee_id' => $employee->employee_id,
                'name' => $employee->name,
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
        $filename = "employee_report_{$monthName}_{$year}." . $type;
        $format = $type === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MonthlyEmployeeReportExport($employeeSummaries, $monthName, $year),
            $filename,
            $format
        );
    }

    /**
     * Employee Attendance Detail - Shows individual employee's attendance history
     */
    public function employeeAttendanceDetail(Request $request, User $employee): Response
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        // Verify manager can access this employee
        if ($user->isManager()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            if (!in_array($employee->sub_department_id, $managedSubDepartmentIds)) {
                abort(403, 'Unauthorized access to this employee.');
            }
        }

        $today = Carbon::today();
        $month = $request->input('month', $today->month);
        $year = $request->input('year', $today->year);
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get employee's attendance records
        $attendances = Attendance::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'date' => $attendance->date,
                    'clock_in' => $attendance->clock_in,
                    'clock_out' => $attendance->clock_out,
                    'is_late' => $attendance->is_late,
                    'net_minutes' => $attendance->net_minutes,
                    'gross_minutes' => $attendance->gross_minutes,
                    'break_minutes' => $attendance->break_minutes,
                    'status' => $attendance->status,
                    'late_minutes' => $attendance->late_minutes,
                ];
            });

        // Calculate summary
        $totalAttendance = $attendances->count();
        $lateCount = $attendances->where('is_late', true)->count();
        $totalMinutes = $attendances->sum('net_minutes');

        // Get available years
        $firstAttendance = Attendance::orderBy('date', 'asc')->first();
        $startYear = $firstAttendance ? Carbon::parse($firstAttendance->date)->year : $today->year;
        $availableYears = range($startYear, $today->year);

        return Inertia::render('attendance/EmployeeAttendanceDetail', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee_id,
                'department_name' => $employee->department?->name,
                'sub_department_name' => $employee->subDepartment?->name,
                'designation' => $employee->designation,
            ],
            'attendances' => $attendances,
            'summary' => [
                'totalAttendance' => $totalAttendance,
                'lateCount' => $lateCount,
                'onTimeCount' => $totalAttendance - $lateCount,
                'totalHours' => round($totalMinutes / 60, 1),
                'avgHoursPerDay' => $totalAttendance > 0 ? round(($totalMinutes / $totalAttendance) / 60, 1) : 0,
            ],
            'filters' => [
                'month' => (int) $month,
                'year' => (int) $year,
            ],
            'availableYears' => $availableYears,
        ]);
    }

    /**
     * Export individual employee's attendance history
     */
    public function exportEmployeeAttendanceDetail(Request $request, User $employee)
    {
        $user = $request->user();
        
        if (!$user->isAdmin() && !$user->isManager()) {
            abort(403, 'Unauthorized access.');
        }

        // Verify manager can access this employee
        if ($user->isManager()) {
            $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();
            if (!in_array($employee->sub_department_id, $managedSubDepartmentIds)) {
                abort(403, 'Unauthorized access to this employee.');
            }
        }

        $today = Carbon::today();
        $month = $request->input('month', $today->month);
        $year = $request->input('year', $today->year);
        $type = $request->input('type', 'csv');
        
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Get employee's attendance records
        $attendances = Attendance::where('user_id', $employee->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $monthName = Carbon::createFromDate($year, $month, 1)->format('F');
        $filename = "{$employee->employee_id}_attendance_{$monthName}_{$year}." . $type;
        $format = $type === 'xlsx' ? \Maatwebsite\Excel\Excel::XLSX : \Maatwebsite\Excel\Excel::CSV;

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\EmployeeReportExport($employee, $attendances),
            $filename,
            $format
        );
    }
}
