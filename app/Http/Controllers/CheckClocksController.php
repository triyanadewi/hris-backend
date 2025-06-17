<?php

namespace App\Http\Controllers;

use Log;
use App\Models\CheckClocks;
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
            DB::raw("CONCAT(e.\"FirstName\", ' ', e.\"LastName\") as employee_name"),
            'e.Position_id',
            'p.name as position',
            'cc.check_clock_date as date',
            'cc.check_clock_time as clock_in',
            'cc.check_out_time as clock_out',
            DB::raw("CASE 
                WHEN cc.check_clock_time IS NOT NULL AND cc.check_out_time IS NOT NULL 
                THEN CONCAT(
                    EXTRACT(HOUR FROM (cc.check_out_time::time - cc.check_clock_time::time))::text,
                    'h ',
                    EXTRACT(MINUTE FROM (cc.check_out_time::time - cc.check_clock_time::time))::text,
                    'm'
                )
                ELSE null 
            END as work_hours"),
            'cc.approved',
            DB::raw("CASE 
                WHEN cc.check_clock_type = 'annual-leave' AND cc.approved = true 
                    AND cc.check_clock_date BETWEEN cc.start_date AND cc.end_date THEN 'Annual Leave'
                    
                WHEN cc.check_clock_type = 'sick-leave' AND cc.approved = true 
                    AND cc.check_clock_date BETWEEN cc.start_date AND cc.end_date THEN 'Sick Leave'

                WHEN cc.approved IS NULL THEN 'Waiting Approval'
                WHEN cc.approved = false THEN '-'
                
                -- Jika tidak ada check-in
                WHEN cc.check_clock_time IS NULL THEN 'Absent'

                WHEN cc.check_clock_type = 'annual-leave' AND cc.approved = true THEN 'Annual Leave'
                WHEN cc.check_clock_type = 'sick-leave' AND cc.approved = true THEN 'Sick Leave'
                            
                -- Cek clock in setelah jam berakhir (langsung Absent)
                WHEN EXISTS (
                    SELECT 1 FROM check_clock_setting_times ccst
                    WHERE ccst.ck_settings_id = cc.ck_settings_id
                    AND ccst.day = TRIM(TO_CHAR(cc.check_clock_date, 'Day'))
                    AND ccst.work_day = true
                    AND cc.check_clock_time::time > ccst.clock_in_end::time
                ) THEN 'Absent'
                
                -- Cek Late berdasarkan setting times
                WHEN EXISTS (
                    SELECT 1 FROM check_clock_setting_times ccst
                    WHERE ccst.ck_settings_id = cc.ck_settings_id
                    AND ccst.day = TRIM(TO_CHAR(cc.check_clock_date, 'Day'))
                    AND ccst.work_day = true
                    AND cc.check_clock_time::time > ccst.clock_in_on_time_limit::time
                ) THEN 'Late'
                
                ELSE 'On Time'
            END as status"),
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

    // Helper method untuk update check-in
    private function updateCheckIn($checkClock, $validated)
    {
        // Validasi khusus untuk check-in
        if (!isset($validated['check_clock_time'])) {
            throw new \Exception('Check clock time is required for check-in');
        }

        // Recalculate status berdasarkan waktu baru
        $newStatus = $this->determineCheckInStatus(
            $checkClock->ck_settings_id,
            $checkClock->check_clock_date,
            $validated['check_clock_time']
        );

        $checkClock->update([
            'check_clock_type' => 'check-in',
            'check_clock_time' => $validated['check_clock_time'],
            'location' => $validated['location'] ?? $checkClock->location,
            'address' => $validated['address'] ?? $checkClock->address,
            'latitude' => $validated['latitude'] ?? $checkClock->latitude,
            'longitude' => $validated['longitude'] ?? $checkClock->longitude,
            'photo' => $validated['photo'] ?? $checkClock->photo,
            'status' => $newStatus,
            'approved' => $validated['approved'] ?? $checkClock->approved
        ]);
    }

    // Helper method untuk update check-out
    private function updateCheckOut($checkClock, $validated)
    {
        // Validasi khusus untuk check-out
        if (!isset($validated['check_out_time'])) {
            throw new \Exception('Check out time is required for check-out');
        }

        // Pastikan sudah ada check-in time
        if (!$checkClock->check_clock_time) {
            throw new \Exception('Cannot check-out without check-in time');
        }

        // Status tetap berdasarkan check-in, tidak berubah karena check-out
        $newStatus = $checkClock->status;

        $checkClock->update([
            'check_clock_type' => 'check-out',
            'check_out_time' => $validated['check_out_time'],
            'location' => $validated['location'] ?? $checkClock->location,
            'address' => $validated['address'] ?? $checkClock->address,
            'latitude' => $validated['latitude'] ?? $checkClock->latitude,
            'longitude' => $validated['longitude'] ?? $checkClock->longitude,
            'photo' => $validated['photo'] ?? $checkClock->photo,
            'status' => $newStatus, // Tetap gunakan status sebelumnya
            'approved' => $validated['approved'] ?? $checkClock->approved
        ]);
    }

    // Helper method untuk update cuti/sakit
    private function updateLeave($checkClock, $validated)
    {
        // Validasi khusus untuk cuti
        if (!isset($validated['start_date']) || !isset($validated['end_date'])) {
            throw new \Exception('Start date and end date are required for leave');
        }

        $status = $validated['approved'] === true ? 
            ($validated['update_type'] === 'annual-leave' ? 'Annual Leave' : 'Sick Leave') : 
            'Waiting Approval';

        $checkClock->update([
            'check_clock_type' => $validated['update_type'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'check_clock_time' => null, // Reset clock times untuk leave
            'check_out_time' => null,
            'status' => $status,
            'approved' => $validated['approved'] ?? null,
            'location' => $validated['location'] ?? $checkClock->location,
            'address' => $validated['address'] ?? $checkClock->address,
            'photo' => $validated['photo'] ?? $checkClock->photo
        ]);
    }

    // Helper method untuk update absent
    private function updateAbsent($checkClock, $validated)
    {
        $checkClock->update([
            'check_clock_type' => 'absent',
            'check_clock_time' => null,
            'check_out_time' => null,
            'start_date' => null,
            'end_date' => null,
            'status' => 'Absent',
            'approved' => $validated['approved'] ?? false,
            'location' => $validated['location'] ?? $checkClock->location,
            'address' => $validated['address'] ?? $checkClock->address,
            'photo' => $validated['photo'] ?? $checkClock->photo
        ]);
    }

    public function index()
    {
        try {
            $today = now()->toDateString();
            $baseQuery = DB::table('check_clocks as cc')
                ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                ->whereNull('cc.deleted_at')
                ->whereDate('cc.check_clock_date', '=', $today);

            $checkClocks = $this->getCheckClockDetails($baseQuery)
                ->distinct()
                ->orderBy('cc.id')
                ->get();

            // Add absent employees
            $allEmployees = DB::table('employees as e')
                ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id') // ✅ Join dengan positions
                ->select('e.*', 'p.name as position_name')
                ->whereNotIn('e.id', $checkClocks->pluck('employee_id')->unique())
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

    // Default ck_settings_id ke 1 jika tidak dikirim
    $validated['ck_settings_id'] = $validated['ck_settings_id'] ?? 1;

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

        return response()->json($clock, 201);
    }

    // Jika tipe check-out -> update record check-in pada hari yang sama
    if ($validated['check_clock_type'] === 'check-out') {
        $clock = CheckClocks::where('employee_id', $validated['employee_id'])
            ->where('check_clock_date', $validated['check_clock_date'])
            ->whereNotNull('check_clock_time') // pastikan sudah check-in
            ->whereNull('check_out_time')      // belum check-out
            ->first();

        if ($clock) {
            // Status tetap sama dengan check-in, tidak berubah karena check-out
            $clock->update([
                'check_out_time' => $validated['check_clock_time'],
                'check_clock_type' => 'check-out',
                // status tetap sama, tidak diubah
            ]);

            return response()->json($clock, 200);
        } else {
            return response()->json([
                'status' => 400,
                'message' => 'No check-in record found or already checked out'
            ], 400);
        }
    }

    // Jika tipe cuti/sakit/absen, langsung simpan sebagai data baru
    $clock = CheckClocks::create($validated);

    return response()->json($clock, 201);
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

    // Update data presensi
public function update(Request $request, $id)
{
    try {
        $checkClock = CheckClocks::findOrFail($id);

        // Jika request hanya untuk mendapatkan data (untuk form edit)
        if ($request->method() === 'GET' || $request->has('get_data')) {
            $currentData = $this->getCheckClockDetails(
                DB::table('check_clocks as cc')
                    ->join('employees as e', 'cc.employee_id', '=', 'e.id')
                    ->leftJoin('positions as p', 'e.Position_id', '=', 'p.id')
                    ->whereNull('cc.deleted_at')
                    ->where('cc.id', $id)
            )->first();

            return response()->json([
                'status' => 200,
                'message' => 'Current data retrieved successfully',
                'data' => [
                    'current_record' => $currentData,
                    'employee_info' => [
                        'id' => $currentData->employee_id,
                        'name' => $currentData->employee_name,
                        'position' => $currentData->position
                    ],
                    'available_types' => [
                        'check-in' => 'Clock In',
                        'check-out' => 'Clock Out',
                        'annual-leave' => 'Annual Leave',
                        'sick-leave' => 'Sick Leave',
                        'absent' => 'Absent'
                    ]
                ]
            ]);
        }

        // Validasi untuk update data
        $validated = $request->validate([
            'ck_settings_id'    => 'nullable|exists:check_clock_settings,id',
            'check_clock_type'  => 'nullable|in:check-in,check-out,annual-leave,sick-leave,absent',
            'check_clock_time'  => 'nullable|date_format:H:i:s',
            'check_out_time'    => 'nullable|date_format:H:i:s',
            'start_date'        => 'nullable|date',
            'end_date'          => 'nullable|date',
            'location'          => 'nullable|string',
            'address'           => 'nullable|string',
            'latitude'          => 'nullable|numeric',
            'longitude'         => 'nullable|numeric',
            'approved'          => 'nullable|boolean',
            'photo'             => 'nullable|string'
        ]);

        // Default ck_settings_id ke 1 jika tidak dikirim
        $validated['ck_settings_id'] = $validated['ck_settings_id'] ?? 1;

        // Jika tipe check-out, update hanya jam keluar dan tipe
        if (isset($validated['check_clock_type']) && $validated['check_clock_type'] === 'check-out') {
            $checkClock->update([
                'check_out_time' => $validated['check_clock_time'] ?? $checkClock->check_out_time,
                'check_clock_type' => 'check-out',
                'location' => $validated['location'] ?? $checkClock->location,
                'address' => $validated['address'] ?? $checkClock->address,
                'latitude' => $validated['latitude'] ?? $checkClock->latitude,
                'longitude' => $validated['longitude'] ?? $checkClock->longitude,
                'approved' => $validated['approved'] ?? $checkClock->approved,
                'photo' => $validated['photo'] ?? $checkClock->photo,
                'ck_settings_id' => $validated['ck_settings_id'] ?? $checkClock->ck_settings_id,
            ]);
        } else {
            // Jika tipe selain check-out (check-in, cuti, sakit, absen), update semua field yang mungkin
            $checkClock->update($validated);
        }

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
            'message' => 'Check clock updated successfully',
            'data' => $updated
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status' => 404,
            'message' => 'Check clock not found'
        ], 404);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 422,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Error updating record',
            'error' => $e->getMessage()
        ], 500);
    }
}


    // Hapus data presensi (soft delete)
    public function destroy($id)
    {
        try {
            $checkClock = CheckClocks::findOrFail($id);
            
            // Check if there are any dependencies before deleting
            // Add any necessary checks here
            
            $checkClock->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Check clock deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Check clock not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error deleting record',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
