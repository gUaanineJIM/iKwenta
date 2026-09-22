<?php

namespace App\Models;

use App\Enums\DebtStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasUuids;

    protected $table = 'debts';

    protected $primaryKey = 'debt_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'debt_id',
        'customer_id',
        'created_by',
        'status',
        'paid_manually',
        'paid_manually_by',
        'paid_manually_at',
        'money_amount',
        'loaned_at',
    ];

    protected $casts = [
        'status' => DebtStatus::class,
        'paid_manually' => 'boolean',
        'paid_manually_at' => 'datetime',
        'money_amount' => 'decimal:2',
        'loaned_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class,
            'customer_id',
            'customer_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            DebtItem::class,
            'debt_id',
            'debt_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(
            Payment::class,
            'debt_id',
            'debt_id'
        );
    }

    public function manuallyPaidBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'paid_manually_by',
            'user_id'
        );
    }
}
