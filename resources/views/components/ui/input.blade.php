@props(['name', 'label' => null, 'type' => 'text', 'error' => null])
@php
    $id = $attributes->get('id', $name);
@endphp
<div class="flex flex-col gap-1">
    @if ($label)
        <label for="{{ $id }}" class="font-sans text-sm font-medium text-text">{{ $label }}</label>
    @endif

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->except(['id', 'name', 'type'])->merge([
            'class' => 'min-h-[44px] rounded-sm border bg-surface px-3 font-sans text-base text-text '
                . 'focus-visible:outline-none focus-visible:shadow-focus '
                . ($error ? 'border-danger' : 'border-border'),
        ]) }}
    />

    @if ($error)
        <p class="font-sans text-sm text-danger">{{ $error }}</p>
    @endif
</div>
