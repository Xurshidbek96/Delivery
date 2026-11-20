<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $number
 * @property OrderStatus $status
 * @property PaymentMethod $payment_method
 * @property PaymentStatus $payment_status
 * @property int $items_total
 * @property int $delivery_fee
 * @property int $service_fee
 * @property int $total
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'courier_id',
        'confirmed_by',
        'status',
        'address',
        'entrance',
        'floor',
        'apartment',
        'landmark',
        'lat',
        'lng',
        'contact_name',
        'contact_phone',
        'note',
        'staff_note',
        'items_total',
        'delivery_fee',
        'service_fee',
        'total',
        'payment_method',
        'payment_status',
        'receipt_path',
        'confirmed_at',
        'assigned_at',
        'shopping_started_at',
        'delivering_at',
        'delivered_at',
        'cancelled_at',
        'cancelled_by_role',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'cancelled_by_role' => UserRole::class,
            'lat' => 'float',
            'lng' => 'float',
            'items_total' => 'integer',
            'delivery_fee' => 'integer',
            'service_fee' => 'integer',
            'total' => 'integer',
            'confirmed_at' => 'datetime',
            'assigned_at' => 'datetime',
            'shopping_started_at' => 'datetime',
            'delivering_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Odam o'qiy oladigan raqam: D000123
        static::created(function (Order $order) {
            $order->forceFill(['number' => 'D'.str_pad((string) $order->id, 6, '0', STR_PAD_LEFT)])->saveQuietly();
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id')->withTrashed();
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id')->withTrashed();
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('position')->orderBy('id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function scopeStatus(Builder $query, OrderStatus ...$statuses): Builder
    {
        return $query->whereIn('status', array_map(fn (OrderStatus $s) => $s->value, $statuses));
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $digits = preg_replace('/\D+/', '', $term);
            $q->where('number', 'like', "%{$term}%")
                ->orWhere('address', 'like', "%{$term}%")
                ->orWhere('contact_name', 'like', "%{$term}%");
            if ($digits !== '') {
                $q->orWhere('contact_phone', 'like', "%{$digits}%");
            }
        });
    }

    public function isAssignedTo(User $courier): bool
    {
        return $this->courier_id !== null && $this->courier_id === $courier->id;
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address,
            $this->entrance ? "{$this->entrance}-podyezd" : null,
            $this->floor ? "{$this->floor}-qavat" : null,
            $this->apartment ? "{$this->apartment}-xonadon" : null,
        ])->filter()->implode(', ');
    }

    public function mapUrl(): ?string
    {
        if ($this->lat === null || $this->lng === null) {
            return null;
        }

        return "https://yandex.uz/maps/?pt={$this->lng},{$this->lat}&z=17&l=map";
    }

    public function receiptUrl(): ?string
    {
        return $this->receipt_path
            ? Storage::disk(config('delivery.receipts_disk'))->url($this->receipt_path)
            : null;
    }
}
