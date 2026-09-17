<?php

namespace App\Models;

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
}
