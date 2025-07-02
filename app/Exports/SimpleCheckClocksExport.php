<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class SimpleCheckClocksExport implements FromCollection, WithHeadings
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
            
            Log::info('Simple export collection starting', [
                'month' => $month,
                'year' => $year,
                'positions' => $positions,
                'statuses' => $statuses
            ]);
            
            // Create date range for the month
            $startDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
            $endDate = date('Y-m-t', strtotime($startDate));
            
            // Base query with date range
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
                'e.FirstName',
                'e.LastName',
                'p.name as position',
                'cc.check_clock_date as date',
                'cc.check_clock_time as clock_in',
                'cc.status'
            ])
            ->orderBy('cc.check_clock_date', 'desc')
            ->get();

            // Transform data for Excel
            $data = collect();
            foreach ($checkInRecords as $record) {
                $data->push([
                    'ID' => $record->id,
                    'Employee Name' => ($record->FirstName ?? '') . ' ' . ($record->LastName ?? ''),
                    'Position' => $record->position ?? '-',
                    'Date' => $record->date ? Carbon::parse($record->date)->format('Y-m-d') : '-',
                    'Check In Time' => $record->clock_in ? Carbon::parse($record->clock_in)->format('H:i:s') : '-',
                    'Status' => $record->status ?? '-'
                ]);
            }

            Log::info('Simple export collection completed', ['final_count' => $data->count()]);
            return $data;
            
        } catch (\Exception $e) {
            Log::error('Simple export collection error: ' . $e->getMessage());
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
            'Status'
        ];
    }
}
