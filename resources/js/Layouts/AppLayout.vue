<script setup>
import Banner from './Partials/Banner.vue'
import TailwindNavigation from '@/Tailwind/Navigation.vue'
import sendToast from '@/Composables/toasts'
import Toast from '@/Components/Toast.vue'
import { Head, usePage } from '@inertiajs/vue3'
import { onBeforeUnmount, onMounted, watch } from 'vue'
import echo from '@/echo'
import dayjs from 'dayjs'
import localizedFormat from 'dayjs/plugin/localizedFormat'

dayjs.extend(localizedFormat)

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

watch(() => usePage().props.flash?.toast, (toast) => {
    if (!toast.title || !toast.message) {
        return
    }

    sendToast(toast.type, toast.title, toast.message)
})

onMounted(() => {
    echo.join('presence')
        .here((authenticated) => console.log(`online users [${authenticated.map((e) => e.username).join(', ')}].`))
        .joining((authenticated) => console.log(`${authenticated.username} is now online.`))
        .leaving((authenticated) => console.log(`${authenticated.username} is now offline.`))

    if (user.administrator) {
        echo.private(`administrators`).listen('EmployeesImportation ', (event) => {
            sendToast(
                event.status,
                `Update ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}`,
                event.message,
                `Updated by @${event.username} at ${dayjs(event.time).format('llll')} for ${event.duration} seconds.`,
            )
        })
    } else {
        echo.private(`users.${user.id}`).listen('EmployeesImportation ', (event) => {
            sendToast(
                event.status,
                `Update ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}`,
                event.message,
                `Updated by @${event.username} at ${dayjs(event.time).format('llll')} for ${event.duration} seconds.`,
            )
        })
    }

    user.scanners?.forEach(scanner => {
        echo.private(`scanners.${scanner.id}`).listen('TimelogsSynchronization', (event) => {
            sendToast(
                event.status,
                `Synchronize ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}`,
                event.message,
                `Synchronized by @${event.user} at ${dayjs(event.time).format('llll')} for ${event.duration} seconds.`,
            )
        })

        echo.private(`scanners.${scanner.id}`).listen('TimelogsImportation', (event) => {
            sendToast(
                event.status,
                `Import ${event.status.charAt(0).toUpperCase() + event.status.slice(1)}`,
                event.message,
                `Imported by @${event.user} at ${dayjs(event.time).format('llll')} for ${event.duration} seconds.`,
            )
        })
    })
})

onBeforeUnmount(() => {
    echo.leave('presence')

    echo.leave(`users.${user.id}`)

    echo.leave(`administrators`)

    user.scanners?.forEach(scanner => echo.leave(`scanners.${scanner.id}`))
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
