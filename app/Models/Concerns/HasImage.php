<?php

namespace App\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** `image_path` ustuni: katalog rasmlari (`delivery.catalog_disk`). */
trait HasImage
{
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk(config('delivery.catalog_disk'))->url($this->image_path) : null;
    }

    /** Yangi rasmni saqlaydi va eskisini o'chiradi (model saqlanmaydi). */
    public function replaceImage(?UploadedFile $file, bool $remove = false): void
    {
        if ($file === null && ! $remove) {
            return;
        }

        $this->deleteImage();
        $this->image_path = $file?->store('catalog/'.$this->getTable(), config('delivery.catalog_disk'));
    }

    public function deleteImage(): void
    {
        if ($this->image_path) {
            Storage::disk(config('delivery.catalog_disk'))->delete($this->image_path);
        }
    }
}
