<?php

namespace App\Http\Requests\Concerns;

use App\Support\Phone;

trait NormalizesPhone
{
    /** @param list<string> $fields */
    protected function normalizePhones(array $fields): void
    {
        foreach ($fields as $field) {
            if ($this->filled($field)) {
                $this->merge([$field => Phone::normalize($this->input($field)) ?? $this->input($field)]);
            }
        }
    }

    protected function phoneRule(): string
    {
        return 'regex:/^\+998\d{9}$/';
    }
}
