<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;

class CheckClocksExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        try {
            // Get filter parameters with defaults
            $month = $this->filters['month'] ?? now()->month;
            $year = $this->filters['year'] ?? now()->year;
            $positions = $this->filters['positions'] ?? [];
            $statuses = $this->filters['statuses'] ?? [];
            
            Log::info('Export collection starting', [
                'month' => $month,
                'year' => $year,
                'positions' => $positions,
                'statuses' => $statuses
            ]);
            
            // Create date range for the month (same as controller)
            $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
            $endDate = date('Y-m-t', strtotime($startDate)); // Last day of the month
            
            Log::info('Date range', ['start' => $startDate, 'end' => $endDate]);
            
            // Base query with date range (same as controller)
            $checkInRecords = DB::table('check_clocks as cc')
                ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                ->whereNull('cc.deleted_at')
                ->whereBetween('cc.check_clock_date', [$startDate, $endDate])
                ->where('cc.check_clock_type', 'check-in');
            
            // Apply position filter if provided
            if (!empty($positions) && is_array($positions)) {
                $checkInRecords->whereIn('p.name', $positions);
            }
            
            // Apply status filter if provided
            if (!empty($statuses) && is_array($statuses)) {
                $checkInRecords->whereIn('cc.status', $statuses);
            }
            
            $checkInRecords = $checkInRecords->select([
                'cc.id',
                'cc.employee_id',
                'e.FirstName',
                'e.LastName',
                DB::raw('CONCAT("e"."FirstName", \' \', "e"."LastName") as employee_name'),
                'e.Position_id',
                'p.name as position',
                'cc.check_clock_date as date',
                'cc.check_clock_time as clock_in',
                'cc.approved',
                'cc.status',
                'cc.location',
                'cc.address as detail_address',
                'cc.latitude',
                'cc.longitude',
                'cc.photo as proof_of_attendance'
            ])
            ->orderBy('cc.check_clock_date', 'desc')
            ->orderBy('cc.id')
            ->get();

            Log::info('Records found', ['count' => $checkInRecords->count()]);

            // For each check-in record, find the corresponding check-out record (same as controller)
            $checkClocks = collect();
            foreach ($checkInRecords as $checkIn) {
                $checkOut = DB::table('check_clocks')
                    ->where('employee_id', $checkIn->employee_id)
                    ->where('check_clock_date', $checkIn->date)
                    ->where('check_clock_type', 'check-out')
                    ->whereNull('deleted_at')
                    ->first();

                // Calculate work hours if both check-in and check-out exist
                $workHours = null;
                $clockOut = null;
                
                if ($checkOut && $checkOut->check_out_time) {
                    $clockOut = $checkOut->check_out_time;
                    $checkInTime = strtotime($checkIn->clock_in);
                    $checkOutTime = strtotime($checkOut->check_out_time);
                    
                    if ($checkInTime && $checkOutTime && $checkOutTime > $checkInTime) {
                        $diffSeconds = $checkOutTime - $checkInTime;
                        $hours = floor($diffSeconds / 3600);
                        $minutes = floor(($diffSeconds % 3600) / 60);
                        $workHours = $hours . 'h ' . $minutes . 'm';
                    }
                }

                $checkClocks->push([
                    'ID' => $checkIn->id,
                    'Employee Name' => $checkIn->employee_name ?? '-',
                    'Position' => $checkIn->position ?? '-',
                    'Date' => $checkIn->date ? Carbon::parse($checkIn->date)->format('Y-m-d') : '-',
                    'Check In Time' => $checkIn->clock_in ? Carbon::parse($checkIn->clock_in)->format('H:i:s') : '-',
                    'Check Out Time' => $clockOut ? Carbon::parse($clockOut)->format('H:i:s') : '-',
                    'Work Hours' => $workHours ?? '-',
                    'Status' => $checkIn->status ?? '-',
                    'Approved' => $checkIn->approved === true ? 'Approved' : ($checkIn->approved === false ? 'Rejected' : 'Pending'),
                    'Location' => $checkIn->location ?? '-',
                    'Address' => $checkIn->detail_address ?? '-',
                    'Latitude' => $checkIn->latitude ?? '-',
                    'Longitude' => $checkIn->longitude ?? '-',
                ]);
            }

            Log::info('Export collection completed', ['final_count' => $checkClocks->count()]);
            return $checkClocks;
            
        } catch (\Exception $e) {
            Log::error('Export collection error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee Name',
            'Position',
            'Date',
            'Check In Time',
            'Check Out Time',
            'Work Hours',
            'Status',
            'Approved',
            'Location',
            'Address',
            'Latitude',
            'Longitude'
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            // Header styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A5F'], // Dark blue
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // Data styling for all rows
            'A:M' => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // ID
            'B' => 20,  // Employee Name
            'C' => 15,  // Position
            'D' => 12,  // Date
            'E' => 12,  // Check In Time
            'F' => 12,  // Check Out Time
            'G' => 12,  // Work Hours
            'H' => 12,  // Status
            'I' => 12,  // Approved
            'J' => 15,  // Location
            'K' => 25,  // Address
            'L' => 12,  // Latitude
            'M' => 12,  // Longitude
        ];
    }

    public function title(): string
    {
        $month = $this->filters['month'] ?? now()->month;
        $year = $this->filters['year'] ?? now()->year;
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        
        return "Check Clock Report - {$monthName} {$year}";
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Add title row
                $sheet->insertNewRowBefore(1, 2);
                
                // Set title
                $month = $this->filters['month'] ?? now()->month;
                $year = $this->filters['year'] ?? now()->year;
                $monthName = date('F', mktime(0, 0, 0, $month, 1));
                
                $sheet->setCellValue('A1', "HRIS - Check Clock Report");
                $sheet->setCellValue('A2', "{$monthName} {$year}");
                
                // Style title
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => '1E3A5F'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 12,
                        'color' => ['rgb' => '666666'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                
                // Merge title cells
                $sheet->mergeCells('A1:M1');
                $sheet->mergeCells('A2:M2');
                
                // Set row heights
                $sheet->getRowDimension(1)->setRowHeight(25);
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getRowDimension(3)->setRowHeight(20); // Header row
                
                // Apply zebra striping to data rows
                $dataStartRow = 4;
                $dataEndRow = $sheet->getHighestRow();
                
                for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                    if (($row - $dataStartRow) % 2 == 1) {
                        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8F9FA'], // Light gray
                            ],
                        ]);
                    }
                }
                
                // Apply conditional formatting for status column (H)
                for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                    $statusCell = $sheet->getCell("H{$row}");
                    $status = $statusCell->getValue();
                    
                    $statusColors = [
                        'On Time' => '28A745',    // Green
                        'Late' => 'FFC107',       // Yellow
                        'Absent' => 'DC3545',     // Red
                        'Annual Leave' => '17A2B8', // Cyan
                        'Sick Leave' => '6F42C1',  // Purple
                    ];
                    
                    if (isset($statusColors[$status])) {
                        $sheet->getStyle("H{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $statusColors[$status]],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ],
                        ]);
                    }
                }
                
                // Apply conditional formatting for approved column (I)
                for ($row = $dataStartRow; $row <= $dataEndRow; $row++) {
                    $approvedCell = $sheet->getCell("I{$row}");
                    $approved = $approvedCell->getValue();
                    
                    $approvedColors = [
                        'Approved' => '28A745',   // Green
                        'Rejected' => 'DC3545',   // Red
                        'Pending' => 'FFC107',    // Yellow
                    ];
                    
                    if (isset($approvedColors[$approved])) {
                        $sheet->getStyle("I{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => 'FFFFFF'],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => $approvedColors[$approved]],
                            ],
                            'alignment' => [
                                'horizontal' => Alignment::HORIZONTAL_CENTER,
                            ],
                        ]);
                    }
                }
                
                // Add filters applied section if filters exist
                $hasFilters = !empty($this->filters['positions']) || !empty($this->filters['statuses']);
                if ($hasFilters) {
                    $lastRow = $sheet->getHighestRow() + 2;
                    
                    $sheet->setCellValue("A{$lastRow}", "Filters Applied:");
                    $sheet->getStyle("A{$lastRow}")->applyFromArray([
                        'font' => ['bold' => true, 'size' => 11],
                    ]);
                    
                    $filterRow = $lastRow + 1;
                    $filterText = [];
                    
                    if (!empty($this->filters['positions'])) {
                        $filterText[] = "Positions: " . implode(', ', $this->filters['positions']);
                    }
                    
                    if (!empty($this->filters['statuses'])) {
                        $filterText[] = "Statuses: " . implode(', ', $this->filters['statuses']);
                    }
                    
                    $sheet->setCellValue("A{$filterRow}", implode(' | ', $filterText));
                    $sheet->getStyle("A{$filterRow}")->applyFromArray([
                        'font' => ['italic' => true, 'color' => ['rgb' => '666666']],
                    ]);
                    
                    $sheet->mergeCells("A{$filterRow}:M{$filterRow}");
                }
                
                // Add generation timestamp
                $timestampRow = $sheet->getHighestRow() + 2;
                $sheet->setCellValue("A{$timestampRow}", "Generated on: " . now()->format('Y-m-d H:i:s'));
                $sheet->getStyle("A{$timestampRow}")->applyFromArray([
                    'font' => ['size' => 9, 'italic' => true, 'color' => ['rgb' => '999999']],
                ]);
                $sheet->mergeCells("A{$timestampRow}:M{$timestampRow}");
                
                // Freeze header rows
                $sheet->freezePane('A4');
                
                // Set print area and page setup
                $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
                $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);
                
                // Set margins
                $sheet->getPageMargins()
                    ->setTop(0.75)
                    ->setRight(0.25)
                    ->setLeft(0.25)
                    ->setBottom(0.75);
            },
        ];
    }
}
