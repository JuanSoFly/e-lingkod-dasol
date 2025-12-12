@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium responsive-text-sm text-slate-700']) }}>
    {{ $value ?? $slot }}
</label>
