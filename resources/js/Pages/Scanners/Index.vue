<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import { ref } from 'vue'

const props = defineProps({
    scanners: Object,
    search: String,
})

const dataTable = ref(null)
</script>

<template>
    <AppLayout title="Biometrics">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Scanners
            </h2>
        </template>

        <div class="px-4 py-5 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <DataTable :items="scanners" ref="dataTable" :compact="true" class="table-sm">
                <template #actions>
                    <div class="flex items-end content-end justify-end w-full h-full">
                        <div class="tooltip" data-tip="Create">
                            <button class="tracking-tighter btn btn-sm btn-square btn-primary">
                                <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                    <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <template #head>
                    <tr>
                        <th class="px-4 py-3 w-[48px]"> # </th>
                        <th class="px-2 py-3"> Name </th>
                        <th class="px-2 py-3"> Assignees </th>
                        <th class="px-2 py-3"> Created </th>
                    </tr>
                </template>

                <template #default="{row, index}">
                    <tr>
                        <td class="px-4 py-2">
                            {{ index }}
                        </td>
                        <td class="p-2 font-mono">
                            {{ row.name }}
                        </td>
                        <td class="p-2 font-mono">
                            {{ row.assignees?.join(', ') }}
                        </td>
                        <td class="p-2 font-mono whitespace-nowrap overflow-clip">
                            {{ row.created_at }}
                        </td>
                    </tr>
                </template>
            </DataTable>
        </div>
    </AppLayout>
</template>
