<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue'

const props = defineProps({
    users: Object,
    search: String,
})
</script>

<template>
    <AppLayout title="Users">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Users
            </h2>
        </template>

        <div class="px-4 py-5 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <DataTable :items="users" ref="dataTable" :compact="true" class="table-sm">

            </DataTable>
        </div>

    </AppLayout>
</template>
