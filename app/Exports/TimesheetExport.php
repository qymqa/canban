<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TimesheetExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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

    public function array(): array
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

        return $data;
    }

    public function headings(): array
    {
        // Заголовки уже включены в array(), поэтому возвращаем пустой массив
        return [];
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getLastColumn();
        $lastRow = count($this->array());

        // Стиль для заголовка
        $sheet->getStyle('A1:' . $lastColumn . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Стиль для информации об объекте
        $sheet->getStyle('A2:A4')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
        ]);

        // Стиль для заголовков таблицы
        $sheet->getStyle('A6:' . $lastColumn . '7')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE0E0E0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Стиль для данных
        $dataStartRow = 8;
        $dataEndRow = $lastRow - 1; // Исключаем итоговую строку
        if ($dataEndRow >= $dataStartRow) {
            $sheet->getStyle('A' . $dataStartRow . ':' . $lastColumn . $dataEndRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // Стиль для итоговой строки
        $sheet->getStyle('A' . $lastRow . ':' . $lastColumn . $lastRow)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFFFFFE0'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THICK,
                ],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Выравнивание для ФИО и должности
        $sheet->getStyle('B' . $dataStartRow . ':C' . $dataEndRow)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        ]);

        return [];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 5,  // №
            'B' => 25, // ФИО
            'C' => 20, // Должность
        ];

        // Ширина для дней месяца
        $columnIndex = 'D';
        foreach ($this->calendar as $day) {
            $widths[$columnIndex] = 6;
            $columnIndex++;
        }

        // Ширина для итоговых колонок
        $widths[$columnIndex] = 12; // Итого часов
        $columnIndex++;
        $widths[$columnIndex] = 10; // Отчетов

        return $widths;
    }

    public function title(): string
    {
        $monthName = $this->getMonthName($this->month);
        return "Табель {$monthName} {$this->year}";
    }

    private function getLastColumn()
    {
        // Рассчитываем последнюю колонку (№, ФИО, Должность + дни месяца + итого часов + отчетов)
        $columnCount = 3 + count($this->calendar) + 2;
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnCount);
    }

    private function getMonthName($month)
    {
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь'
        ];
        return $months[$month] ?? 'Неизвестно';
    }

    private function getDayName($dayOfWeek)
    {
        $dayNames = ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'];
        return $dayNames[$dayOfWeek] ?? '';
    }
}
