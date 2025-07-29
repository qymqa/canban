<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Http;

abstract class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('MAIN_API_URL', 'https://api.pto-app.ru/api/v1');
    }

    /**
     * Получить данные текущего пользователя
     */
    protected function getCurrentUser($token)
    {
        if (!$token) {
            return null;
        }

        try {
            $userResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/user');

            if (!$userResponse->successful()) {
                return null;
            }

            return $userResponse->json()['data'] ?? $userResponse->json();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Проверить является ли пользователь администратором
     */
    protected function isAdmin($user): bool
    {
        return ($user['role'] ?? '') === 'admin' || ($user['is_admin'] ?? false);
    }

    /**
     * Проверить является ли пользователь инспектором
     */
    protected function isInspector($user): bool
    {
        return ($user['role'] ?? '') === 'inspector';
    }

    /**
     * Проверить является ли пользователь обычным пользователем
     */
    protected function isUser($user): bool
    {
        return ($user['role'] ?? '') === 'user' || !in_array($user['role'] ?? '', ['admin', 'inspector']);
    }

    /**
     * Проверить может ли пользователь редактировать задачу
     */
    protected function canEditTask($user, $task): bool
    {
        // Администратор может редактировать любые задачи
        if ($this->isAdmin($user)) {
            return true;
        }

        // Инспектор может редактировать только свои задачи
        if ($this->isInspector($user)) {
            return $task->created_by_user_id === $user['id'];
        }

        // Обычный пользователь может редактировать только свои задачи
        return $task->created_by_user_id === $user['id'] || 
               $task->responsible_user_id === $user['id'];
    }

    /**
     * Проверить может ли пользователь создавать задачи
     */
    protected function canCreateTask($user): bool
    {
        // Все авторизованные пользователи могут создавать задачи
        return true;
    }

    /**
     * Проверить может ли пользователь удалять задачу
     */
    protected function canDeleteTask($user, $task): bool
    {
        // Только администратор может удалять задачи
        return $this->isAdmin($user);
    }

    /**
     * Проверить может ли пользователь создавать отчеты
     */
    protected function canCreateReport($user): bool
    {
        // Инспектор не может создавать отчеты
        if ($this->isInspector($user)) {
            return false;
        }
        
        return true;
    }

    /**
     * Проверить может ли пользователь редактировать табель
     */
    protected function canEditTimesheet($user): bool
    {
        // Только администратор может редактировать табель
        return $this->isAdmin($user);
    }
}
