<script setup>
import { Link } from '@inertiajs/vue3'
import ApplicationBanner from '@/Components/Icons/ApplicationBanner.vue'
</script>

<template>
    <div class="sticky top-0 z-10 bg-base-200">
        <nav class="py-0 mx-auto navbar sm:px-6 lg:px-8 max-w-7xl">
            <div class="navbar-start">
                <Link
                    :href="$page.props.ziggy.url"
                    class="flex items-center gap-3 text-xl font-bold normal-case lg:flex"
                >
                    <ApplicationBanner class="h-8" />
                </Link>
            </div>
            <div class="navbar-end">
                <Link
                    v-if="!route().current('download')"
                    as="button"
                    :href="route('download')"
                    class="ml-4 normal-case btn btn-primary btn-sm"
                >
                    Download
                </Link>
                <Link
                    v-if="!route().current('query.search')"
                    as="button"
                    :href="route('query.search')"
                    class="ml-4 normal-case btn btn-primary btn-sm"
                >
                    Query
                </Link>
                <Link
                    v-if="!route().current('login')"
                    as="button"
                    :href="route('login')"
                    class="ml-4 normal-case btn btn-primary btn-sm"
                >
                    Sign In
                </Link>
            </div>
        </nav>
    </div>
</template>
