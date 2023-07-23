<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import DefaultAvatar from '/resources/assets/default_avatar.gif'
import Application from '@/Components/Icons/Application.vue'

const authenticated = Boolean(usePage().props.auth.user)

const navigation = [

]

const dropdown = [

]
</script>

<template>
    <div class="sticky top-0 z-10 bg-base-200">
        <nav class="py-0 mx-auto navbar sm:px-6 lg:px-8 max-w-7xl">
            <div class="navbar-start">
                <div v-if="authenticated" class="dropdown">
                    <label tabindex="0" class="btn btn-accent lg:hidden">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            height="24"
                            width="24"
                            viewBox="0 0 448 512"
                        >
                            <path
                                d="M0 96C0 78.3 14.3 64 32 64H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32C14.3 128 0 113.7 0 96zM0 256c0-17.7 14.3-32 32-32H416c17.7 0 32 14.3 32 32s-14.3 32-32 32H32c-17.7 0-32-14.3-32-32zM448 416c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32s14.3-32 32-32H416c17.7 0 32 14.3 32 32z"
                            />
                        </svg>
                    </label>
                    <ul
                        tabindex="0"
                        class="p-2 mt-3 shadow menu menu-compact tabs tabs-boxed dropdown-content bg-base-100 rounded-box w-52"
                    >
                        <li v-for="item in navigation" :key="`${item.name}${item.href}`" class="w-full">
                            <Link :href="item.href" :class="[item.current ? 'tab-active' : '', 'tab']">
                                {{ item.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
                <Link
                    :href="$page.props.ziggy.url"
                    :class="[authenticated ? 'hidden lg:flex' : 'flex']"
                    class="items-center gap-3 text-xl font-bold normal-case lg:flex fill-base-content"
                >
                    <Application /> {{ $page.props.app.name }}
                </Link>
                <div v-if="authenticated" class="items-end hidden h-full ml-5 lg:flex">
                    <ul class="flex p-0">
                        <li v-for="item in navigation" :key="`${item.name}${item.href}`">
                            <Link
                                :href="item.href"
                                :class="[
                                    item.current ? 'border-primary-content' : '',
                                    'tab tab-bordered p-0 px-3 text-base-content mt-[0.6em]'
                                ]"
                            >
                                {{ item.name }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="navbar-center" :class="[authenticated ? 'lg:hidden' : 'hidden']">
                <Link
                    :href="$page.props.ziggy.url"
                    class="flex items-center gap-3 text-xl font-bold normal-case fill-base-content"
                >
                    <Application /> {{ $page.props.app.name }}
                </Link>
            </div>
            <div class="navbar-end">
                <Link
                    v-if="!authenticated && !route().current('login')"
                    as="button"
                    :href="route('login')"
                    class="ml-4 normal-case btn btn-outline btn-sm"
                >
                    Sign In
                </Link>
                <div v-if="authenticated" class="dropdown dropdown-end">
                    <label tabindex="0" class="btn btn-ghost mask mask-squircle avatar">
                        <div class="w-10 fill-current mask mask-squircle">
                            <img :src="DefaultAvatar" alt="" />
                        </div>
                    </label>
                    <ul
                        tabindex="0"
                        class="p-2 mt-3 shadow menu menu-compact dropdown-content bg-base-100 rounded-box w-52"
                    >
                        <li v-for="item in dropdown">
                            <Link as="button" :href="item.href" :method="item.method ?? 'GET'" class="justify-between">
                                {{ item.name }}
                                <span v-if="item.badge" class="badge bg-accent">
                                    {{ item.badge }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>
</template>
