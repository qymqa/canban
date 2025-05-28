<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\CustomField;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'board_id' => 'required|exists:boards,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:waiting,in_progress,completed,blocked',
            'priority' => 'required|in:low,medium,high',
            'deadline' => 'nullable|date',
            'created_by_user_id' => 'required|string',
            'assigned_by_user_id' => 'nullable|string',
            'responsible_user_id' => 'nullable|string',
            'custom_fields' => 'nullable|array',
        ]);

        // Валидируем дополнительные поля
        $this->validateCustomFields($request->custom_fields ?? []);

        $maxPosition = Task::where('board_id', $request->board_id)
            ->where('status', $request->status)
            ->max('position') ?? 0;

        $task = Task::create([
            'board_id' => $request->board_id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status,
            'priority' => $request->priority,
            'deadline' => $request->deadline,
            'created_by_user_id' => $request->created_by_user_id,
            'assigned_by_user_id' => $request->assigned_by_user_id,
            'responsible_user_id' => $request->responsible_user_id,
            'position' => $maxPosition + 1,
        ]);

        // Сохраняем значения дополнительных полей
        $this->saveCustomFieldValues($task, $request->custom_fields ?? []);

        return response()->json($task, 201);
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:waiting,in_progress,completed,blocked',
            'position' => 'required|integer|min:0',
        ]);

        Task::where('board_id', $task->board_id)
            ->where('status', $request->status)
            ->where('position', '>=', $request->position)
            ->increment('position');

        $task->update([
            'status' => $request->status,
            'position' => $request->position,
        ]);

        return response()->json($task);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'required|in:low,medium,high',
            'deadline' => 'nullable|date',
            'assigned_by_user_id' => 'nullable|string',
            'responsible_user_id' => 'nullable|string',
            'custom_fields' => 'nullable|array',
        ]);

        // Валидируем дополнительные поля
        $this->validateCustomFields($request->custom_fields ?? []);

        $task->update($request->only(['title', 'description', 'priority', 'deadline', 'assigned_by_user_id', 'responsible_user_id']));

        // Обновляем значения дополнительных полей
        $this->saveCustomFieldValues($task, $request->custom_fields ?? []);

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();
        return response()->json(['message' => 'Задача удалена']);
    }

    private function validateCustomFields(array $customFields): void
    {
        $activeFields = CustomField::active()->get()->keyBy('id');
        
        foreach ($customFields as $fieldId => $value) {
            $field = $activeFields->get($fieldId);
            
            if (!$field) {
                continue; // Пропускаем неактивные поля
            }
            
            // Проверяем обязательность поля
            if ($field->is_required && (is_null($value) || trim($value) === '')) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "custom_fields.{$fieldId}" => "Поле '{$field->label}' обязательно для заполнения"
                ]);
            }
            
            // Валидируем тип поля
            if (!is_null($value) && trim($value) !== '') {
                if ($field->type === 'date') {
                    // Проверяем формат даты
                    if (!$this->isValidDate($value)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "custom_fields.{$fieldId}" => "Поле '{$field->label}' должно содержать корректную дату"
                        ]);
                    }
                }
            }
        }
        
        // Проверяем, что все обязательные поля заполнены
        $requiredFields = $activeFields->where('is_required', true);
        foreach ($requiredFields as $field) {
            if (!isset($customFields[$field->id]) || trim($customFields[$field->id]) === '') {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "custom_fields.{$field->id}" => "Поле '{$field->label}' обязательно для заполнения"
                ]);
            }
        }
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    private function saveCustomFieldValues(Task $task, array $customFields): void
    {
        // Удаляем старые значения
        $task->customFieldValues()->delete();
        
        // Сохраняем новые значения
        foreach ($customFields as $fieldId => $value) {
            if (!is_null($value) && trim($value) !== '') {
                $task->customFieldValues()->create([
                    'custom_field_id' => $fieldId,
                    'value' => $value,
                ]);
            }
        }
    }
}
