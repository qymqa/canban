<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Timesheet;
use App\Models\DailyReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Exports\TimesheetExport;
use Maatwebsite\Excel\Facades\Excel;
use Dompdf\Dompdf;
use Dompdf\Options;

class TimesheetController extends Controller
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('MAIN_API_URL', 'https://api.pto-app.ru/api/v1');
    }

    /**
     * Получить табель за месяц
     */
    public function getMonthlyTimesheet(Request $request): JsonResponse
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
            // Получаем данные табеля за месяц
            $timesheetData = Timesheet::getMonthlyTimesheet(
                $request->object_id,
                $request->year,
                $request->month
            );

            // Если нет данных в табеле, возвращаем пустой ответ
            if ($timesheetData->isEmpty()) {
                return response()->json([
                    'timesheet' => [],
                    'calendar' => [],
                    'work_settings' => [
                        'work_hours_per_day' => env('WORK_HOURS_PER_DAY', 8),
                        'work_start_time' => env('WORK_START_TIME', '09:00'),
                        'timezone' => env('WORK_TIMEZONE', 'Europe/Moscow'),
                        'weekend_days' => explode(',', env('WEEKEND_DAYS', '6,0')),
                        'service_start_date' => env('SERVICE_START_DATE', '2025-01-01'),
                    ],
                ]);
            }

            // Группируем по пользователям
            $timesheetByUser = $timesheetData->groupBy('user_id');

            // Получаем календарь месяца
            $startDate = Carbon::create($request->year, $request->month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            $calendar = $this->generateCalendar($startDate, $endDate);

            // Получаем уникальных пользователей из табеля
            $objectUsers = $timesheetData->groupBy('user_id')->map(function ($userRecords) {
                $first = $userRecords->first();
                return [
                    'id' => $first->user_id,
                    'name' => $first->user_name,
                    'family_name' => $first->user_surname,
                    'surname' => $first->user_surname,
                    'position' => 'Пользователь',
                ];
            })->values()->toArray();

            // Формируем данные для каждого пользователя
            $result = [];
            foreach ($objectUsers as $user) {
                $userId = $user['id'];
                $userTimesheets = $timesheetByUser->get($userId, collect());
                
                // Создаем массив дней с данными
                $days = [];
                foreach ($calendar as $date) {
                    $timesheet = $userTimesheets->filter(function($record) use ($date) { return $record->date->format('Y-m-d') === $date->format('Y-m-d'); })->first();
                    
                    $days[] = [
                        'date' => $date->format('Y-m-d'),
                        'day_of_week' => $date->dayOfWeek,
                        'is_weekend' => $this->isWeekend($date),
                        'is_holiday' => $this->isHoliday($date),
                        'hours_worked' => $timesheet ? $timesheet->hours_worked : 0,
                        'has_report' => $timesheet ? $timesheet->has_report : false,
                    ];
                }

                // Статистика по пользователю
                $stats = Timesheet::getUserMonthlyStats(
                    $request->object_id,
                    $userId,
                    $request->year,
                    $request->month
                );

                $result[] = [
                    'user' => $user,
                    'days' => $days,
                    'stats' => $stats,
                ];
            }


            return response()->json([
                'timesheet' => $result,
                'calendar' => $calendar->map(function ($date) {
                    return [
                        'date' => $date->format('Y-m-d'),
                        'day' => $date->day,
                        'day_of_week' => $date->dayOfWeek,
                        'is_weekend' => $this->isWeekend($date),
                        'is_holiday' => $this->isHoliday($date),
                    ];
                })->toArray(),
                'work_settings' => [
                    'work_hours_per_day' => env('WORK_HOURS_PER_DAY', 8),
                    'work_start_time' => env('WORK_START_TIME', '09:00'),
                    'timezone' => env('WORK_TIMEZONE', 'Europe/Moscow'),
                    'weekend_days' => explode(',', env('WEEKEND_DAYS', '6,0')),
                    'service_start_date' => env('SERVICE_START_DATE', '2025-01-01'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error("Timesheet error", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Обновить табель
     */
    public function updateTimesheet(Request $request): JsonResponse
    {
        $request->validate([
            'object_id' => 'required|string',
            'user_id' => 'required|string',
            'date' => 'required|date',
            'hours_worked' => 'required|numeric|min:0|max:24',
        ]);

        $token = $request->header('Authorization');
        if (!$token) {
            return response()->json(['message' => 'Токен не предоставлен'], 401);
        }

        try {
            // Проверяем права администратора
            $userResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/user');

            if (!$userResponse->successful()) {
                return response()->json(['message' => 'Ошибка авторизации'], 401);
            }

            $currentUser = $userResponse->json()['data'] ?? $userResponse->json();
            $isAdmin = ($currentUser['role'] ?? '') === 'admin' || ($currentUser['is_admin'] ?? false);

            if (!$isAdmin) {
                return response()->json(['message' => 'Только администратор может редактировать табель'], 403);
            }

            // Получаем информацию о пользователе
            $userInfo = collect($this->getAllUsers($token))->firstWhere('id', $request->user_id);
            if (!$userInfo) {
                return response()->json(['message' => 'Пользователь не найден'], 404);
            }

            // Находим или создаем запись в табеле
            $timesheet = Timesheet::getOrCreate(
                $request->object_id,
                $request->user_id,
                $userInfo['name'],
                $userInfo['family_name'] ?? $userInfo['surname'] ?? '',
                Carbon::parse($request->date)
            );

            // Обновляем часы работы
            $timesheet->hours_worked = $request->hours_worked;
            $timesheet->save();

            return response()->json(['message' => 'Табель обновлен', 'timesheet' => $timesheet]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка обновления табеля: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Получить статистику табеля
     */
    public function getTimesheetStats(Request $request): JsonResponse
    {
        $request->validate([
            'object_id' => 'required|string',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $stats = Timesheet::getMonthlyStats(
                $request->object_id,
                $request->year,
                $request->month
            );

            return response()->json($stats);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка получения статистики: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Получить всех пользователей из API
     */
    private function getAllUsers($token)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/users', [
                'perPage' => 1000,
                'page' => 1,
            ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки
        }

        return [];
    }

    /**
     * Генерировать календарь для месяца
     */
    private function generateCalendar($startDate, $endDate)
    {
        $calendar = collect();
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $calendar->push($current->copy());
            $current->addDay();
        }

        return $calendar;
    }

    /**
     * Проверить, является ли день выходным
     */
    private function isWeekend($date)
    {
        $weekendDays = explode(',', env('WEEKEND_DAYS', '6,0'));
        return in_array($date->dayOfWeek, array_map('intval', $weekendDays));
    }

    /**
     * Проверить, является ли день праздничным
     */
    private function isHoliday($date)
    {
        // Здесь можно добавить логику для определения праздничных дней
        // Пока возвращаем false
        return false;
    }

    /**
     * Получить количество рабочих дней в месяце
     */
    private function getWorkDaysInMonth($year, $month)
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();
        $workDays = 0;

        $current = $startDate->copy();
        while ($current <= $endDate) {
            if (!$this->isWeekend($current) && !$this->isHoliday($current)) {
                $workDays++;
            }
            $current->addDay();
        }

        return $workDays;
    }

    /**
     * Экспорт табеля в Excel
     */
    public function exportExcel(Request $request)
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
            // Получаем данные табеля
            $timesheetResponse = $this->getTimesheetData($request, $token);
            if (!$timesheetResponse['success']) {
                return response()->json(['message' => $timesheetResponse['message']], 500);
            }

            $timesheet = $timesheetResponse['timesheet'];
            $calendar = $timesheetResponse['calendar'];
            $workSettings = $timesheetResponse['work_settings'];

            // Получаем название объекта
            $objectName = $this->getObjectName($request->object_id, $token);

            // Создаем экспорт
            $export = new TimesheetExport(
                $timesheet,
                $calendar,
                $workSettings,
                $objectName,
                $request->year,
                $request->month
            );

            $monthName = $this->getMonthName($request->month);
            $fileName = "timesheet_{$objectName}_{$monthName}_{$request->year}.xlsx";

            return Excel::download($export, $fileName);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка экспорта: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Экспорт табеля в PDF
     */
    public function exportPdf(Request $request)
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
            // Получаем данные табеля
            $timesheetResponse = $this->getTimesheetData($request, $token);
            if (!$timesheetResponse['success']) {
                return response()->json(['message' => $timesheetResponse['message']], 500);
            }

            $timesheet = $timesheetResponse['timesheet'];
            $calendar = $timesheetResponse['calendar'];
            $workSettings = $timesheetResponse['work_settings'];

            // Получаем название объекта
            $objectName = $this->getObjectName($request->object_id, $token);

            // Генерируем HTML для PDF
            $html = $this->generateTimesheetHtml(
                $timesheet,
                $calendar,
                $workSettings,
                $objectName,
                $request->year,
                $request->month
            );

            // Создаем PDF
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            $monthName = $this->getMonthName($request->month);
            $fileName = "timesheet_{$objectName}_{$monthName}_{$request->year}.pdf";

            return response($dompdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка экспорта: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Получить данные табеля (общий метод для экспорта)
     */
    private function getTimesheetData($request, $token)
    {
        try {
            // Получаем всех пользователей из основного API
            $usersResponse = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/users', [
                'perPage' => 1000,
                'page' => 1,
            ]);

            if (!$usersResponse->successful()) {
                return ['success' => false, 'message' => 'Ошибка получения пользователей'];
            }

            $allUsers = $usersResponse->json()['data'] ?? [];
            
            // Получаем пользователей, которые уже есть в табеле для этого объекта
            $existingUserIds = Timesheet::where('object_id', $request->object_id)
                ->distinct()
                ->pluck('user_id')
                ->toArray();
            
            // Объединяем всех пользователей и тех, кто уже есть в табеле
            $objectUsers = collect($allUsers)->filter(function ($user) use ($existingUserIds) {
                return in_array($user['id'], $existingUserIds);
            })->values()->toArray();
            
            // Если нет пользователей в табеле, используем всех доступных пользователей
            if (empty($objectUsers)) {
                $objectUsers = $allUsers;
            }

            // Получаем данные табеля за месяц
            $timesheetData = Timesheet::getMonthlyTimesheet(
                $request->object_id,
                $request->year,
                $request->month
            );

            // Группируем по пользователям
            $timesheetByUser = $timesheetData->groupBy('user_id');

            // Получаем календарь месяца
            $startDate = Carbon::create($request->year, $request->month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            $calendar = $this->generateCalendar($startDate, $endDate);

            // Формируем данные для каждого пользователя
            $result = [];
            foreach ($objectUsers as $user) {
                $userId = $user['id'];
                $userTimesheets = $timesheetByUser->get($userId, collect());
                
                // Создаем массив дней с данными
                $days = [];
                foreach ($calendar as $date) {
                    $timesheet = $userTimesheets->filter(function($record) use ($date) { return $record->date->format('Y-m-d') === $date->format('Y-m-d'); })->first();
                    
                    $days[] = [
                        'date' => $date->format('Y-m-d'),
                        'day_of_week' => $date->dayOfWeek,
                        'is_weekend' => $this->isWeekend($date),
                        'is_holiday' => $this->isHoliday($date),
                        'hours_worked' => $timesheet ? $timesheet->hours_worked : 0,
                        'has_report' => $timesheet ? $timesheet->has_report : false,
                    ];
                }

                // Статистика по пользователю
                $stats = Timesheet::getUserMonthlyStats(
                    $request->object_id,
                    $userId,
                    $request->year,
                    $request->month
                );

                $result[] = [
                    'user' => $user,
                    'days' => $days,
                    'stats' => $stats,
                ];
            }

            return [
                'success' => true,
                'timesheet' => $result,
                'calendar' => $calendar->map(function ($date) {
                    return [
                        'date' => $date->format('Y-m-d'),
                        'day' => $date->day,
                        'day_of_week' => $date->dayOfWeek,
                        'is_weekend' => $this->isWeekend($date),
                        'is_holiday' => $this->isHoliday($date),
                    ];
                })->toArray(),
                'work_settings' => [
                    'work_hours_per_day' => env('WORK_HOURS_PER_DAY', 8),
                    'work_start_time' => env('WORK_START_TIME', '09:00'),
                    'timezone' => env('WORK_TIMEZONE', 'Europe/Moscow'),
                    'weekend_days' => explode(',', env('WEEKEND_DAYS', '6,0')),
                    'service_start_date' => env('SERVICE_START_DATE', '2025-01-01'),
                ],
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()];
        }
    }

    /**
     * Получить название объекта
     */
    private function getObjectName($objectId, $token)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
                'Accept' => 'application/json',
            ])->get($this->apiBaseUrl . '/objects', [
                'perPage' => 1000,
                'page' => 1,
            ]);

            if ($response->successful()) {
                $objects = $response->json()['data'] ?? [];
                $object = collect($objects)->firstWhere('id', $objectId);
                return $object['title'] ?? "Объект {$objectId}";
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки получения названия
        }

        return "Объект {$objectId}";
    }

    /**
     * Генерировать HTML для PDF
     */
    private function generateTimesheetHtml($timesheet, $calendar, $workSettings, $objectName, $year, $month)
    {
        $monthName = $this->getMonthName($month);
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 10px; }
                .header { text-align: center; margin-bottom: 20px; }
                .header h1 { font-size: 16px; margin: 0; }
                .header p { margin: 5px 0; }
                table { width: 100%; border-collapse: collapse; font-size: 8px; }
                th, td { border: 1px solid #000; padding: 2px; text-align: center; }
                th { background-color: #e0e0e0; font-weight: bold; }
                .employee-name { text-align: left; font-size: 7px; }
                .weekend { background-color: #ffe0e0; }
                .total-row { background-color: #ffffcc; font-weight: bold; }
                .report-mark { color: green; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Табель рабочего времени</h1>
                <p><strong>Объект:</strong> ' . htmlspecialchars($objectName) . '</p>
                <p><strong>Период:</strong> ' . $monthName . ' ' . $year . '</p>
                <p><strong>Рабочих часов в день:</strong> ' . $workSettings['work_hours_per_day'] . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 30px;">№</th>
                        <th rowspan="2" style="width: 120px;">ФИО</th>
                        <th rowspan="2" style="width: 80px;">Должность</th>';

        // Заголовки дней
        foreach ($calendar as $day) {
            $weekendClass = ($day['is_weekend'] || $day['is_holiday']) ? ' weekend' : '';
            $html .= '<th class="' . $weekendClass . '" style="width: 20px;">' . $day['day'] . '</th>';
        }

        $html .= '
                        <th rowspan="2" style="width: 40px;">Итого часов</th>
                        <th rowspan="2" style="width: 30px;">Отчетов</th>
                    </tr>
                    <tr>';

        // Подзаголовки с днями недели
        foreach ($calendar as $day) {
            $dayName = $this->getDayName($day['day_of_week']);
            $weekendClass = ($day['is_weekend'] || $day['is_holiday']) ? ' weekend' : '';
            $html .= '<th class="' . $weekendClass . '">' . $dayName . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        // Данные по сотрудникам
        $rowNumber = 1;
        foreach ($timesheet as $userTimesheet) {
            $html .= '<tr>';
            $html .= '<td>' . $rowNumber++ . '</td>';
            $html .= '<td class="employee-name">' . htmlspecialchars($userTimesheet['user']['name'] . ' ' . ($userTimesheet['user']['family_name'] ?? $userTimesheet['user']['surname'] ?? '')) . '</td>';
            $html .= '<td class="employee-name">' . htmlspecialchars($userTimesheet['user']['position'] ?? 'Не указана') . '</td>';

            // Часы по дням
            foreach ($userTimesheet['days'] as $day) {
                $weekendClass = ($day['is_weekend'] || $day['is_holiday']) ? ' weekend' : '';
                if ($day['is_weekend'] || $day['is_holiday']) {
                    $html .= '<td class="' . $weekendClass . '">В</td>';
                } else {
                    $cellValue = $day['hours_worked'] ?: '0';
                    if ($day['has_report']) {
                        $cellValue .= ' <span class="report-mark">✓</span>';
                    }
                    $html .= '<td class="' . $weekendClass . '">' . $cellValue . '</td>';
                }
            }

            $html .= '<td><strong>' . $userTimesheet['stats']['total_hours'] . '</strong></td>';
            $html .= '<td>' . $userTimesheet['stats']['days_with_reports'] . '</td>';
            $html .= '</tr>';
        }

        // Итоговая строка
        if (!empty($timesheet)) {
            $html .= '<tr class="total-row">';
            $html .= '<td></td><td><strong>ИТОГО:</strong></td><td></td>';

            $totalHours = 0;
            $totalReports = 0;

            foreach ($calendar as $dayIndex => $day) {
                $dayTotal = 0;
                foreach ($timesheet as $userTimesheet) {
                    if (!$day['is_weekend'] && !$day['is_holiday']) {
                        $dayTotal += $userTimesheet['days'][$dayIndex]['hours_worked'] ?? 0;
                    }
                }
                $weekendClass = ($day['is_weekend'] || $day['is_holiday']) ? ' weekend' : '';
                $html .= '<td class="' . $weekendClass . '">' . ($day['is_weekend'] || $day['is_holiday'] ? 'В' : $dayTotal) . '</td>';
            }

            foreach ($timesheet as $userTimesheet) {
                $totalHours += $userTimesheet['stats']['total_hours'];
                $totalReports += $userTimesheet['stats']['days_with_reports'];
            }

            $html .= '<td><strong>' . $totalHours . '</strong></td>';
            $html .= '<td><strong>' . $totalReports . '</strong></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></body></html>';

        return $html;
    }

    /**
     * Получить название месяца
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];
        return $months[$month] ?? 'Неизвестно';
    }

    /**
     * Получить название дня недели
     */
    private function getDayName($dayOfWeek)
    {
        $dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        return $dayNames[$dayOfWeek] ?? '';
    }

    /**
     * Временный метод для отладки табеля
     */
    public function getMonthlyTimesheetDebug(Request $request): JsonResponse
    {
        $request->validate([
            'object_id' => 'required|string',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            // Получаем данные табеля за месяц
            $timesheetData = Timesheet::getMonthlyTimesheet(
                $request->object_id,
                $request->year,
                $request->month
            );

            // Если нет данных в табеле, возвращаем пустой ответ
            if ($timesheetData->isEmpty()) {
                return response()->json([
                    'timesheet' => [],
                    'calendar' => [],
                    'work_settings' => [
                        'work_hours_per_day' => env('WORK_HOURS_PER_DAY', 8),
                        'work_start_time' => env('WORK_START_TIME', '09:00'),
                        'timezone' => env('WORK_TIMEZONE', 'Europe/Moscow'),
                        'weekend_days' => explode(',', env('WEEKEND_DAYS', '6,0')),
                        'service_start_date' => env('SERVICE_START_DATE', '2025-01-01'),
                    ],
                ]);
            }

            // Группируем по пользователям
            $timesheetByUser = $timesheetData->groupBy('user_id');

            // Получаем календарь месяца
            $startDate = Carbon::create($request->year, $request->month, 1);
            $endDate = $startDate->copy()->endOfMonth();
            $calendar = $this->generateCalendar($startDate, $endDate);

            // Получаем уникальных пользователей из табеля
            $objectUsers = $timesheetData->groupBy('user_id')->map(function ($userRecords) {
                $first = $userRecords->first();
                return [
                    'id' => $first->user_id,
                    'name' => $first->user_name,
                    'family_name' => $first->user_surname,
                    'surname' => $first->user_surname,
                    'position' => 'Пользователь',
                ];
            })->values()->toArray();

            // Формируем данные для каждого пользователя
            $result = [];
            foreach ($objectUsers as $user) {
                $userId = $user['id'];
                $userTimesheets = $timesheetByUser->get($userId, collect());
                
                // Создаем массив дней с данными
                $days = [];
                foreach ($calendar as $date) {
                    $timesheet = $userTimesheets->filter(function($record) use ($date) { return $record->date->format('Y-m-d') === $date->format('Y-m-d'); })->first();
                    
                    $days[] = [
                        'date' => $date->format('Y-m-d'),
                        'day_of_week' => $date->dayOfWeek,
                        'is_weekend' => $this->isWeekend($date),
                        'is_holiday' => $this->isHoliday($date),
                        'hours_worked' => $timesheet ? $timesheet->hours_worked : 0,
                        'has_report' => $timesheet ? $timesheet->has_report : false,
                    ];
                }

                // Статистика по пользователю
                $stats = Timesheet::getUserMonthlyStats(
                    $request->object_id,
                    $userId,
                    $request->year,
                    $request->month
                );

                $result[] = [
                    'user' => $user,
                    'days' => $days,
                    'stats' => $stats,
                ];
            }

            return response()->json([
                'timesheet' => $result,
                'calendar' => $calendar->map(function ($date) {
                    return [
                        'date' => $date->format('Y-m-d'),
                        'day' => $date->day,
                        'day_of_week' => $date->dayOfWeek,
                        'is_weekend' => $this->isWeekend($date),
                        'is_holiday' => $this->isHoliday($date),
                    ];
                })->toArray(),
                'work_settings' => [
                    'work_hours_per_day' => env('WORK_HOURS_PER_DAY', 8),
                    'work_start_time' => env('WORK_START_TIME', '09:00'),
                    'timezone' => env('WORK_TIMEZONE', 'Europe/Moscow'),
                    'weekend_days' => explode(',', env('WEEKEND_DAYS', '6,0')),
                    'service_start_date' => env('SERVICE_START_DATE', '2025-01-01'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка сервера: ' . $e->getMessage()], 500);
        }
    }
}
