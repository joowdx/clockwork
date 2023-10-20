<script setup>
import Navbar from './Partials/Navbar.vue'
import Footer from './Partials/Footer.vue'
import Banner from './Partials/Banner.vue'
</script>

<template>
    <div class="flex flex-col min-h-screen">
        <section>
            <Banner class="bg-opacity-100" />

            <Navbar />
        </section>

        <main class="flex-grow py-8 mx-auto max-w-7xl sm:px-6 lg:px-8" v-bind="$attrs">
            <slot />
        </main>

        <Footer />
    </div>
</template>
