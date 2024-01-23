<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head } from '@inertiajs/vue3'

defineProps(['link', 'version'])
</script>

<template>
    <GuestLayout class="flex">
        <Head title="Download" />

        <div class="flex flex-col content-center flex-1">
            <div class="hero">
                <div
                    class="px-12 py-10 bg-gradient-to-bl via-50% via-base-100/50 to-100% to-base-200/20 from-base-300/60 rounded-none sm:rounded-sm"
                >
                    <div class="flex-col hero-content lg:flex-row-reverse lg:gap-20">
                        <div class="text-center lg:text-left" style="text-wrap: balance;">
                            <h1 class="text-5xl font-bold">Download our mobile app!</h1>
                            <span class="py-6 font-mono text-sm text-primary-focus">{{ $page.props.app.name }}</span>

                            <p class="py-6">
                                Enhance your experience – download the app now for exclusive features and personalized updates.
                            </p>

                            <p class="py-6 text-lg italic font-bold">
                                Your convenience, redefined...
                            </p>

                            <a :href="link" class="my-6 normal-case btn btn-primary btn-wide">
                                <svg class="w-6 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                    <path d="M420.6 301.9a24 24 0 1 1 24-24 24 24 0 0 1 -24 24m-265.1 0a24 24 0 1 1 24-24 24 24 0 0 1 -24 24m273.7-144.5 47.9-83a10 10 0 1 0 -17.3-10h0l-48.5 84.1a301.3 301.3 0 0 0 -246.6 0L116.2 64.5a10 10 0 1 0 -17.3 10h0l47.9 83C64.5 202.2 8.2 285.6 0 384H576c-8.2-98.5-64.5-181.8-146.9-226.6"/>
                                </svg>

                                <span class="uppercase">
                                    Download
                                </span>

                                v{{ version }}.apk
                            </a>

                            <p class="py-6 font-mono text-warning">
                                Note: Current development support is only for <span class="italic underline">Android</span> devices.
                            </p>
                        </div>

                        <div class="py-12">
                            <div class="mockup-phone">
                                <div class="camera" style="width: 96px;">

                                </div>
                                <div class="h-[512px] display">
                                    <div class="p-0 phone-1">
                                        <div class="w-60 carousel">
                                            <div id="dtr" class="relative overflow-hidden w-60 carousel-item">
                                                <img src="./assets/dtr.jpg" class="w-72" />
                                                <div class="absolute flex justify-between transform -translate-y-1/2 -left-6 -right-6 top-1/2">
                                                    <a href="#splash" class="pl-3 btn btn-circle">❮</a>
                                                    <a href="#profile" class="pr-3 btn btn-circle">❯</a>
                                                </div>
                                            </div>
                                            <div id="profile" class="relative overflow-hidden w-60 carousel-item">
                                                <img src="./assets/profile.jpg" class="w-72" />
                                                <div class="absolute flex justify-between transform -translate-y-1/2 -left-6 -right-6 top-1/2">
                                                    <a href="#dtr" class="pl-3 btn btn-circle">❮</a>
                                                    <a href="#pin" class="pr-3 btn btn-circle">❯</a>
                                                </div>
                                            </div>
                                            <div id="pin" class="relative overflow-hidden w-60 carousel-item">
                                                <img src="./assets/pin.jpg" class="w-72" />
                                                <div class="absolute flex justify-between transform -translate-y-1/2 -left-6 -right-6 top-1/2">
                                                    <a href="#profile" class="pl-3 btn btn-circle">❮</a>
                                                    <a href="#loading" class="pr-3 btn btn-circle">❯</a>
                                                </div>
                                            </div>
                                            <div id="loading" class="relative overflow-hidden w-60 carousel-item">
                                                <img src="./assets/loading.jpg" class="w-72" />
                                                <div class="absolute flex justify-between transform -translate-y-1/2 -left-6 -right-6 top-1/2">
                                                    <a href="#pin" class="pl-3 btn btn-circle">❮</a>
                                                    <a href="#splash" class="pr-3 btn btn-circle">❯</a>
                                                </div>
                                            </div>
                                            <div id="splash" class="relative overflow-hidden w-60 carousel-item">
                                                <img src="./assets/splash.jpg" class="w-72" />
                                                <div class="absolute flex justify-between transform -translate-y-1/2 -left-6 -right-6 top-1/2">
                                                    <a href="#loading" class="pl-3 btn btn-circle">❮</a>
                                                    <a href="#dtr" class="pr-3 btn btn-circle">❯</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
