<template>
    <AppLayout title="Employees">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                <Link class="underline" :href="route('employees.index')">Employees</Link> / {{ $page.props.employee.name_format.shortStartLastInitialFirst }}
            </h2>
        </template>

        <div>
            <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <EmployeeInformationFormVue />

                <JetSectionBorder />

                <EmployeeGroupsFormVue />

                <JetSectionBorder />

                <ScannersInformationFormVue />

                <JetSectionBorder />

                <DeleteEmployeeFormVue />
            </div>
        </div>
    </AppLayout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetSectionBorder from '@/Jetstream/SectionBorder.vue'
    import DeleteEmployeeFormVue from './Partials/DeleteEmployeeForm.vue'
    import EmployeeInformationFormVue from './Partials/EmployeeInformationForm.vue'
    import EmployeeGroupsFormVue from './Partials/EmployeeGroupsForm.vue'
    import ScannersInformationFormVue from './Partials/ScannersInformationForm.vue'

    export default defineComponent({
        components: {
            AppLayout,
            Link,
            JetSectionBorder,
            DeleteEmployeeFormVue,
            EmployeeInformationFormVue,
            EmployeeGroupsFormVue,
            ScannersInformationFormVue,
        },


    })
</script>
