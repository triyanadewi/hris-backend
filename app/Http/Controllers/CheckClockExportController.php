<?php

namespace App\Http\Controllers;
use App\Exports\CheckClocksExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckClockExportController extends Controller
{
    public function export(Request $request)
    {
        try {
            $filters = [];
            
            // Get filter parameters from request
            if ($request->has('month')) {
                $filters['month'] = (int) $request->get('month');
            }
            
            if ($request->has('year')) {
                $filters['year'] = (int) $request->get('year');
            }
            
            if ($request->has('positions')) {
                $positions = $request->get('positions');
                // Handle both single values and arrays
                $filters['positions'] = is_array($positions) ? $positions : [$positions];
            }
            
            if ($request->has('statuses')) {
                $statuses = $request->get('statuses');
                // Handle both single values and arrays
                $filters['statuses'] = is_array($statuses) ? $statuses : [$statuses];
            }

            // Generate filename with current date and filters
            $filename = 'check_clocks_' . date('Y-m-d_H-i-s');
            
            if (!empty($filters['month']) && !empty($filters['year'])) {
                $filename .= '_' . $filters['year'] . '-' . str_pad($filters['month'], 2, '0', STR_PAD_LEFT);
            }
            
            $filename .= '.xlsx';
            
            // Log for debugging
            Log::info('Export request received', [
                'filters' => $filters,
                'filename' => $filename
            ]);
            
            $export = new \App\Exports\CheckClocksExportFixed($filters);
            
            // Store the file first
            Excel::store($export, $filename, 'local');
            $filePath = storage_path('app/private/' . $filename);
            
            // Return the file as a response with proper CORS headers
            $response = response()->download($filePath, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0',
                'Access-Control-Allow-Origin' => 'http://localhost:3000',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Expose-Headers' => 'Content-Disposition, Content-Type, Content-Length'
            ]);
            
            // Clean up the temporary file after sending
            register_shutdown_function(function () use ($filePath) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            });
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Export error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'status' => 500,
                'message' => 'Export failed: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
