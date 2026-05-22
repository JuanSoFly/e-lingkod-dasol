<div class="pds-modal-content-wrapper">
    <style>
        .modal-header-actions h2 {
            display: none !important;
        }
        .modal-header-actions > div {
            width: 100%;
            justify-content: flex-end;
        }
    </style>

    @isset($header)
        <div class="modal-header-actions mb-6 flex justify-end">
            {{ $header }}
        </div>
    @endisset

    @isset($slot)
        {{ $slot }}
    @else
        @yield('content')
    @endisset

    @stack('scripts')
</div>
