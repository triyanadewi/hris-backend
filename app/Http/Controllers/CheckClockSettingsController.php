<?php

namespace App\Http\Controllers;

use App\Models\CheckClockSettings;
use App\Models\CheckClockSettingTimes;
use App\Models\Company;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class CheckClockSettingsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = CheckClockSettings::with(['times', 'company', 'branch']);
            
            // Filter by company_id if provided
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }
            
            // Filter by branch_id if provided
            if ($request->has('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            
            $settings = $query->get();

            return response()->json([
                'status' => 200,
                'message' => 'Success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching check clock settings: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error fetching settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Check clock settings store request:', $request->all());
            
            // Get company_id from authenticated user or default to 1 for development
            $companyId = 1; // Temporary hardcode, should get from auth user
            
            $validated = $request->validate([
                'id' => 'nullable|exists:check_clock_settings,id',
                'branch_id' => 'nullable|integer|exists:branches,id',
                'location_name' => 'nullable|string|max:255',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180', 
                'radius' => 'required|integer|min:1|max:10000',
                'address' => 'nullable|string',
                'times' => 'required|array|min:1',
                'times.*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'times.*.clock_in_start' => 'required|date_format:H:i',
                'times.*.clock_in_end' => 'required|date_format:H:i',
                'times.*.clock_in_on_time_limit' => 'required|date_format:H:i',
                'times.*.clock_out_start' => 'required|date_format:H:i',
                'times.*.clock_out_end' => 'required|date_format:H:i',
                'times.*.work_day' => 'required|boolean',
            ]);

            // Handle branch_id null case - set default to null if not provided
            $validated['branch_id'] = $request->get('branch_id', null);

            // Set location name based on branch or default
            $locationName = $validated['location_name'] ?? 'Head Office';
            if ($validated['branch_id']) {
                $branch = Branch::find($validated['branch_id']);
                if ($branch) {
                    $locationName = $branch->name;
                }
            }

            DB::beginTransaction();

            // Update atau create setting utama
            $requestId = $request->get('id', null);
            if ($requestId) {
                $setting = CheckClockSettings::findOrFail($requestId);
                $setting->update([
                    'company_id' => $companyId,
                    'branch_id' => $validated['branch_id'],
                    'location_name' => $locationName,
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'radius' => $validated['radius']
                ]);
            } else {
                // Check for duplicate settings for the same company/branch
                $existingSetting = CheckClockSettings::where('company_id', $companyId)
                    ->where('branch_id', $validated['branch_id'])
                    ->first();
                
                if ($existingSetting) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 422,
                        'message' => 'Check clock setting already exists for this company/branch combination'
                    ], 422);
                }

                $setting = CheckClockSettings::create([
                    'company_id' => $companyId,
                    'branch_id' => $validated['branch_id'],
                    'location_name' => $locationName,
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'radius' => $validated['radius']
                ]);
            }

            // Hapus times yang ada untuk setting ini, lalu buat ulang
            CheckClockSettingTimes::where('ck_settings_id', $setting->id)->forceDelete();

            foreach ($validated['times'] as $timeData) {
                CheckClockSettingTimes::create([
                    'ck_settings_id' => $setting->id,
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

            Log::info('Check clock settings saved successfully:', ['setting_id' => $setting->id]);

            return response()->json([
                'status' => 200,
                'message' => 'Check clock settings saved successfully',
                'data' => $setting->load('times')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation error:', $e->errors());
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving check clock settings:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Failed to save check clock settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $setting = CheckClockSettings::findOrFail($id);
            
            $validated = $request->validate([
                'company_id' => 'sometimes|required|exists:companies,id',
                'branch_id' => 'nullable|exists:branches,id',
                'location_name' => 'sometimes|required|string|max:255',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'radius' => 'nullable|integer|min:1|max:10000',
                'times' => 'sometimes|array',
                'times.*.day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
                'times.*.clock_in_start' => 'required|date_format:H:i',
                'times.*.clock_in_end' => 'required|date_format:H:i', 
                'times.*.clock_in_on_time_limit' => 'required|date_format:H:i',
                'times.*.clock_out_start' => 'required|date_format:H:i',
                'times.*.clock_out_end' => 'required|date_format:H:i',
                'times.*.work_day' => 'required|boolean',
            ]);

            // Validate that branch belongs to company if both are provided
            if (isset($validated['branch_id']) && isset($validated['company_id'])) {
                $branch = Branch::where('id', $validated['branch_id'])
                              ->where('company_id', $validated['company_id'])
                              ->first();
                if (!$branch) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'Branch does not belong to the specified company'
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Update setting utama
            $updateData = array_filter([
                'company_id' => $validated['company_id'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'location_name' => $validated['location_name'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'radius' => $validated['radius'] ?? null
            ], function($value) {
                return $value !== null;
            });

            if (!empty($updateData)) {
                $setting->update($updateData);
            }

            // Update times jika ada
            if (isset($validated['times'])) {
                CheckClockSettingTimes::where('ck_settings_id', $setting->id)->forceDelete();
                
                foreach ($validated['times'] as $timeData) {
                    CheckClockSettingTimes::create([
                        'ck_settings_id' => $setting->id,
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
                'message' => 'Check clock settings updated successfully',
                'data' => $setting->load('times')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Check clock setting not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update check clock settings error:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update check clock settings',
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
            
            DB::beginTransaction();
            
            // Hapus times terkait terlebih dahulu
            CheckClockSettingTimes::where('ck_settings_id', $setting->id)->delete();
            
            // Hapus setting utama
            $setting->delete();
            
            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Check clock setting deleted successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Check clock setting not found'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting check clock setting:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete check clock setting',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get or create default settings for a company
     */
    public function getOrCreateDefault(Request $request)
    {
        try {
            $validated = $request->validate([
                'company_id' => 'required|exists:companies,id',
                'branch_id' => 'nullable|exists:branches,id'
            ]);

            // Check if setting already exists
            $setting = CheckClockSettings::with('times')
                ->where('company_id', $validated['company_id'])
                ->where('branch_id', $validated['branch_id'])
                ->first();

            if (!$setting) {
                // Create default setting
                DB::beginTransaction();
                try {
                    $setting = CheckClockSettings::create([
                        'company_id' => $validated['company_id'],
                        'branch_id' => $validated['branch_id'],
                        'location_name' => $validated['branch_id'] ? 'Branch Office' : 'Head Office',
                        'latitude' => null,
                        'longitude' => null,
                        'radius' => 100
                    ]);

                    // Create default working hours for weekdays
                    $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
                    foreach ($weekdays as $day) {
                        CheckClockSettingTimes::create([
                            'ck_settings_id' => $setting->id,
                            'day' => $day,
                            'clock_in_start' => '07:00:00',
                            'clock_in_end' => '09:00:00',
                            'clock_in_on_time_limit' => '08:00:00',
                            'clock_out_start' => '17:00:00',
                            'clock_out_end' => '19:00:00',
                            'work_day' => true,
                        ]);
                    }

                    // Create weekend settings (non-working days)
                    $weekends = ['Saturday', 'Sunday'];
                    foreach ($weekends as $day) {
                        CheckClockSettingTimes::create([
                            'ck_settings_id' => $setting->id,
                            'day' => $day,
                            'clock_in_start' => '08:00:00',
                            'clock_in_end' => '10:00:00',
                            'clock_in_on_time_limit' => '09:00:00',
                            'clock_out_start' => '17:00:00',
                            'clock_out_end' => '19:00:00',
                            'work_day' => false,
                        ]);
                    }

                    DB::commit();
                    $setting->load('times');
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            return response()->json([
                'status' => 200,
                'message' => 'Default settings retrieved successfully',
                'data' => $setting
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error getting default check clock settings:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Failed to get default settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}