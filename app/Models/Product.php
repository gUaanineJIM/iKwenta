<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasUuids;

    protected $table = 'products';

    protected $primaryKey = 'product_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'product_id',
        'product_name',
        'description',
        'price',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by',
            'user_id'
        );
    }

    public function debtItems(): HasMany
    {
        return $this->hasMany(
            DebtItem::class,
            'product_id',
            'product_id'
        );
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(
            ProductPriceHistory::class,
            'product_id',
            'product_id'
        );
    }
}
