<div style="position: relative">
    <div
        @style([
            'position: absolute',
            'left: 50%',
            'transform: translateX(-50%)',
            'user-select: none',
            'pointer-events: none',
            'top: -45pt' => $signature->portrait || ! $signature->landscape,
            'top: -30pt' => $signature->landscape,
        ])
    >
        <img
            src="data:image/png;base64,{{ $signature->specimenBase64 }}"
            alt="electronic-signature"
            @style([
                'user-select: none',
                'pointer-events: none',
                'max-height:50pt!important;width:auto;' => $signature->landscape,
                'max-height:65pt!important;height:auto;' => $signature->portrait || ! $signature->landscape,
            ])
        >
    </div>

    @if ($signed ?? false)
        <div style="position:absolute;top:-20pt;font-weight:normal;">
            <div style="display:flex;align-items:center;justify-content:center;">
                <div style="color:#4BB54399;display:flex;align-items:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" style="width:auto;height:24pt;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.746 3.746 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                    </svg>
                </div>
                <div style="display:flex;flex-direction:column;text-align:left;font-size:8pt;font-family:Cousine;">
                    <span style="text-transform:uppercase;opacity:0.5;">
                        digitally
                    </span>
                    <span style="text-transform:uppercase;opacity:0.5;">
                        signed
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>
