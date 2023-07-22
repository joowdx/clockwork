<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import EmployeeModal from './Partials/EmployeeModal.vue'
import ImportModal from './Partials/ImportModal.vue'
import OptionsModal from './Partials/OptionsModal.vue'
import SearchModal from './Partials/SearchModal.vue'
import SynchronizeModal from './Partials/SynchronizeModal.vue'
import { reactive, ref, watch } from 'vue'
import { usePage } from '@inertiajs/inertia-vue3'

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

const config = ref({})

const args = ref({
    by: 'employee',
    csc_format: true,
    period: usePage().props.value.period,
    month: usePage().props.value.month,
    employees: [],
    from: usePage().props.value.from,
    to: usePage().props.value.to,
})

const dtrPreview = ref(null)
const transmittalPreview = ref(null)
const dataTable = ref(null)
const all = ref(null)
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

const generate = async () => {
    loaded.value = false

    loading.value = true

    async function loadPreview(transmittal = false) {
        await fetch(route('print', { by: 'employee' }), {
            method: 'POST',
            body: JSON.stringify({
                ...args.value,
                ...config.value,
                ...(transmittal ? { transmittal: true } : {}),
            }),
            headers: {
                'Content-Type': 'application/json',
            }
        })
            .then(async function (response) {
                if (response.ok) {
                    return await response.blob()
                }

                throw new Error(await response.text())
            })
            .then(function (response) {
                (transmittal ? transmittalPreview :dtrPreview).value.setAttribute("src", URL.createObjectURL(response))
            })
            .catch(function () {
                (transmittal ? transmittalPreview :dtrPreview).value.removeAttribute("src")
            })
    }

    await loadPreview()

    await loadPreview(true)

    loading.value = false
}

const closePreview = () => {
    loaded.value = false

    dtrPreview.value.removeAttribute('src')

    transmittalPreview.value.removeAttribute('src')
}

const select = (id) => {

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

const modal = ref({
    employee: false,
    import: false,
    search: false,
    sync: false,
    options: false,
})
</script>

<template>
    <AppLayout title="Home">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Home
            </h2>
        </template>

        <div class="px-4 py-10 sm:px-0">
            <div class="mx-auto space-y-3 max-w-7xl sm:px-6 lg:px-8 bg-gray" style="margin-top:-20px!important">
                <div class="grid grid-cols-12 col-span-12 gap-3">
                    <div class="col-span-6 form-control col sm:col-span-3 md:col-span-2">
                        <label for="print-format" class="p-0 text-xs label">
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
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <template v-if="args.period !== 'custom'">
                        <div class="col-span-6 form-control col sm:col-span-3 md:col-span-2">
                            <label for="month" class="p-0 label">
                                <span class="label-text">Month</span>
                            </label>
                            <input v-model="args.month" id="month" class="input input-bordered input-sm" type="month">
                        </div>

                        <div class="col-span-6 sm:col-span-3 md:col-span-2">

                        </div>
                    </template>

                    <template v-else>
                        <div class="col-span-6 form-control col sm:col-span-3 md:col-span-2">
                            <label for="from" class="p-0 label">
                                <span class="label-text">From</span>
                            </label>
                            <input v-model="args.from" id="from" class="input input-bordered input-sm" type="date">
                        </div>

                        <div class="col-span-6 form-control col sm:col-span-3 md:col-span-2">
                            <label for="to" class="p-0 label">
                                <span class="label-text">To</span>
                            </label>
                            <input v-model="args.to" id="to" class="input input-bordered input-sm" type="date">
                        </div>
                    </template>

                    <template v-if="true">
                        <div class="flex flex-wrap self-end justify-end col-span-4 gap-2 mt-3 justify-self-end">
                            <div v-if="loading" class="flex items-center">
                                <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                    <path d="M222.7 32.1c5 16.9-4.6 34.8-21.5 39.8C121.8 95.6 64 169.1 64 256c0 106 86 192 192 192s192-86 192-192c0-86.9-57.8-160.4-137.1-184.1c-16.9-5-26.6-22.9-21.5-39.8s22.9-26.6 39.8-21.5C434.9 42.1 512 140 512 256c0 141.4-114.6 256-256 256S0 397.4 0 256C0 140 77.1 42.1 182.9 10.6c16.9-5 34.8 4.6 39.8 21.5z"/>
                                </svg>
                            </div>

                            <!-- <div class="tooltip" data-tip="Register">
                                <button @click="modal.employee = true" class="tracking-tighter btn btn-sm btn-square">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/>
                                    </svg>
                                </button>
                            </div> -->

                            <div class="tooltip" data-tip="Synchronize">
                                <button @click="modal.sync = true" class="tracking-tighter btn btn-sm btn-square">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Search">
                                <button @click="modal.search = true" class="tracking-tighter btn btn-sm btn-square">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Import">
                                <button @click="modal.import = true" class="tracking-tighter btn btn-sm btn-square">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M288 109.3V352c0 17.7-14.3 32-32 32s-32-14.3-32-32V109.3l-73.4 73.4c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l128-128c12.5-12.5 32.8-12.5 45.3 0l128 128c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L288 109.3zM64 352H192c0 35.3 28.7 64 64 64s64-28.7 64-64H448c35.3 0 64 28.7 64 64v32c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V416c0-35.3 28.7-64 64-64zM432 456a24 24 0 1 0 0-48 24 24 0 1 0 0 48z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Options">
                                <button @click="modal.options = true" class="tracking-tighter btn btn-sm btn-square">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.5 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Generate">
                                <button @click.exact="generate" :disabled="!args.employees.length || !args.month || loading" class="tracking-tighter btn btn-sm btn-square">
                                    <svg  class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 576 512">
                                        <path d="M0 64C0 28.7 28.7 0 64 0H224V128c0 17.7 14.3 32 32 32H384V288H216c-13.3 0-24 10.7-24 24s10.7 24 24 24H384V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V64zM384 336V288H494.1l-39-39c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l80 80c9.4 9.4 9.4 24.6 0 33.9l-80 80c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l39-39H384zm0-208H256V0L384 128z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Print">
                                <button @click.exact="dtrPreview.contentWindow.print()" @click.alt.exact="transmittalPreview.contentWindow.print()" :disabled="! loaded || ! dtrPreview.hasAttribute('src')" class="tracking-tighter btn btn-sm btn-square">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M128 0C92.7 0 64 28.7 64 64v96h64V64H354.7L384 93.3V160h64V93.3c0-17-6.7-33.3-18.7-45.3L400 18.7C388 6.7 371.7 0 354.7 0H128zM384 352v32 64H128V384 368 352H384zm64 32h32c17.7 0 32-14.3 32-32V256c0-35.3-28.7-64-64-64H64c-35.3 0-64 28.7-64 64v96c0 17.7 14.3 32 32 32H64v64c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V384zM432 248a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <DataTable ref="dataTable" :items="employees" :query-strings="queryStrings" :compact="true" @updated="clearSelection">
                    <template #actions>
                        <div>
                            <div class="grid grid-cols-12 col-span-12 gap-3">
                                <div class="col-span-6 form-control sm:col-span-3">
                                    <label for="period" class="p-0 label">
                                        <span class="label-text">Status</span>
                                    </label>
                                    <select aria-label="Status" class="select select-bordered select-sm" v-model="queryStrings.regular" :disabled="dataTable?.processing">
                                        <option :value="undefined">status</option>
                                        <option :value="true">regular</option>
                                        <option :value="false">jo, cos, etc.</option>
                                    </select>
                                </div>

                                <div class="col-span-6 form-control sm:col-span-3">
                                    <label for="period" class="p-0 label">
                                        <span class="label-text">Office</span>
                                    </label>
                                    <select aria-label="Office" class="select-sm select select-bordered" v-model="queryStrings.office" :disabled="dataTable?.processing">
                                        <option :value="undefined">office</option>
                                        <option v-for="office in offices"> {{ office }} </option>
                                    </select>
                                </div>

                                <div class="col-span-6 form-control sm:col-span-3">
                                    <label for="period" class="p-0 label">
                                        <span class="label-text">Group</span>
                                    </label>
                                    <select aria-label="Group" v-if="groups" v-model="queryStrings.group" class="select-sm select select-bordered" :disabled="dataTable?.processing">
                                        <option :value="undefined">group</option>
                                        <option v-for="group in groups"> {{ group }} </option>
                                    </select>
                                </div>


                                <div class="col-span-6 form-control sm:col-span-3">
                                    <label for="period" class="p-0 label">
                                        <span class="label-text">Active</span>
                                    </label>

                                    <select aria-label="Active" class="select-sm select select-bordered" v-model="queryStrings.active" :disabled="dataTable?.processing">
                                        <option :value="true">active</option>
                                        <option :value="false">inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #head>
                        <tr>
                            <th class="p-0 w-[40px] max-w-[40px] z-10">
                                <label class="flex justify-center">
                                    <input @change="toggleSelection" id="all" ref="all" class="mx-2 checkbox checkbox-xs" type="checkbox">
                                </label>
                            </th>
                            <th class="px-2 py-3 w-96 min-w-96">Name</th>
                            <th class="px-2 py-3 w-36">Status</th>
                            <th class="w-48 px-2 py-3">Office</th>
                            <th class="px-2 py-3 w-36">Groups</th>
                            <th class="w-12 px-2 py-3"></th>
                        </tr>
                    </template>

                    <template #default="{row}">
                        <tr>
                            <th class="p-0 bg-[transparent!important;]">
                                <label class="flex justify-center p-2 cursor-pointer">
                                    <input :id="`employee-selection-${row.id}`" v-model="args.employees" :value="row.id" class="checkbox checkbox-xs" type="checkbox">
                                </label>
                            </th>
                            <td class="p-0">
                                <label :for="`employee-selection-${row.id}`" class="block w-full p-2 cursor-pointer select-none">
                                    {{ row.name }}
                                </label>
                            </td>
                            <td class="p-0">
                                <label :for="`employee-selection-${row.id}`" class="block w-full p-2 cursor-pointer select-none">
                                    {{ row.status }}
                                </label>
                            </td>
                            <td class="p-0">
                                <label :for="`employee-selection-${row.id}`" class="block w-full p-2 cursor-pointer select-none">
                                    {{ row.office }}
                                </label>
                            </td>
                            <td class="overflow-visible text-ellipsis whitespace-nowrap min-w-[fit-content] p-0">
                                <label :for="`employee-selection-${row.id}`" class="block w-full p-2 cursor-pointer select-none">
                                    {{ row.groups }}
                                </label>
                            </td>
                            <td>

                            </td>
                            <!-- <th class="p-0 px-2 text-right bg-[transparent!important;]">
                                <button class="px-1 btn btn-xs btn-primary">
                                    <svg class="h-2.5 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 128 512">
                                        <path d="M64 360a56 56 0 1 0 0 112 56 56 0 1 0 0-112zm0-160a56 56 0 1 0 0 112 56 56 0 1 0 0-112zM120 96A56 56 0 1 0 8 96a56 56 0 1 0 112 0z"/>
                                    </svg>
                                </button>
                            </th> -->
                        </tr>
                    </template>
                </DataTable>
            </div>
        </div>

        <EmployeeModal v-model="modal.employee" />

        <ImportModal v-model="modal.import" :scanners="scanners" />

        <OptionsModal v-model="modal.options" v-model:data="config" />

        <SearchModal v-model="modal.search" />

        <SynchronizeModal v-model="modal.sync" :scanners="scanners" />

        <Teleport to="body">
            <iframe class="w-full h-screen" title="DTR" ref="dtrPreview" hidden @load="loaded=init?false:true;init=false;" frameborder="0"></iframe>
            <iframe class="w-full h-screen" title="DTR" ref="transmittalPreview" hidden @load="loaded=init?false:true;init=false;" frameborder="0"></iframe>
        </Teleport>
    </AppLayout>
</template>
