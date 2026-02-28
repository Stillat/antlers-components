@props(['variant' => 'default', 'indicator' => true])
<div data-variant="{{ $variant }}" data-indicator="{{ $indicator ? 'true' : 'false' }}">
    {{ $slot }}
</div>
