<?php

namespace App\Models;

use App\Enums\ItemUnit;
use App\Enums\OrderItemStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'shop_name',
        'name',
        'quantity',
        'unit',
        'note',
        'status',
        'price',
        'courier_note',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'float',
            'unit' => ItemUnit::class,
            'status' => OrderItemStatus::class,
            'price' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function quantityLabel(): string
    {
        $qty = rtrim(rtrim(number_format($this->quantity, 3, '.', ''), '0'), '.');

        return "{$qty} {$this->unit->label()}";
    }
}
