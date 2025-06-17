<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $xenditService;

    public function __construct(XenditService $xenditService)
    {
        $this->xenditService = $xenditService;
    }

    /**
     * Create payment for an order
     */
    public function createPayment(Request $request, $orderId)
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:bca,bni,bri,mandiri,gopay,ovo,dana,qris,credit_card',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Get order
            $order = Order::with(['company', 'packageBenefit.package'])->findOrFail($orderId);

            if ($order->status !== 'pending') {
                return response()->json(['error' => 'Order is not pending'], 400);
            }

            // Verify amount matches order total
            if ($validated['amount'] != $order->total) {
                return response()->json(['error' => 'Amount mismatch'], 400);
            }

            // Check if payment already exists for this order
            $existingPayment = Payment::where('order_id', $orderId)
                ->whereIn('status', ['pending', 'paid'])
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'id' => $existingPayment->id,
                    'status' => $existingPayment->status,
                    'payment_url' => $existingPayment->payment_url,
                    'va_number' => $existingPayment->va_number,
                    'qr_code' => $existingPayment->qr_code,
                ]);
            }

            // Create payment record
            $payment = Payment::create([
                'order_id' => $order->id,
                'company_id' => $order->company_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'description' => $validated['description'] ?? "Payment for {$order->packageBenefit->package->name}",
                'status' => 'pending',
            ]);

            // Generate external_id yang unik
            $externalId = "order_{$order->id}_payment_{$payment->id}_" . time();

            // Prepare payment data
            $paymentData = [
                'external_id' => $externalId,
                'amount' => $validated['amount'],
                'description' => $payment->description,
                'customer' => [
                    'given_names' => $order->company_name,
                    'email' => $order->email,
                    'mobile_number' => $this->formatPhoneNumber($order->phone_number),
                ],
            ];

            // Create payment with Xendit based on method
            $xenditResponse = $this->createXenditPayment($validated['payment_method'], $paymentData);

            if (!$xenditResponse) {
                throw new \Exception('Failed to create payment with Xendit');
            }

            // Update payment record with Xendit response
            $payment->update([
                'external_id' => $externalId,
                'xendit_id' => $xenditResponse['id'] ?? null,
                'payment_url' => $xenditResponse['payment_url'] ?? null,
                'va_number' => $xenditResponse['va_number'] ?? null,
                'qr_code' => $xenditResponse['qr_code'] ?? null,
                'expires_at' => isset($xenditResponse['expiry_date']) ? 
                    \Carbon\Carbon::parse($xenditResponse['expiry_date']) : 
                    now()->addHours(24),
            ]);

            DB::commit();

            return response()->json([
                'id' => $payment->id,
                'status' => $payment->status,
                'payment_url' => $payment->payment_url,
                'va_number' => $payment->va_number,
                'qr_code' => $payment->qr_code,
                'expires_at' => $payment->expires_at,
                'message' => 'Payment created successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Payment creation failed: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Failed to create payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function getPayment($orderId, $paymentId = null)
    {
        $query = Payment::with(['order.packageBenefit.package', 'order.company']);

        if ($paymentId) {
            $payment = $query->where('order_id', $orderId)->findOrFail($paymentId);
        } else {
            $payment = $query->where('order_id', $orderId)->latest()->firstOrFail();
        }

        return response()->json([
            'id' => $payment->id,
            'order_id' => $payment->order_id,
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'status' => $payment->status,
            'payment_url' => $payment->payment_url,
            'va_number' => $payment->va_number,
            'qr_code' => $payment->qr_code,
            'expires_at' => $payment->expires_at,
            'paid_at' => $payment->paid_at,
            'order' => [
                'id' => $payment->order->id,
                'package_name' => $payment->order->packageBenefit->package->name,
                'company_name' => $payment->order->company_name,
                'total' => $payment->order->total,
            ],
        ]);
    }

    /**
     * Handle Xendit webhook
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Verify webhook (optional but recommended)
            $webhookToken = $request->header('x-callback-token');
            if ($webhookToken !== config('xendit.webhook_token')) {
                Log::warning('Invalid webhook token');
                return response()->json(['error' => 'Invalid token'], 401);
            }

            $payload = $request->all();
            Log::info('Xendit webhook received', $payload);

            // Extract external_id
            $externalId = $payload['external_id'] ?? null;
            if (!$externalId) {
                Log::error('No external_id in webhook payload');
                return response()->json(['error' => 'No external_id'], 400);
            }

            // Find payment by external_id
            $payment = Payment::where('external_id', $externalId)->first();
            if (!$payment) {
                Log::error('Payment not found for external_id: ' . $externalId);
                return response()->json(['error' => 'Payment not found'], 404);
            }

            // Update payment status based on webhook
            $status = strtolower($payload['status'] ?? '');
            
            switch ($status) {
                case 'paid':
                case 'settled':
                    $this->handleSuccessfulPayment($payment, $payload);
                    break;
                    
                case 'expired':
                case 'failed':
                    $this->handleFailedPayment($payment, $payload);
                    break;
                    
                default:
                    Log::info('Unhandled payment status: ' . $status);
                    break;
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Webhook handling failed: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook handling failed'], 500);
        }
    }

    /**
     * Check payment status manually
     */
    public function checkPaymentStatus($orderId, $paymentId)
    {
        try {
            $payment = Payment::where('order_id', $orderId)->findOrFail($paymentId);
            
            if ($payment->status === 'paid') {
                return response()->json([
                    'status' => 'paid',
                    'paid_at' => $payment->paid_at,
                ]);
            }

            // Check with Xendit if payment is still pending
            if ($payment->xendit_id && $payment->status === 'pending') {
                $xenditStatus = $this->xenditService->getPaymentStatus($payment->xendit_id, $payment->payment_method);
                
                if ($xenditStatus && isset($xenditStatus['status'])) {
                    $status = strtolower($xenditStatus['status']);
                    
                    if (in_array($status, ['paid', 'settled'])) {
                        $this->handleSuccessfulPayment($payment, $xenditStatus);
                        return response()->json([
                            'status' => 'paid',
                            'paid_at' => $payment->fresh()->paid_at,
                        ]);
                    }
                }
            }

            return response()->json([
                'status' => $payment->status,
                'expires_at' => $payment->expires_at,
            ]);

        } catch (\Exception $e) {
            Log::error('Payment status check failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to check payment status'], 500);
        }
    }

    /**
     * Create payment with Xendit based on method type
     */
    private function createXenditPayment($paymentMethod, $paymentData)
    {
        switch ($paymentMethod) {
            case 'bca':
            case 'bni':
            case 'bri':
            case 'mandiri':
                return $this->xenditService->createVirtualAccount($paymentMethod, $paymentData);
                
            case 'gopay':
            case 'ovo':
            case 'dana':
                return $this->xenditService->createEWallet($paymentMethod, $paymentData);
                
            case 'qris':
                return $this->xenditService->createQRIS($paymentData);
                
            case 'credit_card':
                return $this->xenditService->createCreditCardPayment($paymentData);
                
            default:
                throw new \Exception('Unsupported payment method: ' . $paymentMethod);
        }
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment($payment, $payload)
    {
        DB::transaction(function () use ($payment, $payload) {
            // Update payment
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'xendit_response' => $payload,
            ]);

            // Update order
            $payment->order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // TODO: Create subscription or activate service
            Log::info('Payment successful for order: ' . $payment->order_id);
        });
    }

    /**
     * Handle failed payment
     */
    private function handleFailedPayment($payment, $payload)
    {
        $payment->update([
            'status' => 'failed',
            'xendit_response' => $payload,
        ]);

        Log::info('Payment failed for order: ' . $payment->order_id);
    }

    /**
     * Format phone number for Xendit
     */
    private function formatPhoneNumber($phone)
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add +62 prefix if not present
        if (substr($phone, 0, 2) === '62') {
            return '+' . $phone;
        } elseif (substr($phone, 0, 1) === '0') {
            return '+62' . substr($phone, 1);
        } else {
            return '+62' . $phone;
        }
    }
}