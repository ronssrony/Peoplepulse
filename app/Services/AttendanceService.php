<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Clock in a user
     */
    public function clockIn(User $user, ?string $ipAddress = null, ?string $userAgent = null): Attendance
    {
        $today = Carbon::today();
        $now = Carbon::now();

        // Determine if late
        $officeStartTime = $this->getOfficeStartTime($today);
        $graceMinutes = config('attendance.late_grace_minutes', 15);
        $lateThreshold = $officeStartTime->copy()->addMinutes($graceMinutes);
        $isLate = $now->greaterThan($lateThreshold);

        // Calculate late minutes (from office start time)
        $lateMinutes = 0;
        if ($isLate) {
            $lateMinutes = max(0, $officeStartTime->diffInMinutes($now));
        }

        // Check if already clocked in today
        $existing = Attendance::forUser($user->id)->forDate($today)->first();

        if ($existing && $existing->hasClockedIn()) {
            throw new \Exception('You have already clocked in today.');
        }

        // Determine status
        $status = 'present';
        if ($user->isWeekend($today->format('l'))) {
            $status = 'weekend';
        }

        // Create or update attendance record
        $attendance = Attendance::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $today,
            ],
            [
                'clock_in' => $now,
                'is_late' => $isLate,
                'late_minutes' => $lateMinutes,
                'status' => $status,
                'clock_in_ip' => $ipAddress,
                'clock_in_user_agent' => $userAgent,
                'break_minutes' => config('attendance.default_break_minutes', 60),
            ]
        );

        return $attendance->fresh();
    }

    /**
     * Clock out a user
     */
    public function clockOut(User $user, ?string $ipAddress = null, ?string $userAgent = null): Attendance
    {
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::forUser($user->id)->forDate($today)->first();

        if (!$attendance || !$attendance->hasClockedIn()) {
            throw new \Exception('You must clock in before clocking out.');
        }

        if ($attendance->hasClockedOut()) {
            throw new \Exception('You have already clocked out today.');
        }

        // Calculate hours
        $clockIn = $attendance->clock_in;
        $grossMinutes = $clockIn->diffInMinutes($now);
        $netMinutes = max(0, $grossMinutes - $attendance->break_minutes);

        // Calculate early exit minutes
        $officeEndTime = $this->getOfficeEndTime($today);
        $earlyExitMinutes = 0;
        if ($now->lessThan($officeEndTime)) {
            $earlyExitMinutes = max(0, $now->diffInMinutes($officeEndTime));
        }

        $attendance->update([
            'clock_out' => $now,
            'gross_minutes' => $grossMinutes,
            'net_minutes' => $netMinutes,
            'early_exit_minutes' => $earlyExitMinutes,
            'clock_out_ip' => $ipAddress,
            'clock_out_user_agent' => $userAgent,
        ]);

        return $attendance->fresh();
    }

    /**
     * Admin override attendance record
     */
    public function override(
        Attendance $attendance,
        User $admin,
        array $data,
        string $reason,
        ?string $ipAddress = null
    ): Attendance {
        return DB::transaction(function () use ($attendance, $admin, $data, $reason, $ipAddress) {
            // Log all changes
            foreach ($data as $field => $newValue) {
                $oldValue = $attendance->{$field};

                if ($oldValue != $newValue) {
                    AttendanceAuditLog::create([
                        'attendance_id' => $attendance->id,
                        'changed_by' => $admin->id,
                        'field_changed' => $field,
                        'old_value' => $oldValue instanceof Carbon ? $oldValue->toDateTimeString() : $oldValue,
                        'new_value' => $newValue,
                        'reason' => $reason,
                        'ip_address' => $ipAddress,
                    ]);
                }
            }

            // If clock_in or clock_out changed, recalculate hours
            if (isset($data['clock_in']) || isset($data['clock_out'])) {
                $clockIn = isset($data['clock_in'])
                    ? Carbon::parse($data['clock_in'])
                    : $attendance->clock_in;
                $clockOut = isset($data['clock_out'])
                    ? Carbon::parse($data['clock_out'])
                    : $attendance->clock_out;

                if ($clockIn && $clockOut) {
                    $grossMinutes = $clockIn->diffInMinutes($clockOut);
                    $breakMinutes = $data['break_minutes'] ?? $attendance->break_minutes;
                    $data['gross_minutes'] = $grossMinutes;
                    $data['net_minutes'] = max(0, $grossMinutes - $breakMinutes);
                }

                // Recalculate is_late if clock_in changed
                if (isset($data['clock_in'])) {
                    $officeStartTime = $this->getOfficeStartTime(Carbon::parse($attendance->date));
                    $graceMinutes = config('attendance.late_grace_minutes', 15);
                    $lateThreshold = $officeStartTime->copy()->addMinutes($graceMinutes);
                    $data['is_late'] = Carbon::parse($data['clock_in'])->greaterThan($lateThreshold);
                }
            }

            $attendance->update($data);

            return $attendance->fresh();
        });
    }

    /**
     * Get today's attendance for a user
     */
    public function getTodayAttendance(User $user): ?Attendance
    {
        return Attendance::forUser($user->id)->forDate(Carbon::today())->first();
    }

    /**
     * Get attendance records for a user within date range
     */
    public function getUserAttendance(User $user, ?string $startDate = null, ?string $endDate = null)
    {
        $query = Attendance::forUser($user->id)->with('user');

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get attendance records visible to a manager
     */
    public function getManagerVisibleAttendance(User $manager, ?string $startDate = null, ?string $endDate = null, ?int $subDepartmentId = null)
    {
        // Get sub-department IDs that this manager manages
        $managedSubDepartmentIds = $manager->managedSubDepartments()->pluck('sub_departments.id')->toArray();

        $query = Attendance::with(['user.department', 'user.subDepartment'])
            ->whereHas('user', function ($q) use ($managedSubDepartmentIds, $subDepartmentId) {
                if ($subDepartmentId) {
                    // Filter by specific sub-department (if manager has access to it)
                    if (in_array($subDepartmentId, $managedSubDepartmentIds)) {
                        $q->where('sub_department_id', $subDepartmentId);
                    } else {
                        // If manager doesn't have access, show nothing
                        $q->whereRaw('1 = 0');
                    }
                } else {
                    // Show all users in manager's assigned sub-departments
                    $q->whereIn('sub_department_id', $managedSubDepartmentIds);
                }
            });

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get all attendance records (admin)
     */
    public function getAllAttendance(?string $startDate = null, ?string $endDate = null, ?string $departmentId = null, ?string $subDepartmentId = null, ?string $employeeId = null)
    {
        $query = Attendance::with(['user.department', 'user.subDepartment']);

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        if ($employeeId && $employeeId !== 'all_employees') {
            $query->where('user_id', $employeeId);
        }
        
        if ($subDepartmentId && $subDepartmentId !== 'all_sub_departments') {
            $query->inSubDepartment((int) $subDepartmentId);
        }
        
        if ($departmentId && $departmentId !== 'all_departments') {
             // Only filter by department if sub-department isn't already narrowing it down (though both apply is fine)
            $query->inDepartment((int) $departmentId);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    /**
     * Get office start time for a given date
     */
    protected function getOfficeStartTime(Carbon $date): Carbon
    {
        $startTime = config('attendance.office_start_time', '09:30');
        [$hours, $minutes] = explode(':', $startTime);

        return $date->copy()->setTime((int) $hours, (int) $minutes);
    }

    /**
     * Get office end time for a given date
     */
    protected function getOfficeEndTime(Carbon $date): Carbon
    {
        $endTime = config('attendance.office_end_time', '17:30');
        [$hours, $minutes] = explode(':', $endTime);

        return $date->copy()->setTime((int) $hours, (int) $minutes);
    }

    /**
     * Get attendance statistics for dashboard
     */
    public function getAttendanceStats(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Attendance::forUser($user->id);

        if ($startDate && $endDate) {
            $query->betweenDates($startDate, $endDate);
        }

        $attendances = $query->get();

        return [
            'total_days' => $attendances->count(),
            'late_days' => $attendances->where('is_late', true)->count(),
            'total_net_hours' => round($attendances->sum('net_minutes') / 60, 2),
            'average_net_hours' => $attendances->count() > 0
                ? round($attendances->avg('net_minutes') / 60, 2)
                : 0,
        ];
    }

    /**
     * Get department attendance summary (for manager/admin)
     */
    public function getDepartmentSummary(int $departmentId, ?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();

        $users = User::inDepartment($departmentId)->get();
        $attendances = Attendance::inDepartment($departmentId)
            ->forDate($targetDate)
            ->with('user')
            ->get()
            ->keyBy('user_id');

        return $this->calculateSummary($users, $attendances, $targetDate);
    }

    /**
     * Get global attendance summary (for company-wide dashboard)
     */
    public function getGlobalAttendanceSummary(?string $date = null): array
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();

        // Exclude admins from the general "Employee" stats if desired, or include all.
        // Usually dashboards show "Employees". Assuming role!=admin or just all users.
        // Let's include all non-admin users as "employees".
        $users = User::where('role', '!=', 'admin')->get();
        
        $attendances = Attendance::whereIn('user_id', $users->pluck('id'))
            ->forDate($targetDate)
            ->with('user')
            ->get()
            ->keyBy('user_id');

        return $this->calculateSummary($users, $attendances, $targetDate);
    }

    /**
     * Helper to calculate summary stats
     */
    protected function calculateSummary($users, $attendances, Carbon $targetDate): array
    {
        $summary = [
            'total_employees' => $users->count(),
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'all_list' => $users->values()->all(),
            'present_list' => [],
            'absent_list' => [],
            'late_list' => [],
        ];

        foreach ($users as $user) {
            $attendance = $attendances->get($user->id);

            if ($attendance && $attendance->hasClockedIn()) {
                $summary['present']++;
                // Add to present list
                $summary['present_list'][] = $user;

                if ($attendance->is_late) {
                    $summary['late']++;
                     $summary['late_list'][] = $user;
                }
            } else {
                // Check if it's not a weekend for this user
                if (!$user->isWeekend($targetDate->format('l'))) {
                    $summary['absent']++;
                    $summary['absent_list'][] = $user;
                }
            }
        }

        return $summary;
    }
}
