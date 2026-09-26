<?php

namespace App\Models;

use App\Enums\DebtStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Debt extends Model
{
    use HasUuids;

    /**
     * Archived (paid) debts stay visible for this many days after the exact
     * date/time they were fully paid, then they are pruned automatically.
     */
    public const ARCHIVE_RETENTION_DAYS = 15;

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
        'paid_at',
    ];

    protected $casts = [
        'status' => DebtStatus::class,
        'paid_manually' => 'boolean',
        'paid_manually_at' => 'datetime',
        'money_amount' => 'decimal:2',
        'loaned_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Limit to paid debts that are still inside the archive retention window.
     *
     * Debts settled manually without a timestamp keep a null `paid_at`; they
     * were settled before tracking existed and should remain viewable.
     */
    public function scopeRetainedArchive(Builder $query): Builder
    {
        return $query->where('status', DebtStatus::Paid->value)
            ->where(
                fn (Builder $query): Builder => $query
                    ->whereNull('paid_at')
                    ->orWhere('paid_at', '>=', now()->subDays(self::ARCHIVE_RETENTION_DAYS))
            );
    }

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
