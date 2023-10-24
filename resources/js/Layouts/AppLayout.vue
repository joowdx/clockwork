<script setup>
import { Head, usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import echo from '@/echo'
import Banner from './Partials/Banner.vue'
import TailwindNavigation from '@/Tailwind/Navigation.vue'
import sendToast from '@/Composables/toasts'
import Toast from '@/Components/Toast.vue'

defineProps({
    title: String,
})

const user = usePage().props.user

const disabled = user.disabled || user.needs_password_reset || user.role === 2

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
    {
        name: 'Configuration',
        href: route('configuration.index'),
        active: route().current('configuration.*'),
        show: user.administrator,
    },
]

echo.join('presence')
    .here((authenticated) => console.log(`online users [${authenticated.map((e) => e.username).join(', ')}].`))
    .joining((authenticated) => console.log(`${authenticated.username} is now online.`))
    .leaving((authenticated) => console.log(`${authenticated.username} is now offline.`))


watch(() => usePage().props.flash?.toast, (toast) => {
    if (!toast.title || !toast.message) {
        return
    }

    sendToast(toast.type, toast.title, toast.message)
})
</script>

<template>
    <Head :title="title" />

    <div class="flex flex-col min-h-screen">
        <section class="sticky top-0 z-10 bg-base-100">
            <Banner />

            <TailwindNavigation :navigation="disabled ? [] : navigation" :dropdown="disabled ? [] : dropdown" />
        </section>

        <header v-if="$slots.header" class="shadow bg-base-300/40">
            <div class="px-4 py-2 mx-auto max-w-7xl sm:px-6 lg:px-8 text-base-content">
                <slot name="header"> </slot>
            </div>
        </header>

        <main class="flex-1" v-bind="$attrs">
            <slot></slot>
        </main>
    </div>

    <Toast />
</template>
