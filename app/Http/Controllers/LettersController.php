<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Letters;
use App\Models\User;
use App\Http\Requests\StoreLetterRequest;

class LettersController extends Controller
{
    public function index()
    {
        return Letters::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'letter_format_id' => 'required|exists:letter_formats,id',
            'user_id' => 'required|exists:users,id',
            'resignation_date' => 'nullable|date',
            'reason_resign' => 'nullable|string',
            'additional_notes' => 'nullable|string',
            'current_division' => 'nullable|string',
            'requested_division' => 'nullable|string',
            'reason_transfer' => 'nullable|string',
            'current_salary' => 'nullable|integer',
            'requested_salary' => 'nullable|integer',
            'reason_salary' => 'nullable|string',
            'leave_start' => 'nullable|date',
            'return_to_work' => 'nullable|date',
            'reason_for_leave' => 'nullable|string',
            'is_sent' => 'boolean',
            'is_approval' => 'boolean',
        ]);

        $letter = Letters::create($validated);
        return response()->json($letter, 201);

    }

    public function show(string $id)
    {
        return Letters::findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $letter = Letters::findOrFail($id);
        $letter->update($request->all());
        return response()->json($letter, 200);
    }

    public function destroy(string $id)
    {
        Letters::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
