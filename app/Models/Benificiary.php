<?php

namespace App\Models;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Benificiary extends Model
{
    use HasFactory;

    protected $table = 'beneficiaries';

    protected $fillable = [
        'barangay_id',
        'first_name',
        'middle_name',
        'last_name',
        'birthdate',
        'gender',
        'address',
        'household_members',
        'qr_code',
        'status',
        'registered_by',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
            'household_members' => 'integer',
        ];
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' '));
    }


 
  protected static function booted(): void
    {
        // qr_code is NOT NULL + unique, so every beneficiary gets an
        // unguessable token the moment it's created.
        static::creating(function (Benificiary $beneficiary) {
            if (empty($beneficiary->qr_code)) {
                $beneficiary->qr_code = (string) Str::uuid();
            }
        });
    }
 
 
    /**
     * The URL a phone opens when it scans this beneficiary's QR code.
     * Built from `qr_code`, so it must be reachable from the scanner's
     * device — APP_URL can't be localhost when scanning from a phone.
     */   public function scanUrl(): string
    {
        return route('beneficiaries.scan', ['qr_code' => $this->qr_code]);
    }
 
    /**
     * Renders the QR code for scanUrl() as a PNG (binary string).
     * PngWriter needs the GD extension (extension=gd in php.ini).
     */
    public function generateQrCode(): string
    {
        $qrCode = new QrCode($this->scanUrl());
        $result = (new PngWriter())->write($qrCode);
 
        return $result->getString();
    }


     public function distributionTransactions(): HasMany
    {
        return $this->hasMany(DistributionTransaction::class);
    }

    public function hasClaimedFor(int $scheduleId): bool
    {
        return $this->distributionTransactions()
            ->where('distribution_schedule_id', $scheduleId)
            ->exists();
    }

   
}