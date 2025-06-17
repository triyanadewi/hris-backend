<?php

namespace App\Http\Controllers;

use App\Models\Letter_formats;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LetterFormatsController extends Controller
{
    public function index(){
        return Letter_formats::all();
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name'=> 'required|string',
            'content'=>'required|string',
            'status' => 'required|integer',
        ]);

        $letter_formats = Letter_formats::create($validated);
        return response()->json($letter_formats,201);
    }

    public function show(string $id)
    {
        return Letter_formats::findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $letter_formats = Letter_formats::findOrFail($id);
        $letter_formats->update($request->all());
        return response()->json($letter_formats, 200);
    }

    public function destroy(string $id)
    {
        Letter_formats::destroy($id);
        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
