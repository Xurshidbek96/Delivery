{{-- Rasm yoki ikon: @include('admin.partials.thumb', ['model' => $product, 'icon' => 'ti-package', 'size' => 'lg']) --}}
<span @class(['thumb', 'thumb-lg' => ($size ?? null) === 'lg'])>
    @if($url = $model?->imageUrl())
        <img src="{{ $url }}" alt="" loading="lazy">
    @else
        <i class="ti {{ $icon ?? 'ti-photo' }}"></i>
    @endif
</span>
