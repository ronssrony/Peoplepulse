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

        return Inertia::render('Dashboard', [
            'todayAttendance' => $todayAttendance,
            'stats' => $stats,
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
        $startDate = $request->input('start_date', $today->copy()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', $today->toDateString());
        $subDepartmentId = $request->input('sub_department');

        // Get sub-department IDs that this manager manages
        $managedSubDepartmentIds = $user->getManagedSubDepartmentIds();

        $query = Attendance::with(['user.department', 'user.subDepartment'])
            ->whereBetween('date', [$startDate, $endDate])
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
                'start_date' => $startDate,
                'end_date' => $endDate,
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
        $startDate = $request->input('start_date', $today->copy()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', $today->toDateString());
        $departmentId = $request->input('department');
        $subDepartmentId = $request->input('sub_department');
        $employeeId = $request->input('employee');

        $query = Attendance::with(['user.department', 'user.subDepartment'])
            ->whereBetween('date', [$startDate, $endDate]);

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

        return Inertia::render('attendance/AdminDashboard', [
            'attendances' => $attendances,
            'departments' => $departments,
            'subDepartments' => $subDepartments,
            'employees' => $employees,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'department' => $departmentId,
                'sub_department' => $subDepartmentId,
                'employee' => $employeeId,
            ],
        ]);
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
}
