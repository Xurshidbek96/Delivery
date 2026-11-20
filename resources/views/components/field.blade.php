{{-- Forma maydoni: <x-field name="phone" label="Telefon" hint="..."> <input ...> </x-field> --}}
@props(['name', 'label', 'hint' => null])
<div {{ $attributes->class('field') }}>
    <label class="label" for="{{ $name }}">{{ $label }}</label>
    {{ $slot }}
    @error($name)<div class="error">{{ $message }}</div>@enderror
    @if($hint && ! $errors->has($name))<div class="hint">{{ $hint }}</div>@endif
</div>
