<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtItem extends Model
{
    use HasUuids;

    protected $table = 'debt_items';

    protected $primaryKey = 'debt_item_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'debt_item_id',
        'debt_id',
        'product_id',
        'product_name',
        'quantity',
        'unit_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function debt(): BelongsTo
    {
        return $this->belongsTo(
            Debt::class,
            'debt_id',
            'debt_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id',
            'product_id'
        );
    }
}
