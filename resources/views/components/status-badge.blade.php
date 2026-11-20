@props(['status'])
<span {{ $attributes->class(['badge', 'badge-'.$status->tone()]) }}><i class="ti {{ $status->icon() }}"></i>{{ $status->label() }}</span>
