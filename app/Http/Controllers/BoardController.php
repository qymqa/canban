<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class BoardController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('MAIN_API_URL', 'https://api.pto-app.ru/api/v1');
    }
    public function getTaskCounts(Request $request): JsonResponse
    {
        $objectIds = $request->input('object_ids', []);
        
        $counts = [];
        foreach ($objectIds as $objectId) {
            $board = Board::where('object_id', $objectId)->first();
            if ($board) {
                $counts[$objectId] = $board->tasks()->count();
            } else {
                $counts[$objectId] = 0;
            }
        }
        
        return response()->json($counts);
    }

    public function show(string $objectId): JsonResponse
    {
        // Найти или создать доску для данного объекта
        $board = Board::firstOrCreate(
            ['object_id' => $objectId],
            ['name' => "Доска для объекта {$objectId}"]
        );

        // Получить все задачи доски с комментариями, вложениями и дополнительными полями
        $allTasks = $board->tasks()
            ->with([
                'comments', 
                'attachments',
                'customFieldValues.customField' => function($query) {
                    $query->active()->ordered();
                }
            ])
            ->orderBy('position')
            ->get();

        // Добавляем дополнительные поля к каждой задаче для удобства фронтенда
        $allTasks->each(function ($task) {
            $customFields = [];
            foreach ($task->customFieldValues as $value) {
                $customFields[$value->custom_field_id] = $value->value;
            }
            $task->custom_fields = $customFields;
        });

        // Группировать задачи по статусу
        $tasks = [
            'waiting' => $allTasks->where('status', 'waiting')->values(),
            'in_progress' => $allTasks->where('status', 'in_progress')->values(),
            'completed' => $allTasks->where('status', 'completed')->values(),
            'blocked' => $allTasks->where('status', 'blocked')->values(),
        ];

        return response()->json([
            'board' => $board,
            'tasks' => $tasks,
        ]);
    }

    public function showAll(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Получаем доступные объекты для пользователя через внешний API
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $token,
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get($this->apiBaseUrl . '/objects', [
                'perPage' => 1000, // Получаем все доступные объекты
                'page' => 1,
            ]);

            if (!$response->successful()) {
                return response()->json(['message' => 'Ошибка получения доступных объектов'], 500);
            }

            $objectsData = $response->json();
            $accessibleObjects = $objectsData['data'] ?? [];
            $accessibleObjectIds = collect($accessibleObjects)->pluck('id')->toArray();

            // Получить только те доски, которые принадлежат доступным объектам
            $accessibleBoards = Board::whereIn('object_id', $accessibleObjectIds)->pluck('id')->toArray();

            // Получить задачи только из доступных досок
            $allTasks = Task::whereIn('board_id', $accessibleBoards)
                ->with([
                    'comments', 
                    'attachments',
                    'customFieldValues.customField' => function($query) {
                        $query->active()->ordered();
                    }
                ])
                ->orderBy('board_id')
                ->orderBy('position')
                ->get();

            // Добавляем дополнительные поля к каждой задаче для удобства фронтенда
            $allTasks->each(function ($task) {
                $customFields = [];
                foreach ($task->customFieldValues as $value) {
                    $customFields[$value->custom_field_id] = $value->value;
                }
                $task->custom_fields = $customFields;
            });

            // Группировать задачи по статусу
            $tasks = [
                'waiting' => $allTasks->where('status', 'waiting')->values(),
                'in_progress' => $allTasks->where('status', 'in_progress')->values(),
                'completed' => $allTasks->where('status', 'completed')->values(),
                'blocked' => $allTasks->where('status', 'blocked')->values(),
            ];

            return response()->json([
                'board' => (object)['id' => 'all', 'name' => 'Все объекты', 'object_id' => 'all'],
                'tasks' => $tasks,
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }
}
