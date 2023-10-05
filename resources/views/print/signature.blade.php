<div
    class="print-only"
    @style([
        'position: absolute',
        'left: 50%',
        'transform: translateX(-50%)',
        'user-select: none',
        'pointer-events: none',
        'top: -20pt' => $landscape,
        'top: -45pt' => $portrait,
        'height: 50pt' => $landscape,
        'width: 50pt' => $portrait,
    ])
>
    @if ($signature)
        <img
            src="{{ $signature }}"
            alt="electronic-signature"
            @style([
                'width: auto',
                'height: auto',
                'user-select: none',
                'pointer-events: none',
                'max-height: 50pt' => $landscape,
                'max-height: 75pt' => $portrait,
            ])
        >
    @endif
</div>
