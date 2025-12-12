@php
$includeJS = $includeJS ?? true;
@endphp

@if($includeJS)
    @once
        @vite('resources/js/csc-validation.js')
    @endonce
@endif

<!-- CSC Validation Component -->
<div class="csc-validation-container" data-csc-enabled="true">
    @if(isset($title))
        <div class="mb-4">
            <h2 class="text-xl font-semibold text-gray-800">{{ $title }}</h2>
            <p class="text-sm text-gray-600 mt-1">{{ $description ?? 'CSC Form No. 212 compliance validation' }}</p>
        </div>
    @endif

    {{ $slot }}
</div>
