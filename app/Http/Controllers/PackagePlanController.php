<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PackagePlan;

class PackagePlanController extends Controller
{
    public function index()
    {
        return response()->json(PackagePlan::all());
    }
}
