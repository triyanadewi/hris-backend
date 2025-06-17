<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'company_id',
        'external_id',
        'xendit_id',
        'amount',
        'payment_method',
        'payment_url',
        'va_number',
        'qr_code',
        'description',
        'status',
        'paid_at',
        'expires_at',
        'xendit_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'xendit_response' => 'array',
    ];

    protected $dates = [
        'paid_at',
        'expires_at',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Accessors
    public function getIsExpiredAttribute()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsPaidAttribute()
    {
        return $this->status === 'paid';
    }

    public function getIsPendingAttribute()
    {
        return $this->status === 'pending';
    }

    // Methods
    public function markAsPaid($xenditResponse = null)
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'xendit_response' => $xenditResponse,
        ]);
    }

    public function markAsFailed($xenditResponse = null)
    {
        $this->update([
            'status' => 'failed',
            'xendit_response' => $xenditResponse,
        ]);
    }

    public function getPaymentMethodNameAttribute()
    {
        $methods = [
            'bca' => 'Bank Transfer BCA',
            'bni' => 'Bank Transfer BNI',
            'bri' => 'Bank Transfer BRI',
            'mandiri' => 'Bank Transfer Mandiri',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'dana' => 'DANA',
            'qris' => 'QRIS',
            'credit_card' => 'Credit Card',
        ];

        return $methods[$this->payment_method] ?? $this->payment_method;
    }
}