<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import AlertForm from './Partials/AlertForm.vue'
</script>

<template>
    <AppLayout title="Configuration">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Configuration
            </h2>
        </template>

        <div class="flex flex-col items-stretch gap-8 py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <AlertForm class="w-full" />
        </div>
    </AppLayout>
</template>
