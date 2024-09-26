@props(['is' => 'div', 'if' => true])
@if ($if)
    <{{ $is }} {{ $attributes }}>
@endif
    {{ $slot }}
@if ($if)
    </{{ $is }}>
@endif
