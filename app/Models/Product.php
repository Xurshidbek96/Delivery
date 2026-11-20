<?php

namespace App\Models;

use App\Enums\ItemUnit;
use App\Models\Concerns\HasImage;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasImage;

    protected $fillable = [
        'shop_id',
        'name',
        'description',
        'unit',
        'price',
        'image_path',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit' => ItemUnit::class,
            'price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** Mijozga ko'rinadi: mahsulot ham, do'koni ham faol */
    public function scopeVisible(Builder $query): void
    {
        $query->where('products.is_active', true)
            ->whereHas('shop', fn (Builder $q) => $q->where('is_active', true));
    }

    public function isVisible(): bool
    {
        return $this->is_active && $this->shop->is_active;
    }
}
