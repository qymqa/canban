<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CustomFieldController;
use App\Http\Controllers\DailyReportController;
use App\Http\Controllers\TimesheetController;

Route::get('/user', [AuthController::class, 'getUser']);

Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/user', [AuthController::class, 'getUser']);
Route::get('/auth/objects', [AuthController::class, 'getObjects']);
Route::get('/auth/users', [AuthController::class, 'getUsers']);
Route::post('/auth/validate-token', [AuthController::class, 'validateToken']);

Route::post('/boards/task-counts', [BoardController::class, 'getTaskCounts']);
Route::get('/boards/{objectId}', [BoardController::class, 'show']);
Route::get('/boards', [BoardController::class, 'showAll']);

Route::post('/tasks', [TaskController::class, 'store']);
Route::put('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
Route::put('/tasks/{task}', [TaskController::class, 'update']);
Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);

Route::get('/tasks/{task}/comments', [CommentController::class, 'index']);
Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);
Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

Route::get('/tasks/{task}/attachments', [AttachmentController::class, 'index']);
Route::post('/tasks/{task}/attachments', [AttachmentController::class, 'store']);
Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);
Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download']);

Route::get('/custom-fields', [CustomFieldController::class, 'index']);
Route::get('/custom-fields/all', [CustomFieldController::class, 'all']);
Route::post('/custom-fields', [CustomFieldController::class, 'store']);
Route::get('/custom-fields/{customField}', [CustomFieldController::class, 'show']);
Route::put('/custom-fields/{customField}', [CustomFieldController::class, 'update']);
Route::delete('/custom-fields/{customField}', [CustomFieldController::class, 'destroy']);
Route::patch('/custom-fields/{customField}/reactivate', [CustomFieldController::class, 'reactivate']);

// Маршруты для ежедневных отчетов
Route::get('/daily-reports/monthly', [DailyReportController::class, 'getMonthlyReports']);
Route::get('/daily-reports/tasks', [DailyReportController::class, 'getAvailableTasks']);
Route::post('/daily-reports', [DailyReportController::class, 'store']);
Route::get('/daily-reports/{report}', [DailyReportController::class, 'show']);
Route::put('/daily-reports/{report}', [DailyReportController::class, 'update']);
Route::delete('/daily-reports/{report}', [DailyReportController::class, 'destroy']);

// Маршруты для табеля рабочего времени
Route::get('/timesheet/monthly', [TimesheetController::class, 'getMonthlyTimesheet']);
Route::put('/timesheet/update', [TimesheetController::class, 'updateTimesheet']);
Route::get('/timesheet/stats', [TimesheetController::class, 'getTimesheetStats']);
Route::get('/timesheet/export/csv', [TimesheetController::class, 'exportExcel']); // CSV export (method name kept for compatibility)
Route::get('/timesheet/export/excel', [TimesheetController::class, 'exportExcel']); // Legacy Excel route - returns CSV
Route::get('/timesheet/export/pdf', [TimesheetController::class, 'exportPdf']);
