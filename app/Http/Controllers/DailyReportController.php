<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyReport;
use App\Models\Timesheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DailyReportController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Получить данные объекта из API
     */
    private function getObjectData($objectId, $token)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get($this->apiBaseUrl . '/objects/' . $objectId);

            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }
        } catch (\Exception $e) {
            \Log::error("Error fetching object data: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Получить отчеты за месяц (календарный вид)
     */
    public function getMonthlyReports(Request $request): JsonResponse
    {
        $request->validate([
            'object_id' => 'required|string',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Получаем данные текущего пользователя
            $userResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/user');

            if (!$userResponse->successful()) {
                return response()->json(['message' => 'Ошибка авторизации'], 401);
            }

            $currentUser = $userResponse->json()['data'] ?? $userResponse->json();
            $isAdmin = ($currentUser['role'] ?? '') === 'admin' || ($currentUser['is_admin'] ?? false);
            $isInspector = ($currentUser['role'] ?? '') === 'inspector';

            // Получаем отчеты
            $startDate = Carbon::create($request->year, $request->month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $query = DailyReport::where('object_id', $request->object_id)
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Администратор и инспектор видят все отчеты, обычный пользователь - только свои
            if (!$isAdmin && !$isInspector) {
                $query->where('user_id', $currentUser['id']);
            }

            $reports = $query->orderBy('created_at', 'desc')->get();

            // Группируем отчеты по дням
            $reportsByDate = $reports->groupBy(function ($report) {
                return $report->created_at->format('Y-m-d');
            });

            return response()->json([
                'reports' => $reportsByDate,
                'is_admin' => $isAdmin,
                'current_user_id' => $currentUser['id'],
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Создать новый отчет
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'object_id' => 'required|string',
            'report_text' => 'required|string',
            'attached_tasks' => 'nullable|array',
            'report_date' => 'nullable|date',
        ]);

        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Получаем данные пользователя
            $userResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/user');

            if (!$userResponse->successful()) {
                return response()->json(['message' => 'Ошибка авторизации'], 401);
            }

            $user = $userResponse->json()['data'] ?? $userResponse->json();

            // Проверяем права на создание отчетов - инспектор не может создавать отчеты
            if (($user['role'] ?? '') === 'inspector') {
                return response()->json(['message' => 'Инспектор не может создавать отчеты'], 403);
            }

            // Получаем данные объекта для получения часов работы
            $objectData = null;
            $workHoursPerDay = env('WORK_HOURS_PER_DAY', 8); // дефолтное значение
            
            if ($request->object_id !== 'all') {
                $objectData = $this->getObjectData($request->object_id, $token);
                if ($objectData && isset($objectData['work_hours_per_day'])) {
                    $workHoursPerDay = $objectData['work_hours_per_day'];
                }
            }

            // Определяем дату отчета (если не передана, используем текущую)
            $reportDate = $request->report_date ? Carbon::parse($request->report_date) : now();
            
            // Создаем отчет
            $report = DailyReport::create([
                'object_id' => $request->object_id,
                'user_id' => $user['id'],
                'user_name' => $user['name'],
                'user_surname' => $user['family_name'] ?? $user['surname'] ?? '',
                'user_position' => $user['position'] ?? null,
                'report_text' => $request->report_text,
                'attached_tasks' => $request->attached_tasks ?? [],
                'created_at' => $reportDate,
                'updated_at' => $reportDate,
            ]);

            // Обновляем табель - отмечаем что отчет сдан
            $timesheet = Timesheet::where('object_id', $request->object_id)
                ->where('user_id', $user['id'])
                ->whereDate('date', $reportDate->format('Y-m-d'))
                ->first();
                
            if ($timesheet) {
                $timesheet->update([
                    'hours_worked' => $workHoursPerDay,
                    'has_report' => true,
                ]);
            } else {
                $timesheet = Timesheet::create([
                    'object_id' => $request->object_id,
                    'user_id' => $user['id'],
                    'date' => $reportDate->format('Y-m-d'),
                    'user_name' => $user['name'],
                    'user_surname' => $user['family_name'] ?? $user['surname'] ?? '',
                    'hours_worked' => $workHoursPerDay,
                    'has_report' => true,
                ]);
            }

            return response()->json($report, 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка создания отчета: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Показать конкретный отчет
     */
    public function show(Request $request, DailyReport $report): JsonResponse
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Получаем данные пользователя для проверки прав
            $userResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/user');

            if (!$userResponse->successful()) {
                return response()->json(['message' => 'Ошибка авторизации'], 401);
            }

            $currentUser = $userResponse->json()['data'] ?? $userResponse->json();
            $isAdmin = ($currentUser['role'] ?? '') === 'admin' || ($currentUser['is_admin'] ?? false);
            $isInspector = ($currentUser['role'] ?? '') === 'inspector';

            // Проверяем права доступа - админ и инспектор видят все отчеты, пользователь - только свои
            if (!$isAdmin && !$isInspector && $report->user_id !== $currentUser['id']) {
                return response()->json(['message' => 'Нет доступа к этому отчету'], 403);
            }

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Обновить отчет
     */
    public function update(Request $request, DailyReport $report): JsonResponse
    {
        $request->validate([
            'report_text' => 'required|string',
            'attached_tasks' => 'nullable|array',
        ]);

        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Получаем данные пользователя для проверки прав
            $userResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/user');

            if (!$userResponse->successful()) {
                return response()->json(['message' => 'Ошибка авторизации'], 401);
            }

            $currentUser = $userResponse->json()['data'] ?? $userResponse->json();

            // Проверяем права доступа - только автор может редактировать
            if ($report->user_id !== $currentUser['id']) {
                return response()->json(['message' => 'Можно редактировать только свои отчеты'], 403);
            }

            // Обновляем отчет
            $report->update([
                'report_text' => $request->report_text,
                'attached_tasks' => $request->attached_tasks ?? [],
            ]);

            return response()->json($report);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка обновления отчета: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Удалить отчет
     */
    public function destroy(Request $request, DailyReport $report): JsonResponse
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Получаем данные пользователя для проверки прав
            $userResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/user');

            if (!$userResponse->successful()) {
                return response()->json(['message' => 'Ошибка авторизации'], 401);
            }

            $currentUser = $userResponse->json()['data'] ?? $userResponse->json();

            // Проверяем права доступа - только автор может удалять
            if ($report->user_id !== $currentUser['id']) {
                return response()->json(['message' => 'Можно удалять только свои отчеты'], 403);
            }

            // Удаляем отчет
            $report->delete();

            // Обновляем табель - убираем отметку об отчете
            $timesheet = Timesheet::where('object_id', $report->object_id)
                ->where('user_id', $report->user_id)
                ->where('date', $report->created_at->format('Y-m-d'))
                ->first();

            if ($timesheet) {
                $timesheet->updateReportStatus(false, 0);
            }

            return response()->json(['message' => 'Отчет удален']);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка удаления отчета: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Получить доступные задачи для прикрепления к отчету
     */
    public function getAvailableTasks(Request $request): JsonResponse
    {
        $request->validate([
            'object_id' => 'required|string',
        ]);

        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Если object_id = 'all', получаем задачи из всех досок
            if ($request->object_id === 'all') {
                // Проверяем права доступа через внешний API
                $response = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => $token,
                    'X-Requested-With' => 'XMLHttpRequest',
                ])->get($this->apiBaseUrl . '/objects', [
                    'perPage' => 1000,
                    'page' => 1,
                ]);

                if (!$response->successful()) {
                    return response()->json(['tasks' => []]);
                }

                $objectsData = $response->json();
                $accessibleObjects = $objectsData['data'] ?? [];
                $accessibleObjectIds = collect($accessibleObjects)->pluck('id')->toArray();

                // Получаем задачи из всех доступных объектов
                $boards = \App\Models\Board::whereIn('object_id', $accessibleObjectIds)->get();
                
                $tasks = [];
                foreach ($boards as $board) {
                    $boardTasks = $board->tasks()->get();
                    foreach ($boardTasks as $task) {
                        $tasks[] = [
                            'id' => $task->id,
                            'title' => $task->title,
                            'status' => $task->status,
                            'priority' => $task->priority,
                            'board_title' => $board->name,
                            'object_id' => $board->object_id,
                        ];
                    }
                }
            } else {
                // Получаем задачи конкретного объекта
                $board = \App\Models\Board::where('object_id', $request->object_id)->first();
                
                if (!$board) {
                    return response()->json(['tasks' => []]);
                }

                $boardTasks = $board->tasks()->get();
                $tasks = [];
                
                foreach ($boardTasks as $task) {
                    $tasks[] = [
                        'id' => $task->id,
                        'title' => $task->title,
                        'status' => $task->status,
                        'priority' => $task->priority,
                        'board_title' => $board->name,
                        'object_id' => $board->object_id,
                    ];
                }
            }

            return response()->json($tasks);

        } catch (\Exception $e) {
            return response()->json([]);
        }
    }
}
