<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Checkout;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    // List semua checkout (admin)
    public function index()
    {
        $checkouts = Checkout::with('company')->orderBy('created_at', 'desc')->get();
        return response()->json($checkouts);
    }

    // Simpan checkout baru (create)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|string',
            'company_id' => 'required|exists:companies,id',
            'branches' => 'required|integer|min:0',
            'addon_employees' => 'required|integer|min:0',
            'subtotal' => 'required|integer|min:0',
            'tax' => 'required|integer|min:0',
            'total' => 'required|integer|min:0',
        ]);

        $checkout = Checkout::create($validated);
        return response()->json($checkout, 201);
    }

    // Detail checkout
    public function show($id)
    {
        $checkout = Checkout::with('company')->find($id);
        if (!$checkout) {
            return response()->json(['message' => 'Checkout not found'], 404);
        }
        return response()->json($checkout);
    }

    // Update checkout (misal status)
    public function update(Request $request, $id)
    {
        $checkout = Checkout::find($id);
        if (!$checkout) {
            return response()->json(['message' => 'Checkout not found'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,paid,failed',
        ]);

        $checkout->status = $validated['status'];
        $checkout->save();

        return response()->json($checkout);
    }

    // Hapus checkout (opsional)
    public function destroy($id)
    {
        $checkout = Checkout::find($id);
        if (!$checkout) {
            return response()->json(['message' => 'Checkout not found'], 404);
        }

        $checkout->delete();
        return response()->json(['message' => 'Checkout deleted']);
    }
}
