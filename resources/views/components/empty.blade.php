@props(['icon' => 'ti-inbox', 'title' => 'Hech narsa topilmadi'])
<div class="empty">
    <i class="ti {{ $icon }}"></i>
    <strong>{{ $title }}</strong>
    @if($slot->isNotEmpty())<div>{{ $slot }}</div>@endif
</div>
