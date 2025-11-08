@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border border-amber-200 focus:border-amber-500 focus:ring-amber-500 rounded-md bg-white text-slate-800 placeholder-slate-400 transition-colors']) }}>
