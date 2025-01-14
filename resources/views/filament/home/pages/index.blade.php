<main class="relative">
    <nav class="bg-white border-gray-200 py-2.5 dark:bg-gray-900 sticky">
        <div class="flex flex-wrap items-center justify-end max-w-screen-xl px-4 mx-auto">
            <a href="/" title="Home" class="items-center hidden mr-auto lg:flex">
                <span class="w-48">
                    @include('banner')
                </span>
            </a>
            <div class="flex items-center gap-2 lg:order-2">
                @include('theme-switcher')

                <x-filament::button tag="a" :href="route('filament.auth.auth.login')">
                    @if(auth()->check() || auth()->guard('employee')->check())
                        Continue
                    @else
                        Sign in
                    @endif
                </x-filament::button>

                @if(auth()->check() || auth()->guard('employee')->check())
                    <form method="post" action="{{ route('filament.home.auth.logout') }}">
                        @csrf
                        <x-filament::button type="submit">
                            Sign out
                        </x-filament::button>
                    </form>
                @endif
            </div>
        </div>
    </nav>

    <section class="bg-white dark:bg-gray-900">
        <div class="grid max-w-screen-xl px-4 pt-8 pb-8 mx-auto lg:gap-8 xl:gap-0 lg:py-16 lg:grid-cols-12 lg:pt-28">
            <div class="hidden lg:mt-0 lg:col-span-5 lg:flex">
                <div class="w-2/3 pb-3">
                    @include('logo')
                </div>
            </div>
            <div class="mr-auto place-self-center lg:col-span-7">
                <h1 class="max-w-2xl mb-4 text-4xl font-extrabold leading-none tracking-tight md:text-5xl xl:text-6xl dark:text-white">
                    Effortless Attendance Management for <br> Modern Workplaces
                </h1>

                <p class="max-w-2xl mb-6 font-light text-gray-500 lg:mb-8 md:text-lg lg:text-xl dark:text-gray-400">
                    {{ config('app.name') }} is your trusted companion for managing and viewing daily attendance logs with ease. Designed for employees of the {{ settings('name') }}, {{ config('app.name') }} ensures accurate, secure, and efficient time tracking through biometric integration.
                </p>
                <div class="space-y-4 sm:flex sm:space-y-0 sm:space-x-4">

                </div>
            </div>
        </div>
        <div class="spacer layer-1"> </div>
    </section>


    <section class="bg-[#145a92]">
        <div class="max-w-screen-xl px-4 py-8 mx-auto space-y-12 lg:space-y-20 lg:py-24 lg:px-6">
            <div class="items-center gap-8 space-y-12 lg:grid lg:grid-cols-2 xl:gap-16 lg:space-y-0">
                <div class="text-gray-300 sm:text-lg">
                    <h2 class="mb-4 text-3xl font-extrabold tracking-tight text-white">
                        Why Choose {{ config('app.name') }}?
                    </h2>

                    <p class="mb-8 font-light lg:text-xl">
                        Experience the next level of efficiency and security with {{ config('app.name') }}!
                        Here&rsquo;s why {{ config('app.name') }} stands out as the perfect solution for managing your attendance...
                    </p>

                    <ul role="list" class="space-y-5">
                        <li>
                            <div class="flex space-x-3">
                                <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <span class="text-base font-medium leading-tight text-white">Streamlined Attendance Tracking</span>
                            </div>
                            <p class="mt-1 ml-8 text-sm text-gray-400">Say goodbye to manual attendance processes! Clockwork automates the collection and management of attendance data from biometric devices, ensuring precision and reliability.</p>
                        </li>
                        <li>
                            <div class="flex space-x-3">
                                <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <span class="text-base font-medium leading-tight text-white">Secure and Private</span>
                            </div>
                            <p class="mt-1 ml-8 text-sm text-gray-400">We prioritize your privacy. Your data is encrypted and stored securely, with no third-party sharing.</p>
                        </li>
                        <li>
                            <div class="flex space-x-3">
                                <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                <span class="text-base font-medium leading-tight text-white">Empower Your Workday</span>
                            </div>
                            <p class="mt-1 ml-8 text-sm text-gray-400">Access your attendance time logs, review attendance records, and manage your digital signature for official documents — all in one place.</p>
                        </li>
                    </ul>
                </div>

                <div class="text-gray-300 sm:text-lg">
                    <h2 class="mb-4 text-3xl font-extrabold tracking-tight text-white">
                        Features
                    </h2>

                    <ul role="list" class="space-y-3">
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span class="text-base font-medium leading-tight text-white">Digital signature certification and verification</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span class="text-base font-medium leading-tight text-white">Erroneous timelog state rectification</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span class="text-base font-medium leading-tight text-white">Employee schedule management</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span class="text-base font-medium leading-tight text-white">Centralized biometric data</span>
                        </li>
                        <li class="flex space-x-3">
                            <svg class="flex-shrink-0 w-5 h-5 text-[aqua]" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span class="text-base font-medium leading-tight text-white">Export directly in CSC Form No. 48</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white dark:bg-gray-900">
    <div class="spacer layer-2"> </div>
    <div class="items-center max-w-screen-xl px-4 py-8 mx-auto lg:grid lg:grid-cols-4 lg:gap-16 xl:gap-24 lg:py-24 lg:px-6">

        <div class="col-span-2 mb-8">
            <h2 class="mb-4 text-3xl font-extrabold leading-tight tracking-tight text-gray-900 dark:text-white">Don't have an account?</h2>
            <p class="mb-6 font-light text-gray-500 dark:text-gray-400 md:text-lg">
                Please contact us to get started with {{ config('app.name') }}. Either send us an email, give us a call, or send us a letter, and we'll be happy to assist you with your account setup.
            </p>

            <p class="mb-6 text-sm font-light text-gray-500 dark:text-gray-400">
                If you do not work for the {{ settings('name') }}, we are sorry, but this service is not for you.
            </p>

            <x-filament::button tag="a" :href="route('filament.auth.auth.login')" size="xl">
                @if(auth()->check() || auth()->guard('employee')->check())
                    Continue
                @else
                    Sign in here
                @endif
            </x-filament::button>
        </div>
        <div class="col-span-2 mb-8">
            <p class="text-lg font-medium">Our Promise</p>
            <h2 class="mt-3 mb-4 text-3xl font-extrabold tracking-tight text-gray-900 md:text-3xl dark:text-white">Reliability and Security You Can Trust</h2>
            <p class="font-light text-gray-500 sm:text-xl dark:text-gray-400">
                We commit to providing you with the highest level of reliability, security, and service. Our team is always ready to assist you with your needs, ensuring that your experience is seamless and efficient.
            </p>
            <div class="pt-6 mt-6 space-y-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <x-filament::link
                        :href="route('filament.legal.pages.privacy-policy')"
                        icon="heroicon-o-arrow-long-right"
                        icon-position="after"
                    >
                        Learn more about our Privacy Policy
                    </x-filament::link>
                </div>
                <div>
                    <x-filament::link
                        :href="route('filament.legal.pages.user-agreement')"
                        icon="heroicon-o-arrow-long-right"
                        icon-position="after"
                    >
                        Read the User Agreement
                    </x-filament::link>
                </div>
            </div>
        </div>
    </div>
</section>

</main>

@push('styles')
<style>
    .spacer {
        aspect-ratio: 900 / 100;
        width: 100%;
        min-height: 12em;
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
    }

    .layer-1 {
        background-image: url('./svg/layer-1.svg');
    }

    .layer-2 {
        background-image: url('./svg/layer-2.svg');
    }
</style>
@endpush
