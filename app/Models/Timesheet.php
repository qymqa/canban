<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Timesheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'object_id',
        'user_id',
        'user_name',
        'user_surname',
        'date',
        'hours_worked',
        'has_report',
    ];

    protected $casts = [
        'date' => 'date',
        'hours_worked' => 'decimal:2',
        'has_report' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить полное имя пользователя
     */
    public function getFullNameAttribute()
    {
        return trim($this->user_name . ' ' . $this->user_surname);
    }

    /**
     * Получить или создать запись табеля
     */
    public static function getOrCreate($objectId, $userId, $userName, $userSurname, $date)
    {
        $dateFormatted = Carbon::parse($date)->format('Y-m-d');
        
        // Сначала пытаемся найти существующую запись используя whereDate для правильного сравнения
        $existing = self::where('object_id', $objectId)
            ->where('user_id', $userId)
            ->whereDate('date', $dateFormatted)
            ->first();
            
        if ($existing) {
            return $existing;
        }
        
        // Если не найдена, создаем новую
        return self::create([
            'object_id' => $objectId,
            'user_id' => $userId,
            'date' => $dateFormatted,
            'user_name' => $userName,
            'user_surname' => $userSurname,
            'hours_worked' => 0,
            'has_report' => false,
        ]);
    }

    /**
     * Обновить статус отчета
     */
    public function updateReportStatus($hasReport, $hoursWorked = null)
    {
        $this->has_report = $hasReport;
        
        if ($hoursWorked !== null) {
            $this->hours_worked = $hoursWorked;
        } elseif ($hasReport) {
            // Если отчет есть, но часы не указаны, ставим рабочие часы из env
            $this->hours_worked = env('WORK_HOURS_PER_DAY', 8);
        } else {
            // Если отчета нет, ставим 0 часов
            $this->hours_worked = 0;
        }
        
        $this->save();
    }

    /**
     * Получить табель за месяц
     */
    public static function getMonthlyTimesheet($objectId, $year, $month, $userId = null)
    {
        $query = self::where('object_id', $objectId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('date')->get();
    }

    /**
     * Получить статистику по пользователю за месяц
     */
    public static function getUserMonthlyStats($objectId, $userId, $year, $month)
    {
        $records = self::where('object_id', $objectId)
            ->where('user_id', $userId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        return [
            'total_hours' => $records->sum('hours_worked'),
            'days_with_reports' => $records->where('has_report', true)->count(),
            'total_work_days' => $records->count(),
        ];
    }

    /**
     * Получить общую статистику за месяц для всех пользователей объекта
     */
    public static function getMonthlyStats($objectId, $year, $month)
    {
        $records = self::where('object_id', $objectId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->get();

        $userStats = $records->groupBy('user_id')->map(function ($userRecords, $userId) {
            return [
                'user_id' => $userId,
                'user_name' => $userRecords->first()->user_name,
                'user_surname' => $userRecords->first()->user_surname,
                'total_hours' => $userRecords->sum('hours_worked'),
                'days_with_reports' => $userRecords->where('has_report', true)->count(),
                'total_work_days' => $userRecords->count(),
            ];
        })->values();

        return [
            'users' => $userStats,
            'totals' => [
                'total_hours' => $records->sum('hours_worked'),
                'total_reports' => $records->where('has_report', true)->count(),
                'total_work_days' => $records->count(),
                'unique_users' => $records->pluck('user_id')->unique()->count(),
            ]
        ];
    }
}
