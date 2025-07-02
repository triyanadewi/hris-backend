<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use App\Models\CheckClocks;
use App\Models\Employee;
use App\Models\Branch;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckClocksController extends Controller
{
    private function getCheckClockDetails($query)
    {
        return $query->select([
            'cc.id',
            'cc.employee_id',
            'e.FirstName',
            'e.LastName',
            DB::raw('CONCAT("e"."FirstName", \' \', "e"."LastName") as employee_name'),
            'e.Position_id',
            'p.name as position',
            'cc.check_clock_date as date',
            'cc.check_clock_time as clock_in',
            'cc.check_out_time as clock_out',
            DB::raw('CASE 
                WHEN "cc"."check_clock_time" IS NOT NULL AND "cc"."check_out_time" IS NOT NULL 
                THEN CONCAT(
                    FLOOR(EXTRACT(EPOCH FROM ("cc"."check_out_time"::time - "cc"."check_clock_time"::time)) / 3600),
                    \'h \',
                    FLOOR((EXTRACT(EPOCH FROM ("cc"."check_out_time"::time - "cc"."check_clock_time"::time)) % 3600) / 60),
                    \'m\'
                )
                ELSE null 
            END as work_hours'),
            'cc.approved',
            'cc.status',
            'cc.location',
            'cc.address as detail_address',
            'cc.latitude',
            'cc.longitude',
            'cc.photo as proof_of_attendance'
        ]);
    }

    // Helper method untuk menentukan status check-in
    private function determineCheckInStatus($settingsId, $date, $time)
    {
        if (!$settingsId || !$time) {
            return 'On Time';
        }

        $dayName = date('l', strtotime($date)); // Monday, Tuesday, etc.
        
        $setting = DB::table('check_clock_setting_times')
            ->where('ck_settings_id', $settingsId)
            ->where('day', $dayName)
            ->where('work_day', true)
            ->first();

        if (!$setting) {
            return 'On Time'; // Default jika tidak ada setting
        }

        $checkTime = strtotime($time);
        $onTimeLimit = strtotime($setting->clock_in_on_time_limit);
        $endTime = strtotime($setting->clock_in_end);

        if ($checkTime > $endTime) {
            return 'Absent'; // Setelah jam berakhir
        } elseif ($checkTime > $onTimeLimit) {
            return 'Late'; // Setelah batas on time
        } else {
            return 'On Time';
        }
    }

    // Helper method untuk menentukan status check-out
    private function determineCheckOutStatus($settingsId, $date, $time, $previousStatus)
    {
        if (!$settingsId || !$time) {
            return $previousStatus;
        }

        $dayName = date('l', strtotime($date));
        
        $setting = DB::table('check_clock_setting_times')
            ->where('ck_settings_id', $settingsId)
            ->where('day', $dayName)
            ->where('work_day', true)
            ->first();

        if (!$setting) {
            return $previousStatus;
        }

        $checkOutTime = strtotime($time);
        $minCheckOutTime = strtotime($setting->clock_out_start);

        // Jika checkout terlalu awal, tetap gunakan status sebelumnya
        // Karena tidak ada status "Early Leave" dalam list yang diizinkan
        if ($checkOutTime < $minCheckOutTime) {
            return $previousStatus; // Tetap gunakan status check-in
        }

        // Return status berdasarkan check-in
        return $previousStatus;
    }
    
    public function index()
    {
        try {
            $today = now()->toDateString();
            
            // Get all employees who have check-in records today
            $checkInRecords = DB::table('check_clocks as cc')
                ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                ->whereNull('cc.deleted_at')
                ->whereDate('cc.check_clock_date', '=', $today)
                ->where('cc.check_clock_type', 'check-in')
                ->select([
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
                ->get();

            // For each check-in record, find the corresponding check-out record
            $checkClocks = collect();
            foreach ($checkInRecords as $checkIn) {
                $checkOut = DB::table('check_clocks')
                    ->where('employee_id', $checkIn->employee_id)
                    ->where('check_clock_date', $today)
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

                $checkClocks->push((object)[
                    'id' => $checkIn->id,
                    'employee_id' => $checkIn->employee_id,
                    'employee_name' => $checkIn->employee_name,
                    'position' => $checkIn->position,
                    'date' => $checkIn->date,
                    'clock_in' => $checkIn->clock_in,
                    'clock_out' => $clockOut,
                    'work_hours' => $workHours,
                    'approved' => $checkIn->approved,
                    'status' => $checkIn->status,
                    'location' => $checkIn->location,
                    'detail_address' => $checkIn->detail_address,
                    'latitude' => $checkIn->latitude,
                    'longitude' => $checkIn->longitude,
                    'proof_of_attendance' => $checkIn->proof_of_attendance
                ]);
            }

            // Add absent employees (those who haven't checked in today)
            $presentEmployeeIds = $checkClocks->pluck('employee_id')->toArray();
            $allEmployees = DB::table('employees as e')
                ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                ->select('e.*', 'p.name as position_name')
                ->whereNotIn('e.id', $presentEmployeeIds)
                ->get();

            foreach ($allEmployees as $employee) {
                $checkClocks->push((object)[
                    'id' => null,
                    'employee_id' => $employee->id,
                    'employee_name' => "{$employee->FirstName} {$employee->LastName}",
                    'position' => $employee->position_name,
                    'date' => $today,
                    'clock_in' => null,
                    'clock_out' => null,
                    'work_hours' => null,
                    'approved' => null,
                    'status' => 'Absent',
                    'location' => null,
                    'detail_address' => null,
                    'latitude' => null,
                    'longitude' => null,
                    'proof_of_attendance' => null
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => $checkClocks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving records',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id'       => 'required|exists:employees,id',
            'ck_settings_id'    => 'nullable|exists:check_clock_settings,id',
            'branch_id'         => 'nullable|exists:branches,id',
            'check_clock_type'  => 'required|in:check-in,check-out,annual-leave,sick-leave,absent',
            'check_clock_date'  => 'required|date',
            'check_clock_time'  => 'nullable|date_format:H:i:s',
            'check_out_time'    => 'nullable|date_format:H:i:s',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date',
            'status'            => 'nullable|in:On Time,Late,Absent,Annual Leave,Sick Leave,Waiting Approval,-',
            'approved'          => 'nullable|boolean',
            'location'          => 'nullable|string',
            'address'           => 'nullable|string',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
            'photo'             => 'nullable|string',
        ]);

        try {
            // Get employee's branch if not provided
            if (!isset($validated['branch_id'])) {
                $employee = DB::table('employees')->where('id', $validated['employee_id'])->first();
                $validated['branch_id'] = $employee ? $employee->Branch_id : null;
            }

            // Get default check clock setting if not provided
            if (!isset($validated['ck_settings_id'])) {
                $setting = DB::table('check_clock_settings')
                    ->where('branch_id', $validated['branch_id'])
                    ->orWhere('branch_id', null) // fallback to head office
                    ->first();
                $validated['ck_settings_id'] = $setting ? $setting->id : null;
            }

            // Jika tipe check-in -> buat data baru
            if ($validated['check_clock_type'] === 'check-in') {
                // Cek apakah sudah ada record check-in hari ini
                $existingRecord = CheckClocks::where('employee_id', $validated['employee_id'])
                    ->where('check_clock_date', $validated['check_clock_date'])
                    ->first();

                if ($existingRecord) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Employee already checked in today'
                    ], 400);
                }

                // Auto-determine status berdasarkan setting times
                $status = $this->determineCheckInStatus(
                    $validated['ck_settings_id'],
                    $validated['check_clock_date'],
                    $validated['check_clock_time']
                );

                $clock = CheckClocks::create([
                    'employee_id'       => $validated['employee_id'],
                    'ck_settings_id'    => $validated['ck_settings_id'],
                    'branch_id'         => $validated['branch_id'],
                    'check_clock_type'  => 'check-in',
                    'check_clock_date'  => $validated['check_clock_date'],
                    'check_clock_time'  => $validated['check_clock_time'],
                    'check_out_time'    => null, 
                    'start_date'        => $validated['start_date'] ?? null,
                    'end_date'          => $validated['end_date'] ?? null,
                    'status'            => $status,
                    'approved'          => $validated['approved'] ?? null,
                    'location'          => $validated['location'] ?? null,
                    'address'           => $validated['address'] ?? null,
                    'latitude'          => $validated['latitude'] ?? null,
                    'longitude'         => $validated['longitude'] ?? null,
                    'photo'             => $validated['photo'] ?? null,
                ]);

                return response()->json([
                    'status' => 201,
                    'message' => 'Check-in recorded successfully',
                    'data' => $clock
                ], 201);
            }

            // Jika tipe check-out -> buat record check-out terpisah
            if ($validated['check_clock_type'] === 'check-out') {
                // Cek apakah sudah ada record check-in hari ini
                $checkInRecord = CheckClocks::where('employee_id', $validated['employee_id'])
                    ->where('check_clock_date', $validated['check_clock_date'])
                    ->where('check_clock_type', 'check-in')
                    ->whereNull('deleted_at')
                    ->first();

                if (!$checkInRecord) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'No check-in record found for today'
                    ], 400);
                }

                // Cek apakah sudah ada record check-out hari ini
                $existingCheckOut = CheckClocks::where('employee_id', $validated['employee_id'])
                    ->where('check_clock_date', $validated['check_clock_date'])
                    ->where('check_clock_type', 'check-out')
                    ->whereNull('deleted_at')
                    ->first();

                if ($existingCheckOut) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'Employee already checked out today'
                    ], 400);
                }

                // Buat record check-out baru
                $clock = CheckClocks::create([
                    'employee_id'       => $validated['employee_id'],
                    'ck_settings_id'    => $validated['ck_settings_id'],
                    'branch_id'         => $validated['branch_id'],
                    'check_clock_type'  => 'check-out',
                    'check_clock_date'  => $validated['check_clock_date'],
                    'check_clock_time'  => $checkInRecord->check_clock_time, // Copy check-in time
                    'check_out_time'    => $validated['check_out_time'] ?? $validated['check_clock_time'],
                    'start_date'        => $validated['start_date'] ?? null,
                    'end_date'          => $validated['end_date'] ?? null,
                    'status'            => $checkInRecord->status, // Use same status as check-in
                    'approved'          => $validated['approved'] ?? $checkInRecord->approved,
                    'location'          => $validated['location'] ?? $checkInRecord->location,
                    'address'           => $validated['address'] ?? $checkInRecord->address,
                    'latitude'          => $validated['latitude'] ?? $checkInRecord->latitude,
                    'longitude'         => $validated['longitude'] ?? $checkInRecord->longitude,
                    'photo'             => $validated['photo'] ?? null,
                ]);

                return response()->json([
                    'status' => 201,
                    'message' => 'Check-out recorded successfully',
                    'data' => $clock
                ], 201);
            }

            // For leave types, determine status based on approval
            $status = 'Waiting Approval';
            if (isset($validated['approved'])) {
                if ($validated['approved'] === true) {
                    $status = $validated['check_clock_type'] === 'annual-leave' ? 'Annual Leave' : 'Sick Leave';
                } elseif ($validated['approved'] === false) {
                    $status = '-';
                }
            }

            // Jika tipe cuti/sakit/absen, langsung simpan sebagai data baru
            $clockData = array_merge($validated, ['status' => $status]);
            $clock = CheckClocks::create($clockData);

            return response()->json([
                'status' => 201,
                'message' => 'Record created successfully',
                'data' => $clock
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating check clock record: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error creating record',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Tampilkan detail satu presensi
    public function show($id)
    {
        try {
            $checkClock = $this->getCheckClockDetails(
                DB::table('check_clocks as cc')
                    ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                    ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                    ->whereNull('cc.deleted_at')
                    ->where('cc.id', $id)
            )->first();

            if (!$checkClock) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Record not found'
                ], 404);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => $checkClock
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Approve attendance record
    public function approve($id)
    {
        try {
            $checkClock = CheckClocks::findOrFail($id);
            
            // Update approval status
            $checkClock->update([
                'approved' => true,
                'status' => $this->determineApprovedStatus($checkClock)
            ]);

            // Get fresh updated data
            $updated = $this->getCheckClockDetails(
                DB::table('check_clocks as cc')
                    ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                    ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                    ->whereNull('cc.deleted_at')
                    ->where('cc.id', $id)
            )->first();

            return response()->json([
                'status' => 200,
                'message' => 'Check clock approved successfully',
                'data' => $updated
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Check clock not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error approving check clock: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error approving record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Reject attendance record
    public function reject($id)
    {
        try {
            $checkClock = CheckClocks::findOrFail($id);
            
            // Update approval status
            $checkClock->update([
                'approved' => false,
                'status' => '-'
            ]);

            // Get fresh updated data
            $updated = $this->getCheckClockDetails(
                DB::table('check_clocks as cc')
                    ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                    ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                    ->whereNull('cc.deleted_at')
                    ->where('cc.id', $id)
            )->first();

            return response()->json([
                'status' => 200,
                'message' => 'Check clock rejected successfully',
                'data' => $updated
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Check clock not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error rejecting check clock: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error rejecting record',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Helper method to determine approved status based on type
    private function determineApprovedStatus($checkClock)
    {
        switch ($checkClock->check_clock_type) {
            case 'annual-leave':
                return 'Annual Leave';
            case 'sick-leave':
                return 'Sick Leave';
            case 'absent':
                return 'Absent';
            default:
                // For check-in/check-out, keep the original status logic
                return $this->determineCheckInStatus(
                    $checkClock->ck_settings_id,
                    $checkClock->check_clock_date,
                    $checkClock->check_clock_time
                );
        }
    }

    // Get employees for dropdown in add check clock form
    public function getEmployees()
    {
        try {
            $employees = DB::table('employees as e')
                ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                ->leftJoin('divisions as d', 'e.Division_id', '=', 'd.id')
                ->leftJoin('branches as b', 'e.Branch_id', '=', 'b.id')
                ->where('e.Status', 'Active') // Only active employees
                ->select([
                    'e.id',
                    'e.FirstName',
                    'e.LastName',
                    DB::raw('CONCAT("e"."FirstName", \' \', "e"."LastName") as full_name'),
                    'e.EmployeeID',
                    'p.name as position_name',
                    'd.name as division_name',
                    'b.name as branch_name',
                    'e.Branch_id',
                    'e.Division_id',
                    'e.Position_id'
                ])
                ->orderBy('e.FirstName')
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving employees',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function indexWithFilters(Request $request)
    {
        try {
            // Get filter parameters
            $month = $request->input('month');
            $year = $request->input('year');
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $positions = $request->input('positions', []);
            $statuses = $request->input('statuses', []);
            
            // Determine date range based on provided parameters
            if ($startDate && $endDate) {
                // Use custom date range
                $filterStartDate = $startDate;
                $filterEndDate = $endDate;
            } elseif ($month && $year) {
                // Use month/year filter
                $filterStartDate = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
                $filterEndDate = date('Y-m-t', strtotime($filterStartDate)); // Last day of the month
            } else {
                // Default to current month
                $currentMonth = now()->month;
                $currentYear = now()->year;
                $filterStartDate = "$currentYear-" . str_pad($currentMonth, 2, '0', STR_PAD_LEFT) . "-01";
                $filterEndDate = date('Y-m-t', strtotime($filterStartDate));
            }
            
            // Base query with date range
            $checkInRecords = DB::table('check_clocks as cc')
                ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                ->whereNull('cc.deleted_at')
                ->whereBetween('cc.check_clock_date', [$filterStartDate, $filterEndDate])
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

            // For each check-in record, find the corresponding check-out record
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

                $checkClocks->push((object)[
                    'id' => $checkIn->id,
                    'employee_id' => $checkIn->employee_id,
                    'employee_name' => $checkIn->employee_name,
                    'position' => $checkIn->position,
                    'date' => $checkIn->date,
                    'clock_in' => $checkIn->clock_in,
                    'clock_out' => $clockOut,
                    'work_hours' => $workHours,
                    'approved' => $checkIn->approved,
                    'status' => $checkIn->status,
                    'location' => $checkIn->location,
                    'detail_address' => $checkIn->detail_address,
                    'latitude' => $checkIn->latitude,
                    'longitude' => $checkIn->longitude,
                    'proof_of_attendance' => $checkIn->proof_of_attendance
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => $checkClocks,
                'filters_applied' => [
                    'month' => $month,
                    'year' => $year,
                    'positions' => $positions,
                    'statuses' => $statuses
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error retrieving records',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
