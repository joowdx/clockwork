<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DataTable from '@/Components/DataTable.vue';
import { reactive, ref, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/inertia-vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    employees: Object,
    offices: Object,
    groups: Object,
    scanners: Object,
    office: String,
    group: String,
    active: {
        type: [Boolean, String],
        default: true,
    },
    regular: {
        type: [Boolean, String],
        default: undefined,
    },
})

const args = ref({
    by: 'employee',
    csc_format: true,
    period: usePage().props.value.period,
    month: usePage().props.value.month,
    employees: [],
})

const printPreview = ref(null)
const dataTable = ref(null)
const all = ref(null)
const importModal = ref(false)
const loaded = ref(false)
const loading = ref(false)
let flag = false
let init = true

const queryStrings = reactive({
    active: props.active,
    regular: props.regular,
    office: props.office,
    group: props.group,
})

const form = useForm({
    scanner: null,
    file: null,
})

const clearSelection = () => {
    args.value.employees = []
    all.value.checked = false
    all.value.indeterminate = false
}

const toggleSelection = () => {
    flag = true

    if (all.value.indeterminate || !all.value.checked) {
        args.value.employees = []

        flag = false
        return
    } else {
        args.value.employees = props.employees.data.map(e => e.id)

        flag = false
        return
    }
}

const generate = () => {
    loaded.value = false

    loading.value = true

    fetch(route('print', args.value))
        .then(async function (response) {
            if (response.ok) {
                return await response.blob()
            }

            throw new Error(await response.text())
        })
        .then(function (response) {
            printPreview.value.setAttribute("src", URL.createObjectURL(response))
        })
        .catch(function () {
            printPreview.value.removeAttribute("src")

            Swal.fire(
                'Something went wrong.',
                'A page refresh might help.',
                'error'
            )
        })
        .finally(() => {
            loading.value = false
        })
}

const closePreview = () => {
    loaded.value = false

    printPreview.value.removeAttribute('src')
}

const upload = () => {

    form.post(route('timelogs.store'), {
        preserveScroll: true,
        only: ['scanners', 'employees'],
        onSuccess: () => {
            form.reset()
        },
    });
}

watch(() => args.value.employees, function (n) {
    closePreview()

    if (flag) {
        return
    }

    if (props.employees.data.every(e => n.includes(e.id))) {
        all.value.indeterminate = false
        all.value.checked = true
        return
    } else if (n.length == 0) {
        all.value.indeterminate = false
        all.value.checked = false
        return
    }
    all.value.indeterminate = true
}, { flush: 'sync' })
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Time Logs
            </h2>
        </template>

        <div class="px-4 py-12 sm:px-0">
            <div class="mx-auto space-y-3 max-w-7xl sm:px-6 lg:px-8 bg-gray" style="margin-top:-20px!important">
                <div class="flex self-end justify-end col-span-4 mt-3 space-x-3 justify-self-end">
                    <div v-if="loading" class="flex items-center">
                        <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                        </svg>
                    </div>

                    <div class="tooltip" data-tip="Import attlogs">
                        <label for="import-modal" class="tracking-tighter btn btn-xs">
                            Import
                        </label>
                    </div>

                    <div class="tooltip" data-tip="Generate printable form">
                        <button @click="generate" :disabled="!args.employees.length || !args.month || loading" class="tracking-tighter btn btn-xs">
                            Generate
                        </button>
                    </div>

                    <div class="tooltip" data-tip="Print">
                        <button @click="this.$refs.printPreview.contentWindow.print()" :disabled="! loaded || ! printPreview.hasAttribute('src')" class="tracking-tighter btn btn-xs">
                            Print
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-12 col-span-12 gap-3 pb-3 border-b border-primary-content">
                    <div class="col-span-6 form-control col sm:col-span-3 md:col-span-2">
                        <label for="print-format" class="p-0 label">
                            <span class="label-text">Format</span>
                        </label>
                        <select v-model="args.csc_format" id="print-format" class="col-span-6 select-sm sm:col-span-3 select select-bordered">
                            <option value="null">Preferred</option>
                            <option :value="false">Default</option>
                            <option :value="true">CSC Form No. 48</option>
                        </select>
                    </div>

                    <div class="col-span-6 form-control col sm:col-span-3 md:col-span-2">
                        <label for="period" class="p-0 label">
                            <span class="label-text">Period</span>
                        </label>
                        <select v-model="args.period" id="period" class="col-span-6 select-sm sm:col-span-3 select select-bordered">
                            <option value="full">Full</option>
                            <option>1st</option>
                            <option>2nd</option>
                        </select>
                    </div>

                    <div class="col-span-6 form-control col sm:col-span-3 md:col-span-2">
                        <label for="month" class="p-0 label">
                            <span class="label-text">Month</span>
                        </label>
                        <input v-model="args.month" id="month" class="input input-bordered input-sm" type="month">
                    </div>
                </div>

                <DataTable ref="dataTable" :items="employees" :query-strings="queryStrings" :compact="true" @updated="clearSelection">
                    <template #preColHead>
                        <input @change="toggleSelection" id="all" ref="all" class="mx-2 checkbox checkbox-xs" type="checkbox">
                    </template>

                    <template #preColCell="{id}">
                        <div class="flex py-1 mx-4 align-bottom">
                            <input v-model="args.employees" :value="id" class="checkbox checkbox-xs" type="checkbox">
                        </div>
                    </template>

                    <template #actions>
                        <div>
                            <div class="grid grid-cols-12 col-span-12 gap-3">
                                <select aria-label="Status" class="col-span-6 select-sm sm:col-span-3 select select-bordered" v-model="queryStrings.regular" :disabled="dataTable?.processing">
                                    <option :value="undefined">status</option>
                                    <option :value="true">regular</option>
                                    <option :value="false">jo, cos, etc.</option>
                                </select>
                                <select aria-label="Office" class="col-span-6 select-sm sm:col-span-3 select select-bordered" v-model="queryStrings.office" :disabled="dataTable?.processing">
                                    <option :value="undefined">office</option>
                                    <option v-for="office in offices"> {{ office }} </option>
                                </select>
                                <select aria-label="Group" v-if="groups" v-model="queryStrings.group" class="col-span-6 select-sm sm:col-span-3 select select-bordered" :disabled="dataTable?.processing">
                                    <option :value="undefined">group</option>
                                    <option v-for="group in groups"> {{ group }} </option>
                                </select>
                                <select aria-label="Active" class="col-span-6 select-sm sm:col-span-3 select select-bordered" v-model="queryStrings.active" :disabled="dataTable?.processing">
                                    <option :value="true">active</option>
                                    <option :value="false">inactive</option>
                                </select>
                            </div>
                        </div>
                    </template>
                </DataTable>
            </div>
            <Teleport to="body">
                <iframe class="w-full h-screen" title="DTR" ref="printPreview" hidden @load="loaded=init?false:true;init=false;" frameborder="0"></iframe>
            </Teleport>

            <Teleport to="body">
                <input v-model="importModal" type="checkbox" id="import-modal" class="modal-toggle" />

                <div class="modal">
                    <div class="relative modal-box">
                        <div class="absolute tooltip right-3 top-3 tooltip-left" data-tip="Close">
                            <button type="button" @click="importModal = false" :disabled="form.processing" class="btn btn-sm btn-circle">
                                ✕
                            </button>
                        </div>
                        <h3 class="text-lg font-bold">Upload attlogs</h3>
                        <div class="grid gap-3 py-5">
                            <div class="form-control">
                                <label for="scanner" class="px-0 label">
                                    <span class="label-text">Scanner</span>
                                </label>
                                <select v-model="form.scanner" :disabled="form.processing" id="scanner" aria-label="Scanner" class="w-full select select-bordered select-sm">
                                    <option hidden :value="null">Select scanner</option>
                                    <option v-for="scanner in scanners" :value="scanner.id">
                                        {{ scanner.name }}
                                    </option>
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="px-0 label">
                                    <span class="label-text">Scanner</span>
                                </label>
                                <input ref="fileInput" @input="form.file = $event.target.files[0]" :disabled="form.processing" type="file" class="w-full file-input file-input-bordered file-input-sm"/>
                            </div>
                        </div>

                        <div class="modal-action">
                            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                <div class="flex items-center">
                                    <p v-if="form.recentlySuccessful" class="mr-3 text-sm text-gray-600 dark:text-gray-400">
                                        Success!
                                    </p>
                                </div>
                            </Transition>

                            <div v-if="form.processing" class="flex items-center mr-3 align-middle">
                                <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                                </svg>
                            </div>

                            <button type="button" @click="upload" :disabled="form.processing" class="btn btn-sm">Submit</button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </AppLayout>
</template>
