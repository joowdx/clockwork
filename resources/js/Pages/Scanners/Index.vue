<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import ScannerInformationModal from './Partials/ScannerInformationModal.vue'
import { nextTick, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    scanners: Object,
    users: Object,
    search: String,
    show: String,
})

const hasPrivilege = usePage().props.auth.user.administrator

const dataTable = ref(null)

const modal = ref(false)

const scanner = ref(null)

watch(() => usePage().props.flash?.scanner, (scanner) => {
    if (scanner === undefined) {
        return
    }

    nextTick(() => showScannerModal(scanner))
})

const showScannerModal = (e) => {
    scanner.value = e
    modal.value = true
}

onMounted(() => {
    if (props.show) {
        const scanner = props.scanners.data.find(e => e.id === props.show)

        if (scanner) {
            nextTick(() => showScannerModal(scanner))
        }
    }
})
</script>

<template>
    <AppLayout title="Biometrics">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Scanners
            </h2>
        </template>

        <div class="px-4 py-5 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <DataTable :items="scanners" ref="dataTable" :compact="true" class="table-sm" wrapperClass="h-[calc(100vh-329px)] min-h-[29em]">
                <template #actions>
                    <div class="flex items-end content-end justify-end flex-1 w-full">
                        <div class="tooltip" data-tip="Create">
                            <button @click="showScannerModal(null)" class="tracking-tighter btn btn-sm btn-square btn-primary" :disabled="! hasPrivilege">
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
                        <th class="px-2 py-3"> IP Address </th>
                        <th class="px-2 py-3"> Created </th>
                        <th class="w-16 px-2 py-0 opacity-0">
                            <button class="cursor-default btn btn-primary btn-xs">
                                Edit
                            </button>
                        </th>
                    </tr>
                </template>

                <template #default="{row, index}">
                    <tr class="group">
                        <td class="px-4 py-2">
                            {{ index }}
                        </td>
                        <td class="p-2 font-mono">
                            {{ row.name }}
                        </td>
                        <td class="p-2 font-mono">
                            {{ row.assignees?.join(', ') }}
                        </td>
                        <td class="p-2 font-mono">
                            {{ row.ip_address }}
                        </td>
                        <td class="p-2 font-mono whitespace-nowrap overflow-clip">
                            {{ row.created_at }}
                        </td>
                        <td class="px-2 py-0 text-right">
                            <button @click="showScannerModal(row)" class="hidden group-hover:block btn btn-primary btn-xs">
                                Edit
                            </button>
                        </td>
                    </tr>
                </template>
            </DataTable>
        </div>

        <ScannerInformationModal v-model="modal" v-model:scanner="scanner" :users="users" />
    </AppLayout>
</template>
