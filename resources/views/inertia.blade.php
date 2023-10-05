@extends('app')

@section('head')

    @vite(['resources/js/inertia.js'])

@endsection

@section('body')

    @inertia

    @routes

    <script src="{{ url('tsparticles.min.js') }}"></script>

    <script>
        (async () => {
            await tsParticles.load("tsparticles", {
                preset: "triangles",
                fpsLimit: 120,
                fullScreen: {
                    enable: true,
                    zIndex: -1,
                },
                interactivity: {
                    events: {
                        onClick: {
                            enable: true,
                            mode: 'push',
                        },
                        onHover: {
                            enable: true,
                            mode: 'repulse',
                        },
                        resize: true,
                        },
                        modes: {
                        push: {
                            quantity: 4,
                        },
                        repulse: {
                            distance: 200,
                            duration: 0.4,
                        },
                    },
                },
            });
        })();
    </script>
@endsection
