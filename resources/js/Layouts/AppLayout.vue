<template>
    <div>
        <Head :title="title" />

        <div class="min-h-screen bg-gray-100 dark:bg-gray-800">
            <section class="bg-white dark:bg-black">
                <tailwind-navigation :navigation="navigation" :dropdown="dropdown" />
            </section>

            <header class="shadow bg-gray-50 dark:bg-gray-900" v-if="$slots.header">
                <div class="px-4 py-4 mx-auto text-gray-800 max-w-7xl sm:px-6 lg:px-8 dark:text-gray-200">
                    <slot name="header"></slot>
                </div>
            </header>

            <main>
                <slot></slot>
            </main>
        </div>
    </div>
</template>

<script>
    import { defineComponent } from 'vue'
    import TailwindNavigation from '@/Tailwind/Navigation.vue'
    import { Head } from '@inertiajs/inertia-vue3'

    export default defineComponent({
        props: {
            title: String,
        },

        components: {
            Head,
            TailwindNavigation,
        },

        computed: {
            navigation: function() {
                return [
                    {
                        name: 'Dashboard',
                        href: route('dashboard'),
                        active: route().current('dashboard'),
                        show: false,
                    },
                    {
                        name: 'Biometrics',
                        href: route('biometrics.index'),
                        active: route().current('biometrics.*'),
                        show: true,
                    },
                    {
                        name: 'Time Logs',
                        href: route('timelogs.index'),
                        active: route().current('timelogs.*'),
                        show: true,
                    },
                ]
            },

            dropdown: function() {
                return [
                    {
                        name: 'Settings',
                        href: route('profile.show'),
                        active: route().current('profile.show'),
                        show: true,
                    },
                ]
            }
        },
    })
</script>
