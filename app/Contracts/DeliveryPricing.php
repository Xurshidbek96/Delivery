<?php

namespace App\Contracts;

use App\Support\DeliveryQuote;

/**
 * Yetkazish narxini hisoblash. Hozir: FlatDeliveryPricing (sozlamadagi qat'iy narx).
 * Keyin: masofa/zona bo'yicha narx — shu interfeysni implement qilib, AppServiceProvider'da almashtiring.
 */
interface DeliveryPricing
{
    public function quote(?float $lat, ?float $lng, int $itemsCount): DeliveryQuote;
}
