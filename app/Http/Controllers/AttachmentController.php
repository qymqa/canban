<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attachment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    private $apiBaseUrl = 'https://api.pto-app.ru/api/v1';

    public function store(Request $request, Task $task): JsonResponse
    {
        $token = $request->header('Authorization');
        
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // Max 10MB
            'uploaded_by_user_id' => 'required|string',
        ]);

        try {
            $file = $request->file('file');
            
            // Загружаем файл во внешний API
            $response = Http::withHeaders([
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'ru,en;q=0.9',
                'Authorization' => $token,
                'Cache-Control' => 'no-cache',
                'Origin' => 'https://app.staging.pto-app.ru',
                'Pragma' => 'no-cache',
                'Referer' => 'https://app.staging.pto-app.ru/',
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36',
                'X-Requested-With' => 'XMLHttpRequest',
            ])->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
              ->post($this->apiBaseUrl . '/upload/file');

            if ($response->successful()) {
                $uploadResponse = $response->json();
                
                // Извлекаем original_url из ответа API
                $originalUrl = $uploadResponse['original_url'] ?? 
                              $uploadResponse['data']['original_url'] ?? 
                              $uploadResponse['url'] ?? 
                              $uploadResponse['data']['url'] ?? 
                              $uploadResponse['file_url'] ?? 
                              $uploadResponse['data']['file_url'] ?? '';
                
                // Сохраняем информацию о файле в базе данных
                $attachment = $task->attachments()->create([
                    'file_name' => $file->getClientOriginalName(),
                    'original_url' => $originalUrl,
                    'file_size' => $file->getSize(),
                    'file_type' => $file->getMimeType(),
                    'uploaded_by_user_id' => $request->uploaded_by_user_id,
                ]);

                return response()->json($attachment, 201);
            }

            return response()->json([
                'message' => 'Ошибка загрузки файла', 
                'status' => $response->status(),
                'error' => $response->body()
            ], 422);

        } catch (\Exception $e) {
            Log::error('File upload error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    public function index(Task $task): JsonResponse
    {
        $attachments = $task->attachments()->get();
        return response()->json($attachments);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $attachment->delete();
        return response()->json(['message' => 'Файл удален']);
    }

    public function download(Request $request, Attachment $attachment): JsonResponse
    {
        try {
            return response()->json([
                'url' => $attachment->original_url,
                'file_name' => $attachment->file_name,
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера'], 500);
        }
    }
}
