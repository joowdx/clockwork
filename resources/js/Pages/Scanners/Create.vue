<template>
    <AppLayout title="Scanners">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                <Link class="underline" :href="route('scanners.index')">Scanners</Link> / Create
            </h2>
        </template>

        <div>
            <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">

                <ScannerInformation />

            </div>
        </div>
    </AppLayout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import ScannerInformation from './Partials/ScannerInformation.vue'

    export default defineComponent({
        components: {
            AppLayout,
            Link,
            ScannerInformation,
        },
    })
</script>
