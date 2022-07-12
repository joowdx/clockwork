<template>
    <AppLayout title="Scanners">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                <Link class="underline" :href="route('scanners.index')">Scanners</Link> / <span class="uppercase">{{ $page.props.scanner.name }}</span>
            </h2>
        </template>

        <div>
            <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">

                <ScannerInformation />

                <!-- <JetSectionBorder /> -->
            </div>
        </div>
    </AppLayout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
    import ScannerInformation from './Partials/ScannerInformation.vue'
    import Assignees from './Partials/Assignees.vue'

    export default defineComponent({
        components: {
            AppLayout,
            Link,
            JetSectionBorder,
            ScannerInformation,
            Assignees,
        },


    })
</script>
