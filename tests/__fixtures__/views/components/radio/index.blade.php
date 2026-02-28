@aware(['variant', 'indicator'])
@props(['label'])
<label data-variant="{{ $variant ?? 'default' }}" data-indicator="{{ isset($indicator) && $indicator ? 'true' : 'false' }}">{{ $label }}</label>
