<?php

namespace App\Http\Controllers;

use App\Models\CheckClockSettings;
use App\Models\CheckClockSettingTimes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CheckClockSettingsController extends Controller
{
    public function index()
    {
        try {
            // ✅ Ambil data terbaru atau buat default jika belum ada
            $setting = CheckClockSettings::with('times')->first();
            
            if (!$setting) {
                // Buat setting default jika belum ada
                $setting = CheckClockSettings::create([
                    'latitude' => null,
                    'longitude' => null,
                    'radius' => 100
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Work settings store request:', $request->all()); // ✅ Sekarang bisa digunakan
            
            $validated = $request->validate([
                'id' => 'nullable|exists:check_clock_settings,id',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric', 
                'radius' => 'nullable|numeric',
                'times' => 'required|array',
                'times.*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'times.*.clock_in_start' => 'required|date_format:H:i',
                'times.*.clock_in_end' => 'required|date_format:H:i',
                'times.*.clock_in_on_time_limit' => 'required|date_format:H:i',
                'times.*.clock_out_start' => 'required|date_format:H:i',
                'times.*.clock_out_end' => 'required|date_format:H:i',
                'times.*.work_day' => 'required|boolean',
            ]);

            DB::beginTransaction();

            // ✅ Update atau create setting utama
            if ($validated['id']) {
                $setting = CheckClockSettings::findOrFail($validated['id']);
                $setting->update([
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'radius' => $validated['radius']
                ]);
            } else {
                $setting = CheckClockSettings::create([
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'radius' => $validated['radius']
                ]);
            }

            // ✅ Hapus hanya times untuk setting ini, lalu buat ulang
            $setting->times()->delete();

            foreach ($validated['times'] as $timeData) {
                $setting->times()->create([
                    'day' => $timeData['day'],
                    'clock_in_start' => $timeData['clock_in_start'] . ':00',
                    'clock_in_end' => $timeData['clock_in_end'] . ':00',
                    'clock_in_on_time_limit' => $timeData['clock_in_on_time_limit'] . ':00',
                    'clock_out_start' => $timeData['clock_out_start'] . ':00',
                    'clock_out_end' => $timeData['clock_out_end'] . ':00',
                    'work_day' => $timeData['work_day'],
                ]);
            }

            DB::commit();

            Log::info('Work settings saved successfully:', ['setting_id' => $setting->id]); // ✅ Bisa digunakan

            return response()->json([
                'status' => 200,
                'message' => 'Work settings saved successfully',
                'data' => $setting->load('times')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error:', $e->errors()); // ✅ Bisa digunakan
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving work settings:', ['error' => $e->getMessage()]); // ✅ Bisa digunakan
            return response()->json([
                'status' => 500,
                'message' => 'Failed to save work settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ✅ Tambahkan method update terpisah
    public function update(Request $request, $id)
    {
        try {
            $setting = CheckClockSettings::findOrFail($id);
            
            $validated = $request->validate([
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric',
                'times' => 'sometimes|array',
                'times.*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'times.*.clock_in_start' => 'required|date_format:H:i',
                'times.*.clock_in_end' => 'required|date_format:H:i', 
                'times.*.clock_in_on_time_limit' => 'required|date_format:H:i',
                'times.*.clock_out_start' => 'required|date_format:H:i',
                'times.*.clock_out_end' => 'required|date_format:H:i',
                'times.*.work_day' => 'required|boolean',
            ]);

            DB::beginTransaction();

            // Update setting utama
            $setting->update([
                'latitude' => $validated['latitude'] ?? $setting->latitude,
                'longitude' => $validated['longitude'] ?? $setting->longitude,
                'radius' => $validated['radius'] ?? $setting->radius
            ]);

            // Update times jika ada
            if (isset($validated['times'])) {
                $setting->times()->delete();
                
                foreach ($validated['times'] as $timeData) {
                    $setting->times()->create([
                        'day' => $timeData['day'],
                        'clock_in_start' => $timeData['clock_in_start'] . ':00',
                        'clock_in_end' => $timeData['clock_in_end'] . ':00',
                        'clock_in_on_time_limit' => $timeData['clock_in_on_time_limit'] . ':00',
                        'clock_out_start' => $timeData['clock_out_start'] . ':00',
                        'clock_out_end' => $timeData['clock_out_end'] . ':00',
                        'work_day' => $timeData['work_day'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Work settings updated successfully',
                'data' => $setting->load('times')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update work settings error:', ['error' => $e->getMessage()]); // ✅ Bisa digunakan
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update work settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $setting = CheckClockSettings::with('times')->findOrFail($id);
            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => $setting
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Setting not found'
            ], 404);
        }
    }

    public function destroy($id)
    {
        try {
            $setting = CheckClockSettings::findOrFail($id);
            $setting->times()->delete(); // Hapus times terkait
            $setting->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Setting deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}