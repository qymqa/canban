<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('MAIN_API_URL', 'https://api.pto-app.ru/api/v1');
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])->post($this->apiBaseUrl . '/auth/login', [
                'email' => $request->email,
                'password' => $request->password,
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['message' => 'Неверные учетные данные'], 401);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка подключения к серверу'], 500);
        }
    }

    public function getUser(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $token,
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get($this->apiBaseUrl . '/user');

            if ($response->successful()) {
                $userData = $response->json();
                return response()->json($userData['data'] ?? $userData);
            }

            return response()->json(['message' => 'Неавторизован'], 401);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера'], 500);
        }
    }

    public function getObjects(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');
        $perPage = $request->query('perPage', 9);
        $page = $request->query('page', 1);
        
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $token,
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get($this->apiBaseUrl . '/objects', [
                'perPage' => $perPage,
                'page' => $page,
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['message' => 'Неавторизован'], 401);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера'], 500);
        }
    }

    public function getUsers(Request $request): JsonResponse
    {
        $token = $request->header('Authorization');
        $perPage = $request->query('perPage', 100);
        $page = $request->query('page', 1);
        
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => $token,
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get($this->apiBaseUrl . '/users', [
                'perPage' => $perPage,
                'page' => $page,
            ]);

            if ($response->successful()) {
                return response()->json($response->json());
            }

            return response()->json(['message' => 'Неавторизован'], 401);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера'], 500);
        }
    }

    public function validateToken(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $token = $request->token;

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$token}",
                'X-Requested-With' => 'XMLHttpRequest',
            ])->get($this->apiBaseUrl . '/user');

            if ($response->successful()) {
                $userData = $response->json();
                return response()->json([
                    'valid' => true,
                    'user' => $userData['data'] ?? $userData
                ]);
            }

            return response()->json(['valid' => false, 'message' => 'Неверный токен'], 401);

        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => 'Ошибка проверки токена'], 500);
        }
    }
}
