<?php

namespace App\Http\Controllers;
use App\Exports\CheckClocksExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class CheckClockExportController extends Controller
{
    public function export()
    {
        return Excel::download(new CheckClocksExport, 'check_clocks.xlsx');
    }
}
