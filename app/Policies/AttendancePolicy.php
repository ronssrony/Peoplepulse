<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Determine whether the user can view any attendances.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view (scope determines what they see)
    }

    /**
     * Determine whether the user can view the attendance.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        // Admin can view all
        if ($user->isAdmin()) {
            return true;
        }

        // Manager can view their department/sub-department
        if ($user->isManager()) {
            $attendanceUser = $attendance->user;
            
            // Same department
            if ($attendanceUser->department === $user->department) {
                return true;
            }
            
            // Or manager's assigned sub-department
            if ($user->sub_department && $attendanceUser->sub_department === $user->sub_department) {
                return true;
            }
        }

        // Users can only view their own
        return $attendance->user_id === $user->id;
    }

    /**
     * Determine whether the user can clock in.
     */
    public function clockIn(User $user): bool
    {
        return true; // All authenticated users can clock in
    }

    /**
     * Determine whether the user can clock out.
     */
    public function clockOut(User $user, Attendance $attendance): bool
    {
        // Can only clock out your own attendance
        return $attendance->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the attendance (admin override).
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // Only admin can override/update attendance records
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the attendance.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        // Only admin can delete attendance records
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view audit logs.
     */
    public function viewAuditLogs(User $user, Attendance $attendance): bool
    {
        // Admin can view all audit logs
        if ($user->isAdmin()) {
            return true;
        }

        // Users can view audit logs for their own attendance
        return $attendance->user_id === $user->id;
    }
}
