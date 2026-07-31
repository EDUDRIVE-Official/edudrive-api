@props([])
<div {{ $attributes->merge(['class' => 'rounded-md bg-surface p-4 shadow-sm']) }}>
    {{ $slot }}
</div>
