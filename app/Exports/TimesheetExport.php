<?php

namespace App\Exports;

class TimesheetExport
{
    protected $timesheet;
    protected $calendar;
    protected $workSettings;
    protected $objectName;
    protected $year;
    protected $month;

    public function __construct($timesheet, $calendar, $workSettings, $objectName, $year, $month)
    {
        $this->timesheet = $timesheet;
        $this->calendar = $calendar;
        $this->workSettings = $workSettings;
        $this->objectName = $objectName;
        $this->year = $year;
        $this->month = $month;
    }

    public function toCsv(): string
    {
        $data = [];
        
        // Добавляем заголовок с информацией об объекте и месяце
        $monthName = $this->getMonthName($this->month);
        $data[] = ["Табель рабочего времени"];
        $data[] = ["Объект: " . $this->objectName];
        $data[] = ["Период: " . $monthName . " " . $this->year];
        $data[] = ["Рабочих часов в день: " . $this->workSettings['work_hours_per_day']];
        $data[] = []; // Пустая строка

        // Заголовки столбцов
        $headers = ['№', 'ФИО', 'Должность'];
        foreach ($this->calendar as $day) {
            $headers[] = $day['day'];
        }
        $headers[] = 'Итого часов';
        $headers[] = 'Отчетов';
        $data[] = $headers;

        // Подзаголовки с днями недели
        $subHeaders = ['', '', ''];
        foreach ($this->calendar as $day) {
            $dayName = $this->getDayName($day['day_of_week']);
            $subHeaders[] = $dayName;
        }
        $subHeaders[] = '';
        $subHeaders[] = '';
        $data[] = $subHeaders;

        // Данные по сотрудникам
        $rowNumber = 1;
        foreach ($this->timesheet as $userTimesheet) {
            $row = [
                $rowNumber++,
                $userTimesheet['user']['name'] . ' ' . ($userTimesheet['user']['family_name'] ?? $userTimesheet['user']['surname'] ?? ''),
                $userTimesheet['user']['position'] ?? 'Не указана'
            ];

            // Добавляем часы по дням
            foreach ($userTimesheet['days'] as $day) {
                if ($day['is_weekend'] || $day['is_holiday']) {
                    $row[] = 'В'; // Выходной
                } else {
                    $cellValue = $day['hours_worked'] ?: '0';
                    if ($day['has_report']) {
                        $cellValue .= ' ✓'; // Отметка о наличии отчета
                    }
                    $row[] = $cellValue;
                }
            }

            $row[] = $userTimesheet['stats']['total_hours'];
            $row[] = $userTimesheet['stats']['days_with_reports'];
            $data[] = $row;
        }

        // Итоговая строка
        if (!empty($this->timesheet)) {
            $totalRow = ['', 'ИТОГО:', ''];
            $totalHours = 0;
            $totalReports = 0;

            foreach ($this->calendar as $dayIndex => $day) {
                $dayTotal = 0;
                foreach ($this->timesheet as $userTimesheet) {
                    if (!$day['is_weekend'] && !$day['is_holiday']) {
                        $dayTotal += $userTimesheet['days'][$dayIndex]['hours_worked'] ?? 0;
                    }
                }
                $totalRow[] = $day['is_weekend'] || $day['is_holiday'] ? 'В' : $dayTotal;
            }

            foreach ($this->timesheet as $userTimesheet) {
                $totalHours += $userTimesheet['stats']['total_hours'];
                $totalReports += $userTimesheet['stats']['days_with_reports'];
            }

            $totalRow[] = $totalHours;
            $totalRow[] = $totalReports;
            $data[] = $totalRow;
        }

        return $this->arrayToCsv($data);
    }

    public function toCsvExcel(): string
    {
        $data = [];
        
        // Добавляем заголовок с информацией об объекте и месяце
        $monthName = $this->getMonthName($this->month);
        $data[] = ["Табель рабочего времени"];
        $data[] = ["Объект: " . $this->objectName];
        $data[] = ["Период: " . $monthName . " " . $this->year];
        $data[] = ["Рабочих часов в день: " . $this->workSettings['work_hours_per_day']];
        $data[] = []; // Пустая строка

        // Заголовки столбцов
        $headers = ['№', 'ФИО', 'Должность'];
        foreach ($this->calendar as $day) {
            $headers[] = $day['day'];
        }
        $headers[] = 'Итого часов';
        $headers[] = 'Отчетов';
        $data[] = $headers;

        // Подзаголовки с днями недели
        $subHeaders = ['', '', ''];
        foreach ($this->calendar as $day) {
            $dayName = $this->getDayName($day['day_of_week']);
            $subHeaders[] = $dayName;
        }
        $subHeaders[] = '';
        $subHeaders[] = '';
        $data[] = $subHeaders;

        // Данные по сотрудникам
        $rowNumber = 1;
        foreach ($this->timesheet as $userTimesheet) {
            $row = [
                $rowNumber++,
                $userTimesheet['user']['name'] . ' ' . ($userTimesheet['user']['family_name'] ?? $userTimesheet['user']['surname'] ?? ''),
                $userTimesheet['user']['position'] ?? 'Не указана'
            ];

            // Добавляем часы по дням
            foreach ($userTimesheet['days'] as $day) {
                if ($day['is_weekend'] || $day['is_holiday']) {
                    $row[] = 'В'; // Выходной
                } else {
                    $cellValue = $day['hours_worked'] ?: '0';
                    if ($day['has_report']) {
                        $cellValue .= ' ✓'; // Отметка о наличии отчета
                    }
                    $row[] = $cellValue;
                }
            }

            $row[] = $userTimesheet['stats']['total_hours'];
            $row[] = $userTimesheet['stats']['days_with_reports'];
            $data[] = $row;
        }

        // Итоговая строка
        if (!empty($this->timesheet)) {
            $totalRow = ['', 'ИТОГО:', ''];
            $totalHours = 0;
            $totalReports = 0;

            foreach ($this->calendar as $dayIndex => $day) {
                $dayTotal = 0;
                foreach ($this->timesheet as $userTimesheet) {
                    if (!$day['is_weekend'] && !$day['is_holiday']) {
                        $dayTotal += $userTimesheet['days'][$dayIndex]['hours_worked'] ?? 0;
                    }
                }
                $totalRow[] = $day['is_weekend'] || $day['is_holiday'] ? 'В' : $dayTotal;
            }

            foreach ($this->timesheet as $userTimesheet) {
                $totalHours += $userTimesheet['stats']['total_hours'];
                $totalReports += $userTimesheet['stats']['days_with_reports'];
            }

            $totalRow[] = $totalHours;
            $totalRow[] = $totalReports;
            $data[] = $totalRow;
        }

        return $this->arrayToCsvExcel($data);
    }

    private function arrayToCsv(array $data): string
    {
        // Добавляем BOM для UTF-8 чтобы Excel корректно отображал русские символы
        $output = "\xEF\xBB\xBF";
        
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($row as $field) {
                // Конвертируем в строку если это не строка
                $field = (string) $field;
                
                // Экранируем кавычки и оборачиваем в кавычки если нужно
                $field = str_replace('"', '""', $field);
                if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false || strpos($field, ';') !== false) {
                    $field = '"' . $field . '"';
                }
                $csvRow[] = $field;
            }
            $output .= implode(',', $csvRow) . "\r\n";
        }
        return $output;
    }

    private function arrayToCsvExcel(array $data): string
    {
        // Добавляем BOM для UTF-8 чтобы Excel корректно отображал русские символы
        $output = "\xEF\xBB\xBF";
        
        foreach ($data as $row) {
            $csvRow = [];
            foreach ($row as $field) {
                // Конвертируем в строку если это не строка
                $field = (string) $field;
                
                // Экранируем кавычки и оборачиваем в кавычки если нужно
                $field = str_replace('"', '""', $field);
                if (strpos($field, ';') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false || strpos($field, ',') !== false) {
                    $field = '"' . $field . '"';
                }
                $csvRow[] = $field;
            }
            $output .= implode(';', $csvRow) . "\r\n"; // Используем точку с запятой для Excel
        }
        return $output;
    }

    private function getMonthName($month)
    {
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];
        return $months[$month] ?? 'Неизвестный месяц';
    }

    private function getDayName($dayOfWeek)
    {
        $days = [
            0 => 'Вс', 1 => 'Пн', 2 => 'Вт', 3 => 'Ср',
            4 => 'Чт', 5 => 'Пт', 6 => 'Сб'
        ];
        return $days[$dayOfWeek] ?? '';
    }
}
