<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [AttendanceController::class, 'dashboard'])->name('dashboard');

    // Attendance routes
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'userDashboard'])->name('user');
        Route::get('/manager', [AttendanceController::class, 'managerDashboard'])->name('manager');
        Route::get('/admin', [AttendanceController::class, 'adminDashboard'])->name('admin');
        
        Route::post('/clock-in', [AttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [AttendanceController::class, 'clockOut'])->name('clock-out');
        
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
        Route::patch('/{attendance}/override', [AttendanceController::class, 'override'])->name('override');
    });

    // Employee Management routes (Admin only)
    Route::resource('employees', \App\Http\Controllers\EmployeeController::class)->except(['show']);
});

require __DIR__.'/settings.php';
