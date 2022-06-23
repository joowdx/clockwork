<template>
    <AppLayout title="Employees">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                <Link class="underline" :href="route('employees.index')">Employees</Link> / Create
            </h2>
        </template>

        <div>
            <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <EmployeeInformationFormVue />
            </div>
        </div>
    </AppLayout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
    import EmployeeInformationFormVue from './Partials/EmployeeInformationForm.vue'
    import ScannersInformationFormVue from './Partials/ScannersInformationForm.vue'

    export default defineComponent({
        components: {
            AppLayout,
            Link,
            JetSectionBorder,
            EmployeeInformationFormVue,
            ScannersInformationFormVue,
        },
    })
</script>
