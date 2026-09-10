<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Beneficiary extends Model
{
    use HasFactory;

    protected $table = 'benificiaries';

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
}