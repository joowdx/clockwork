<template>
    <Head :title="title" />

    <div class="min-h-screen bg-base-100">
        <section class="">
            <TailwindNavigation :navigation="navigation" :dropdown="dropdown" />
        </section>

        <header class="shadow bg-base-300" v-if="$slots.header">
            <div class="px-4 py-2 mx-auto max-w-7xl sm:px-6 lg:px-8 text-base-content">
                <slot name="header"> </slot>
            </div>
        </header>

        <main>
            <slot></slot>
        </main>
    </div>
</template>

<script setup>
import TailwindNavigation from '@/Tailwind/Navigation.vue'
import { Head, usePage } from '@inertiajs/vue3'

defineProps({
    title: String,
})

const navigation = [
    {
        name: 'Dashboard',
        href: route('dashboard'),
        active: route().current('dashboard'),
        show: false,
    },
    {
        name: 'Home',
        href: route('home'),
        active: route().current('home'),
        show: true,
    },
    {
        name: 'Scanners',
        href: route('scanners.index'),
        active: route().current('scanners.*'),
        show: false,
    },
    {
        name: 'Users',
        href: route('users.index'),
        active: route().current('users.*'),
        show: false || usePage().props.user.administrator,
    },
]

const dropdown = [
    {
        name: 'Users',
        href: route('users.index'),
        active: route().current('users.*'),
        show: false,
    },
    {
        name: 'Account Settings',
        href: route('profile.show'),
        active: route().current('profile.show'),
        show: true,
    },
]
</script>
