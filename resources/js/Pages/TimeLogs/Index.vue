<template>
    <app-layout title="Logs">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Time Logs
            </h2>
        </template>
    </app-layout>
</template>

<script>
    import { defineComponent } from 'vue'
    import AppLayout from '@/Layouts/AppLayout.vue'

    export default defineComponent({
        components: {
            AppLayout,
        },
    })
</script>
