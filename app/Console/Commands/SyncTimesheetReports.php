<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyReport;
use App\Models\Timesheet;

class SyncTimesheetReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timesheet:sync-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Синхронизация отчетов с табелем рабочего времени';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Синхронизация отчетов с табелем...');
        
        $reports = DailyReport::all();
        $bar = $this->output->createProgressBar($reports->count());
        
        foreach ($reports as $report) {
            $reportDate = $report->created_at->format('Y-m-d');
            
            // Находим или создаем запись в табеле
            $timesheet = Timesheet::where('object_id', $report->object_id)
                ->where('user_id', $report->user_id)
                ->whereDate('date', $reportDate)
                ->first();
                
            if ($timesheet) {
                $timesheet->update([
                    'has_report' => true,
                    'hours_worked' => $timesheet->hours_worked > 0 ? $timesheet->hours_worked : env('WORK_HOURS_PER_DAY', 8)
                ]);
            } else {
                Timesheet::create([
                    'object_id' => $report->object_id,
                    'user_id' => $report->user_id,
                    'date' => $reportDate,
                    'user_name' => $report->user_name,
                    'user_surname' => $report->user_surname,
                    'hours_worked' => env('WORK_HOURS_PER_DAY', 8),
                    'has_report' => true,
                ]);
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Синхронизация завершена успешно!');
        
        return 0;
    }
}
