<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;

class CheckClocksExportFixed implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithTitle
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
            // Simple header styling only
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1E3A5F'],
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
}
