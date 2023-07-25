<script setup>
import { usePage } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import EmployeeModal from './Partials/EmployeeModal.vue'
import ImportModal from './Partials/ImportModal.vue'
import OptionsModal from './Partials/OptionsModal.vue'
import SearchModal from './Partials/SearchModal.vue'
import SettingsModal from './Partials/SettingsModal.vue'
import SynchronizeModal from './Partials/SynchronizeModal.vue'

const props = defineProps([
    'employees',
    'offices',
    'groups',
    'scanners',
    'office',
    'group',
    'active',
    'regular',
    'all',
    'unenrolled',
])

const args = ref({
    by: 'employee',
    csc_format: true,
    period: usePage().props.period,
    month: usePage().props.month,
    employees: [],
    from: usePage().props.from,
    to: usePage().props.to,
})

const queryStrings = ref({
    active: props.active,
    regular: props.regular,
    office: props.office,
    group: props.group,
    all: props.all,
    unenrolled: props.unenrolled,
})

const config = ref({})

const settings = ref({
    all: queryStrings.value.all,
    unenrolled: queryStrings.value.unenrolled,
})

const employee = ref(null)

const dtrPreview = ref(null)
const transmittalPreview = ref(null)
const dataTable = ref(null)
const all = ref(null)
const loading = ref(false)

let flag = false

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

watch(args, function (args) {
    closePreview()

    if (flag) {
        return
    }

    if (props.employees.data.every(e => args.employees.includes(e.id))) {
        all.value.indeterminate = false
        all.value.checked = true
        return
    } else if (args.employees.length == 0) {
        all.value.indeterminate = false
        all.value.checked = false
        return
    }
    all.value.indeterminate = true
}, { deep: true, flush: 'sync' })

const modal = ref({
    employee: false,
    import: false,
    search: false,
    settings: false,
    sync: false,
    options: false,
})

watch(settings, (settings) => {
    queryStrings.value.all = settings.all ? true : null
    queryStrings.value.unenrolled = settings.unenrolled ? settings.unenrolled : null
}, { deep: true })

const loadPreview = async (transmittal = false) => {
    return await fetch(route('print', { by: 'dtr' }), {
        method: 'POST',
        body: JSON.stringify({
            ...args.value,
            ...config.value,
            ...(transmittal ? { transmittal : true } : {}),
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
            (transmittal ? transmittalPreview : dtrPreview).value.setAttribute("src", URL.createObjectURL(response))
        })
        .catch(function () {
            (transmittal ? transmittalPreview : dtrPreview).value.removeAttribute("src")
        })
}

const closePreview = () => {
    dtrPreview.value.removeAttribute('src')

    transmittalPreview.value.removeAttribute('src')
}

const print = async (transmittal = false) => {
    if (dtrPreview.value.hasAttribute('src')) {
        (transmittal ? transmittalPreview : dtrPreview).value.contentWindow.print()

        return
    }

    loading.value = true

    await loadPreview()

    await loadPreview(true)

    setTimeout(() => print(transmittal), 750)

    loading.value = false
}

const showEmployeeModal = (e) => {
    employee.value = e
    modal.value.employee = true
}

</script>

<template>
    <AppLayout title="Home">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Home
            </h2>
        </template>

        <div class="px-4 py-5 sm:px-0">
            <div class="mx-auto space-y-3 max-w-7xl sm:px-6 lg:px-8">
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

                            <div class="tooltip" data-tip="Register">
                                <button @click.exact="showEmployeeModal(null)" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Search">
                                <button @click.exact="modal.search = true" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M416 208c0 45.9-14.9 88.3-40 122.7L502.6 457.4c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L330.7 376c-34.4 25.2-76.8 40-122.7 40C93.1 416 0 322.9 0 208S93.1 0 208 0S416 93.1 416 208zM208 352a144 144 0 1 0 0-288 144 144 0 1 0 0 288z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Import">
                                <button @click.exact="modal.import = true" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M288 109.3V352c0 17.7-14.3 32-32 32s-32-14.3-32-32V109.3l-73.4 73.4c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l128-128c12.5-12.5 32.8-12.5 45.3 0l128 128c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0L288 109.3zM64 352H192c0 35.3 28.7 64 64 64s64-28.7 64-64H448c35.3 0 64 28.7 64 64v32c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V416c0-35.3 28.7-64 64-64zM432 456a24 24 0 1 0 0-48 24 24 0 1 0 0 48z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Synchronize">
                                <button @click.exact="modal.sync = true" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Options">
                                <button @click.exact="modal.options = true" @click.alt.ctrl.exact="modal.settings = true" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/>
                                    </svg>
                                </button>
                            </div>

                            <div class="tooltip" data-tip="Print">
                                <button @click.exact="print(false)" @click.alt.exact="print(true)" :disabled="!args.employees.length || !args.month || loading" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                    <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                        <path d="M128 0C92.7 0 64 28.7 64 64v96h64V64H354.7L384 93.3V160h64V93.3c0-17-6.7-33.3-18.7-45.3L400 18.7C388 6.7 371.7 0 354.7 0H128zM384 352v32 64H128V384 368 352H384zm64 32h32c17.7 0 32-14.3 32-32V256c0-35.3-28.7-64-64-64H64c-35.3 0-64 28.7-64 64v96c0 17.7 14.3 32 32 32H64v64c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V384zM432 248a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <DataTable
                    ref="dataTable"
                    :items="employees"
                    :query-strings="queryStrings"
                    @updated="clearSelection"
                    class="table-sm"
                    wrapper-class="h-[34em]"
                >
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
                                    {{ row.name_format.fullStartLastInitialMiddle }}
                                </label>
                            </td>
                            <td class="p-0">
                                <label :for="`employee-selection-${row.id}`" class="block w-full p-2 cursor-pointer select-none">
                                    {{ row.regular ? 'regular' : 'non-regular' }}
                                </label>
                            </td>
                            <td class="p-0">
                                <label :for="`employee-selection-${row.id}`" class="block w-full p-2 cursor-pointer select-none">
                                    {{ row.office?.toLowerCase() }}
                                </label>
                            </td>
                            <td class="overflow-visible text-ellipsis whitespace-nowrap min-w-[fit-content] p-0">
                                <label :for="`employee-selection-${row.id}`" class="block w-full p-2 cursor-pointer select-none">
                                    {{ row.groups?.map(e => e.toLowerCase()).join(', ') }}
                                </label>
                            </td>
                            <th class="p-0 px-2 text-right bg-[transparent!important;]">
                                <button @click="showEmployeeModal(row)" class="px-1 btn btn-xs btn-primary">
                                    <svg class="h-2.5 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 128 512">
                                        <path d="M64 360a56 56 0 1 0 0 112 56 56 0 1 0 0-112zm0-160a56 56 0 1 0 0 112 56 56 0 1 0 0-112zM120 96A56 56 0 1 0 8 96a56 56 0 1 0 112 0z"/>
                                    </svg>
                                </button>
                            </th>
                        </tr>
                    </template>
                </DataTable>
            </div>
        </div>

        <EmployeeModal v-model="modal.employee" v-model:employee="employee" :scanners="scanners" />

        <ImportModal v-model="modal.import" :scanners="scanners" />

        <OptionsModal v-model="modal.options" v-model:data="config" />

        <SearchModal v-model="modal.search" />

        <SettingsModal v-model="modal.settings" v-model:data="settings" />

        <SynchronizeModal v-model="modal.sync" :scanners="scanners" />

        <Teleport to="body">
            <iframe title="DTR" ref="dtrPreview" hidden frameborder="0"></iframe>
            <iframe title="Transmittal" ref="transmittalPreview" hidden frameborder="0"></iframe>
        </Teleport>
    </AppLayout>
</template>
