<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Board;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class BoardController extends Controller
{
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
}
