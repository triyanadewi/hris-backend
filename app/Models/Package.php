<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'description'
    ];

    protected $casts = [
        'price' => 'integer'
    ];

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function packageBenefits()
    {
        return $this->hasMany(PackageBenefit::class);
    }

    // Relasi untuk mendapatkan satu benefit aktif (PENTING: ini yang dibutuhkan!)
    public function packageBenefit()
    {
        return $this->hasOne(PackageBenefit::class)->where('is_active', true);
    }

    // Alternatif: jika Anda ingin mendapatkan benefit terbaru yang aktif
    public function activePackageBenefit()
    {
        return $this->hasOne(PackageBenefit::class)
                    ->where('is_active', true)
                    ->latest();
    }

    // Helper methods
    public function getActiveBenefit()
    {
        return $this->packageBenefits()->where('is_active', true)->first();
    }

    public function hasActiveBenefit()
    {
        return $this->packageBenefits()->where('is_active', true)->exists();
    }
}