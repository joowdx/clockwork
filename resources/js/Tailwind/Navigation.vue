<template>
    <Disclosure as="nav" v-slot="{ open }">
        <div class="px-2 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="relative flex items-center justify-between h-16">
                <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                    <!-- Mobile menu button-->
                    <DisclosureButton class="inline-flex items-center justify-center p-2 text-gray-400 rounded-md hover:text-white hover:bg-indigo-500 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                        <span class="sr-only">Open main menu</span>
                        <MenuIcon v-if="!open" class="block w-6 h-6" aria-hidden="true" />
                        <XIcon v-else class="block w-6 h-6" aria-hidden="true" />
                    </DisclosureButton>
                </div>
                <div class="flex items-center justify-center flex-1 sm:items-stretch sm:justify-start">
                    <div class="flex items-center flex-shrink-0">
                        <JetApplicationMark class="block w-auto h-8 lg:hidden" />
                        <JetApplicationLogo class="hidden w-auto h-8 lg:block" />
                    </div>
                    <div class="hidden sm:block sm:ml-6">
                        <div class="flex space-x-4">
                            <template v-for="item in navigation" :key="item.name">
                                <Link v-if="item.show" :href="item.href" :class="[item.active ? 'bg-indigo-500 text-white dark:bg-gray-800' : 'text-gray-600 hover:bg-indigo-400 dark:hover:bg-gray-900 hover:text-white dark:text-gray-300', 'px-3 py-2 rounded-md text-sm font-medium']" :aria-current="item.active ? 'page' : undefined">
                                    {{ item.name }}
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="absolute inset-y-0 right-0 flex items-center pr-2 sm:static sm:inset-auto sm:ml-6 sm:pr-0">
                    <ColorSchemeToggle />
                    <!-- Profile dropdown -->
                    <Menu as="div" class="relative ml-3">
                        <div>
                            <MenuButton class="flex text-sm rounded-full">
                                <span class="sr-only">Open user menu</span>
                                <img class="object-cover w-8 h-8 rounded-full" :src="$page.props.user?.profile_photo_url" alt="" />
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                            <MenuItems class="absolute right-0 w-48 py-1 mt-2 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <template v-for="item in dropdown" :key="item.name + item.href">
                                    <MenuItem v-if="item.show" v-slot="{ active }">
                                        <Link :href="item.href" :class="[active ? 'bg-gray-100' : '', 'block px-4 py-2 text-sm text-gray-700']">{{ item.name }}</Link>
                                    </MenuItem>
                                </template>

                                <MenuItem>
                                    <Link :href="route('logout')" method="post" class="block w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100" as="button">Sign out</Link>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
        </div>
        <DisclosurePanel class="sm:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <Link v-for="item in navigation" :key="item.name" :href="item.href" :class="[item.active ? 'bg-indigo-500 text-white dark:bg-gray-800' : 'text-gray-600 hover:bg-indigo-400 hover:text-white dark:hover:bg-gray-900 hover:text-white dark:text-gray-300', 'block px-3 py-2 rounded-md text-base font-medium']" :aria-current="item.active ? 'page' : undefined">
                    {{ item.name }}
                </Link>
            </div>
        </DisclosurePanel>
    </Disclosure>
</template>

<script>
    import {
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems
    } from '@headlessui/vue'

    import {
        BellIcon,
        MenuIcon,
        XIcon
    } from '@heroicons/vue/outline'

    import { Link } from '@inertiajs/inertia-vue3';

    import ColorSchemeToggle from '@/PICT/ColorSchemeToggle.vue'

    import JetApplicationMark from '@/Jetstream/ApplicationMark.vue'
    import JetApplicationLogo from '@/Jetstream/ApplicationLogo.vue'

    export default {
        components: {
            Disclosure,
            DisclosureButton,
            DisclosurePanel,
            Menu,
            MenuButton,
            MenuItem,
            MenuItems,
            BellIcon,
            MenuIcon,
            XIcon,
            Link,
            ColorSchemeToggle,
            JetApplicationMark,
            JetApplicationLogo,
        },

        props: {
            navigation: Array,
            dropdown: Array,
        },
    }
</script>
