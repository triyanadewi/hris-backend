<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class XenditController extends Controller
{
    public function createInvoice(Request $request)
    {
        $response = Http::withToken(env('XENDIT_SECRET_KEY'))
            ->post('https://api.xendit.co/v2/invoices', [
                'external_id' => 'invoice-' . Str::random(10),
                'amount' => (int) $request->amount,
                'payer_email' => $request->email,
                'description' => 'Pembayaran Plan ' . $request->plan,
                'success_redirect_url' => 'https://yourdomain.com/success',
            ]);

        if ($response->successful()) {
            return response()->json([
                'invoice_url' => $response['invoice_url'],
            ]);
        }

        return response()->json([
            'error' => $response->body(),
        ], 500);
    }
}
