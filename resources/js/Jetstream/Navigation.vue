<template>
    <nav class="bg-white border-b border-gray-100">
        <!-- Primary Navigation Menu -->
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <!-- Logo -->
                    <div class="flex items-center flex-shrink-0">
                        <Link :href="route('dashboard')">
                            <jet-application-mark class="block w-auto h-9" />
                        </Link>
                    </div>

                    <!-- Navigation Links -->
                    <template v-for="nav in navigation" :key="nav.name">
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <jet-nav-link v-if="nav.show" v-html="nav.name" :href="nav.href" :active="nav.active" />
                        </div>
                    </template>
                </div>

                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    <button hidden type="button" class="p-1 text-gray-400 rounded-full hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">View notifications</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>

                    <!-- Settings Dropdown -->
                    <div class="relative ml-3">
                        <jet-dropdown align="right" width="48">
                            <template #trigger>
                                <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm transition border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300">
                                    <img class="object-cover w-8 h-8 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" />
                                </button>

                                <span v-else class="inline-flex rounded-md">
                                    <button type="button" class="inline-flex items-center px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition bg-white border border-transparent rounded-md hover:text-gray-700 focus:outline-none">
                                        {{ $page.props.auth.user.name }}

                                        <svg class="ml-2 -mr-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            <!-- Dropdown Links -->
                            <template #content>
                                <template v-for="link in dropdown" :key="link.name">
                                    <template v-if="link.href">
                                        <jet-dropdown-link :href="link.href">
                                            {{ link.name }}
                                        </jet-dropdown-link>
                                    </template>

                                    <template v-else>
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            {{ link.name }}
                                        </div>
                                    </template>
                                </template>

                                <div class="border-t border-gray-100"></div>

                                <!-- Authentication -->
                                <form @submit.prevent="logout">
                                    <jet-dropdown-link as="button">
                                        Log Out
                                    </jet-dropdown-link>
                                </form>
                            </template>
                        </jet-dropdown>
                    </div>
                </div>

                <!-- Hamburger -->
                <div class="flex items-center -mr-2 sm:hidden">
                    <button @click="showingNavigationDropdown = ! showingNavigationDropdown" class="inline-flex items-center justify-center p-2 text-gray-400 transition rounded-md hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500">
                        <svg class="w-6 h-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Responsive Navigation Menu -->
        <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
            <template v-for="nav in navigation" :key="nav.name">
                <div class="pt-2 pb-3 space-y-1">
                    <jet-responsive-nav-link v-if="nav.show" :href="nav.href" :active="nav.active">
                        {{ nav.name }}
                    </jet-responsive-nav-link>
                </div>
            </template>

            <!-- Responsive Settings Options -->
            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="flex items-center px-4">
                    <div v-if="$page.props.jetstream.managesProfilePhotos" class="flex-shrink-0 mr-3" >
                        <img class="object-cover w-10 h-10 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name" />
                    </div>

                    <div>
                        <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <template v-for="link in dropdown" :key="link.name">
                        <jet-responsive-nav-link v-if="link.show && link.href" :href="link.href" :active="link.active">
                            {{ link.name }}
                        </jet-responsive-nav-link>
                    </template>

                    <!-- Authentication -->
                    <form method="POST" @submit.prevent="logout">
                        <jet-responsive-nav-link as="button">
                            Log Out
                        </jet-responsive-nav-link>
                    </form>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetApplicationMark from '@/Jetstream/ApplicationMark.vue'
    import JetDropdown from '@/Jetstream/Dropdown.vue'
    import JetDropdownLink from '@/Jetstream/DropdownLink.vue'
    import JetNavLink from '@/Jetstream/NavLink.vue'
    import JetResponsiveNavLink from '@/Jetstream/ResponsiveNavLink.vue'
    import { Link } from '@inertiajs/inertia-vue3';

    export default defineComponent({
        props: {
            title: String,
            navigation: Array,
            dropdown: Array,
        },

        components: {
            JetApplicationMark,
            JetDropdown,
            JetDropdownLink,
            JetNavLink,
            JetResponsiveNavLink,
            Link,
        },

        data() {
            return {
                showingNavigationDropdown: false,
            }
        },

        methods: {
            logout() {
                this.$inertia.post(route('logout'));
            },
        },
    })
</script>
