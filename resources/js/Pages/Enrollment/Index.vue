<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3';

const props = defineProps(['data', 'paginate', 'page', 'scanner'])

console.log(props.data)
</script>

<template>
    <AppLayout title="Enrollment">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Enrollments
            </h2>
        </template>

        <div class="px-4 py-5 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <DataTable :items="data" class="table-sm table-pin-rows">
                <template #actions>
                    <div class="flex items-end content-end justify-end flex-1 order-first w-full md:order-none">
                        <div class="tooltip" data-tip="Export">
                            <a :href="route('enrollment.export')" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512">
                                    <path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V288H216c-13.3 0-24 10.7-24 24s10.7 24 24 24H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zM384 336V288H494.1l-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39H384zm0-208H256V0L384 128z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </template>

                <template #head>
                    <tr>
                        <th class="w-3/4 px-2 py-3">Scanner</th>
                        <th class="w-1/4 px-2 py-3">UID</th>
                    </tr>
                </template>

                <template #default="{row}">
                    <tr class="group bg-opacity-40 hover">
                        <template v-if="row.header">
                            <th colspan="2" class="text-accent">
                                {{ row.header }}
                            </th>
                        </template>
                        <template v-else>
                            <td>
                                {{ row.scanner }}
                            </td>
                            <td class="font-mono">
                                {{ row.uid }}
                            </td>
                        </template>
                    </tr>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
