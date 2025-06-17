<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Carbon\Carbon;

class CheckClocksExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $data = DB::table('check_clocks as cc')
            ->select(
                'cc.id',
                DB::raw("CONCAT(e.\"FirstName\", ' ', e.\"LastName\") as employee_name"),
                'cc.check_clock_date',
                'cc.check_clock_time',
                'cc.check_out_time',
                'cc.start_date',
                'cc.end_date',
                DB::raw("CASE 
                    WHEN cc.check_clock_type = 'annual-leave' AND cc.approved = true 
                        AND cc.check_clock_date BETWEEN cc.start_date AND cc.end_date THEN 'Annual Leave'
                        
                    WHEN cc.check_clock_type = 'sick-leave' AND cc.approved = true 
                        AND cc.check_clock_date BETWEEN cc.start_date AND cc.end_date THEN 'Sick Leave'

                    WHEN cc.approved IS NULL THEN 'Waiting Approval'
                    WHEN cc.approved = false THEN '-'
                    
                    WHEN cc.check_clock_time IS NULL THEN 'Absent'

                    WHEN cc.check_clock_type = 'annual-leave' AND cc.approved = true THEN 'Annual Leave'
                    WHEN cc.check_clock_type = 'sick-leave' AND cc.approved = true THEN 'Sick Leave'
                                
                    WHEN EXISTS (
                        SELECT 1 FROM check_clock_setting_times ccst
                        WHERE ccst.ck_settings_id = cc.ck_settings_id
                        AND ccst.day = TRIM(TO_CHAR(cc.check_clock_date, 'Day'))
                        AND ccst.work_day = true
                        AND cc.check_clock_time::time > ccst.clock_in_end::time
                    ) THEN 'Absent'
                    
                    WHEN EXISTS (
                        SELECT 1 FROM check_clock_setting_times ccst
                        WHERE ccst.ck_settings_id = cc.ck_settings_id
                        AND ccst.day = TRIM(TO_CHAR(cc.check_clock_date, 'Day'))
                        AND ccst.work_day = true
                        AND cc.check_clock_time::time > ccst.clock_in_on_time_limit::time
                    ) THEN 'Late'
                    
                    ELSE 'On Time'
                END as status"),
                'cc.approved',
                'cc.location',
                'cc.address',
                'cc.latitude',
                'cc.longitude'
            )
            ->join('employees as e', 'cc.employee_id', '=', 'e.id')
            ->whereNull('cc.deleted_at')
            ->get()
            ->map(function ($item) {
                return [
                    'ID' => $item->id,
                    'Employee Name' => $item->employee_name ?? '-',
                    'Date' => $item->check_clock_date ? Carbon::parse($item->check_clock_date)->format('Y-m-d') : '-',
                    'Check In Time' => $item->check_clock_time ? Carbon::parse($item->check_clock_time)->format('H:i:s') : '-',
                    'Check Out Time' => $item->check_out_time ? Carbon::parse($item->check_out_time)->format('H:i:s') : '-',
                    'Start Date' => $item->start_date ? Carbon::parse($item->start_date)->format('Y-m-d') : '-',
                    'End Date' => $item->end_date ? Carbon::parse($item->end_date)->format('Y-m-d') : '-',
                    'Status' => $item->status ?? '-',
                    'Approved' => $item->approved === true ? 'Approved' : ($item->approved === false ? 'Rejected' : 'Pending'),
                    'Location' => $item->location,
                    'Address' => $item->address,
                    'Latitude' => $item->latitude,
                    'Longitude' => $item->longitude,
                ];
            });

        return $data;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Employee Name',
            'Date',
            'Check In Time',
            'Check Out Time',
            'Start Date',
            'End Date',
            'Status',
            'Approved',
            'Location',
            'Address',
            'Latitude',
            'Longitude'
        ];
    }
}
