<script setup>
import TailwindNavigation from '@/Tailwind/Navigation.vue'
import { Head, usePage } from '@inertiajs/vue3'
import Echo from './../echo'

defineProps({
    title: String,
})

const user = usePage().props.user

const disabled = user.disabled || user.type === 2

const navigation = [
    {
        name: 'Home',
        href: route('home'),
        active: route().current('home'),
        show: true,
    },
    {
        name: 'Attendance',
        href: route('attendance'),
        active: route().current('attendance'),
        show: user.administrator,
    },
]

const dropdown = [
    {
        name: 'Account',
        href: route('profile.show'),
        active: route().current('profile.show'),
        show: true,
    },
    {
        name: 'Users',
        href: route('users.index'),
        active: route().current('users.*'),
        show: user.administrator,
    },
    {
        name: 'Scanners',
        href: route('scanners.index'),
        active: route().current('scanners.*'),
        show: true,
    },
]

Echo.join('presence')
    .here((authenticated) => console.log(`online users [${authenticated.map((e) => e.username).join(', ')}].`))
    .joining((authenticated) => console.log(`${authenticated.username} is now online.`))
    .leaving((authenticated) => console.log(`${authenticated.username} is now offline.`))
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-col min-h-screen">
        <section class="bg-base-100/90">
            <TailwindNavigation :navigation="disabled ? [] : navigation" :dropdown="disabled ? [] : dropdown" />
        </section>

        <header class="shadow bg-base-300/40" v-if="$slots.header">
            <div class="px-4 py-2 mx-auto max-w-7xl sm:px-6 lg:px-8 text-base-content">
                <slot name="header"> </slot>
            </div>
        </header>

        <main class="flex-1" v-bind="$attrs">
            <slot></slot>
        </main>
    </div>
</template>
