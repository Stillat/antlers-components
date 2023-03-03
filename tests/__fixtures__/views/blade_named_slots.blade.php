<div {{ $attributes->class(['border']) }}>
    <h1 {{ $title->attributes->class(['text-lg']) }}>
        {{ $title }}
    </h1>

    {{ $slot }}

    <footer {{ $footer->attributes->class(['text-gray-700']) }}>
        {{ $footer }}
    </footer>
</div>