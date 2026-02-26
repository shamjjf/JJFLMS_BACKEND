<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\LeaveBalanceController;
use App\Http\Controllers\Api\LeaveRequestController;
use App\Http\Controllers\Api\LeaveTypeController;
use App\Http\Controllers\Api\ReportController;

// ─── Public ────────────────────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Protected ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    [AuthController::class, 'user']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Employees
    Route::get('/employees',          [EmployeeController::class, 'index']);
    Route::get('/employees/{id}',     [EmployeeController::class, 'show']);
    Route::post('/employees',         [EmployeeController::class, 'store']);
    Route::put('/employees/{id}',     [EmployeeController::class, 'update']);    // EDIT
    Route::delete('/employees/{id}',  [EmployeeController::class, 'destroy']);   // DELETE
    Route::get('/departments',        [EmployeeController::class, 'departments']);

    // Leave Types
    Route::get('/leave-types',       [LeaveTypeController::class, 'index']);
    Route::post('/leave-types',      [LeaveTypeController::class, 'store']);
    Route::put('/leave-types/{id}',  [LeaveTypeController::class, 'update']);

    // Leave Requests
    Route::get('/leaves',              [LeaveRequestController::class, 'index']);
    Route::post('/leaves',             [LeaveRequestController::class, 'store']);
    Route::put('/leaves/{id}/cancel',  [LeaveRequestController::class, 'cancel']);
    Route::put('/leaves/{id}/review',  [LeaveRequestController::class, 'review']);

    // Balances
    Route::get('/balances', [LeaveBalanceController::class, 'index']);

    // Holidays
    Route::get('/holidays',         [HolidayController::class, 'index']);
    Route::post('/holidays',        [HolidayController::class, 'store']);
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy']);

    // Reports
    Route::get('/reports/employee',   [ReportController::class, 'employeeReport']);
    Route::get('/reports/department', [ReportController::class, 'departmentReport']);
    Route::get('/reports/monthly',    [ReportController::class, 'monthlyReport']);
});
