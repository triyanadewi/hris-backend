<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class FilterController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::query();

        if ($request->has('status')) {
            $query->whereIn('status', $request->input('status'));
        }

        if ($request->has('division')) {
            $query->whereIn('division', $request->input('division'));
        }

        if ($request->has('position')) {
            $query->whereIn('position', $request->input('position'));
        }

        if ($request->has('branch')) {
            $query->whereIn('branch', $request->input('branch'));
        }

        return response()->json($query->get());
    }
}
