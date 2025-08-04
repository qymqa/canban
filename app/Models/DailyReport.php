<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'object_id',
        'user_id',
        'user_name',
        'user_surname',
        'user_position',
        'report_text',
        'attached_tasks',
    ];

    protected $casts = [
        'attached_tasks' => 'array',
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
     * Получить отчеты за определенный период
     */
    public static function getReportsForPeriod($objectId, $startDate, $endDate, $userId = null)
    {
        $query = self::where('object_id', $objectId)
            ->whereBetween('created_at', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Получить отчеты за конкретный день
     */
    public static function getReportsForDate($objectId, $date, $userId = null)
    {
        $query = self::where('object_id', $objectId)
            ->whereDate('created_at', Carbon::parse($date));

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
