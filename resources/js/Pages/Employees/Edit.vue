<template>
    <app-layout title="Employees">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                <Link class="underline" :href="route('employees.index')">Employees</Link> / {{ $page.props.employee.name_format.shortStartLastInitialFirst }}
            </h2>
        </template>

        <div>
            <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <UpdateEmployeeInformationForm />

                <JetSectionBorder />

                <UpdateScannersInformationForm />
            </div>
        </div>
    </app-layout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
    import UpdateEmployeeInformationForm from '@/Pages/Employees/Partials/UpdateEmployeeInformationForm.vue'
    import UpdateScannersInformationForm from '@/Pages/Employees/Partials/UpdateScannersInformationForm.vue'

    export default defineComponent({
        components: {
            AppLayout,
            Link,
            JetSectionBorder,
            UpdateEmployeeInformationForm,
            UpdateScannersInformationForm,
        },


    })
</script>
