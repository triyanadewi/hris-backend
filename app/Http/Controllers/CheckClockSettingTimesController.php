<?php

namespace App\Http\Controllers;

use App\Models\CheckClockSettingTimes;
use Illuminate\Http\Request;

class CheckClockSettingTimesController extends Controller
{
    public function index()
    {
        $times = CheckClockSettingTimes::with('checkClockSettings')->get();
        return response()->json($times);
    }

    // Menyimpan data waktu baru
   public function store(Request $request)
    {
        $validated = $request->validate([
            'ck_settings_id'            => 'required|exists:check_clock_settings,id',
            'day'                       => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'clock_in_start'            => 'required|date_format:H:i:s',
            'clock_in_end'              => 'required|date_format:H:i:s',
            'clock_in_on_time_limit'    => 'required|date_format:H:i:s',
            'clock_out_start'           => 'required|date_format:H:i:s',
            'clock_out_end'             => 'required|date_format:H:i:s',
            'work_day'                  => 'required|boolean',
        ]);

        $time = CheckClockSettingTimes::create($validated);

        return response()->json($time, 201);
    }

    // Menampilkan satu data berdasarkan ID
    public function show($id)
    {
        $time = CheckClockSettingTimes::with('checkClockSettings')->findOrFail($id);
        return response()->json($time);
    }

    // Update data waktu
    public function update(Request $request, $id)
    {
        $time = CheckClockSettingTimes::findOrFail($id);

        $validated = $request->validate([
            'ck_settings_id'           => 'sometimes|exists:check_clock_settings,id',
            'day'                      => 'sometimes|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'clock_in_start'           => 'sometimes|date_format:H:i:s',
            'clock_in_end'             => 'sometimes|date_format:H:i:s',
            'clock_in_on_time_limit'   => 'sometimes|date_format:H:i:s',
            'clock_out_start'          => 'sometimes|date_format:H:i:s',
            'clock_out_end'            => 'sometimes|date_format:H:i:s',
            'work_day'                 => 'sometimes|boolean',
        ]);

        $time->update($validated);

        return response()->json($time);
    }

    // Menghapus data (soft delete)
    public function destroy($id)
    {
        $time = CheckClockSettingTimes::findOrFail($id);
        $time->delete();

        return response()->json(['message' => 'CheckClock setting time deleted']);
    }

}
