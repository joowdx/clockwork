<script setup>
import { usePage } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import EmployeeInformationModal from './Partials/EmployeeInformationModal.vue'
import EmployeesTable from './Partials/EmployeesTable.vue'
import ImportModal from './Partials/ImportModal.vue'
import OptionsModal from './Partials/OptionsModal.vue'
import SearchModal from './Partials/SearchModal.vue'
import SettingsModal from './Partials/SettingsModal.vue'
import SynchronizeModal from './Partials/SynchronizeModal.vue'
import TimelogsModal from './Partials/TimelogsModal.vue'

const props = defineProps(['employees', 'offices', 'groups', 'scanners', 'office', 'group', 'active', 'regular', 'all', 'unenrolled'])

const config = ref({})

const args = ref({
    by: 'employee',
    csc_format: true,
    period: usePage().props.period,
    month: usePage().props.month,
    employees: {},
    from: usePage().props.from,
    to: usePage().props.to,
})

const queryStrings = ref({
    active: props.active ?? true,
    regular: props.regular,
    office: props.office,
    group: props.group,
    all: props.all,
    unenrolled: props.unenrolled,
})

const settings = ref({
    all: queryStrings.value.all,
    unenrolled: queryStrings.value.unenrolled,
})

const modal = ref({
    employee: false,
    import: false,
    options: false,
    search: false,
    settings: false,
    sync: false,
    timelogs: false,
})

const employee = ref(null)

const developer = ref(false)

const loading = ref(false)

const printPreview = ref('dtr')

const selected = computed(() => Object.keys(args.value.employees).filter(e => args.value.employees[e]))

onBeforeUnmount(() => {
    dtrPreview.value.onload = null
    transmittalPreview.value.onload = null
})

onMounted(() => {
    dtrPreview.value.onload = () => {
        if (dtrPreview.value.hasAttribute('src') && printPreview.value === 'dtr') {
            loading.value = false

            nextTick(() => setTimeout(() => dtrPreview.value.contentWindow.print(), 250))
        }
    }
    transmittalPreview.value.onload = () => {
        if (transmittalPreview.value.hasAttribute('src') && printPreview.value === 'transmittal') {
            loading.value = false

            nextTick(() => setTimeout(() => transmittalPreview.value.contentWindow.print(), 250))
        }
    }
})

watch([args, config], () => closePreview(), { deep: true, flush: 'sync' })

watch(settings, (settings) => {
    queryStrings.value.all = settings.all ? true : null
    queryStrings.value.unenrolled = settings.unenrolled ? settings.unenrolled : null
}, { deep: true })

watch(() => usePage().props.flash?.employee, (employee) => {
    if (employee === undefined) {
        return
    }

    nextTick(() => showEmployeeModal(employee))
})

const loadPreview = async (transmittal = false) => {
    return await fetch(route('print', { by: 'dtr' }), {
        method: 'POST',
        body: JSON.stringify({
            ...args.value,
            ...config.value,
            ...(transmittal ? { transmittal: true } : {}),
            employees: selected.value
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
    dtrPreview.value?.removeAttribute('src')

    transmittalPreview.value?.removeAttribute('src')
}

const print = async (transmittal = false) => {
    printPreview.value = transmittal ? 'transmittal' : 'dtr'

    if (transmittalPreview.value.hasAttribute('src') || dtrPreview.value.hasAttribute('src')) {
        (transmittal ? transmittalPreview : dtrPreview).value.contentWindow.print()

        return
    }

    loading.value = true

    await loadPreview()

    await loadPreview(true)
}

const showEmployeeModal = (e) => {
    employee.value = e
    modal.value.employee = true
}

const showTimelogsModal = (e, dev) => {
    employee.value = e
    developer.value = dev
    modal.value.timelogs = true
}

const dtrPreview = ref(null)
const transmittalPreview = ref(null)

const formOptions = {
    only: ['errors', 'employees', 'groups', 'offices']
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
                    <div class="col-span-6 form-control col sm:col-span-3 lg:col-span-2">
                        <label for="print-format" class="p-0 text-xs label">
                            <span class="label-text">Format</span>
                        </label>
                        <select v-model="args.csc_format" id="print-format" class="col-span-6 select-sm sm:col-span-3 select select-bordered">
                            <option value="null">Preferred</option>
                            <option :value="false">Default</option>
                            <option :value="true">CSC Form No. 48</option>
                        </select>
                    </div>

                    <div class="col-span-6 form-control col sm:col-span-3 lg:col-span-2">
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
                        <div class="col-span-12 form-control col sm:col-span-3 lg:col-span-2">
                            <label for="month" class="p-0 label">
                                <span class="label-text">Month</span>
                            </label>
                            <input v-model="args.month" id="month" class="input input-bordered input-sm" type="month">
                        </div>

                        <div class="col-span-6 sm:col-span-3 lg:col-span-2">

                        </div>
                    </template>

                    <template v-else>
                        <div class="col-span-6 form-control col sm:col-span-3 lg:col-span-2">
                            <label for="from" class="p-0 label">
                                <span class="label-text">From</span>
                            </label>
                            <input v-model="args.from" id="from" class="input input-bordered input-sm" type="date">
                        </div>

                        <div class="col-span-6 form-control col sm:col-span-3 lg:col-span-2">
                            <label for="to" class="p-0 label">
                                <span class="label-text">To</span>
                            </label>
                            <input v-model="args.to" id="to" class="input input-bordered input-sm" type="date">
                        </div>
                    </template>

                    <div class="flex flex-wrap self-end justify-end order-first col-span-12 gap-2 mt-3 lg:col-span-4 lg:order-none justify-self-end">
                        <div v-if="loading" class="flex items-center">
                            <svg class="w-4 h-4 fill-current" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <rect class="spinner_9y7u" x="1" y="1" rx="1" width="10" height="10"/>
                                <rect class="spinner_9y7u spinner_DF2s" x="1" y="1" rx="1" width="10" height="10"/>
                                <rect class="spinner_9y7u spinner_q27e" x="1" y="1" rx="1" width="10" height="10"/>
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
                            <button @click.exact="print(false)" @click.alt.exact="print(true)" :disabled="!selected.length || !args.month || loading" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                    <path d="M128 0C92.7 0 64 28.7 64 64v96h64V64H354.7L384 93.3V160h64V93.3c0-17-6.7-33.3-18.7-45.3L400 18.7C388 6.7 371.7 0 354.7 0H128zM384 352v32 64H128V384 368 352H384zm64 32h32c17.7 0 32-14.3 32-32V256c0-35.3-28.7-64-64-64H64c-35.3 0-64 28.7-64 64v96c0 17.7 14.3 32 32 32H64v64c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V384zM432 248a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <EmployeesTable
                    v-model="args.employees"
                    v-model:queryStrings="queryStrings"
                    :employees="employees"
                    :offices="offices"
                    :groups="groups"
                    :options="formOptions"
                    @edit="showEmployeeModal"
                    @timelogs="showTimelogsModal"
                />
            </div>
        </div>

        <EmployeeInformationModal
            v-model="modal.employee"
            v-model:employee="employee"
            :scanners="scanners"
            :options="formOptions"
            @saved="closePreview"
        />

        <ImportModal
            v-model="modal.import"
            :scanners="scanners"
            :options="formOptions"
        />

        <OptionsModal
            v-model="modal.options"
            v-model:data="config"
        />

        <SearchModal
            v-model="modal.search"
        />

        <SettingsModal
            v-model="modal.settings"
            v-model:data="settings"
        />

        <SynchronizeModal
            v-model="modal.sync"
            :scanners="scanners"
            :options="formOptions"
        />

        <TimelogsModal
            v-model="modal.timelogs"
            v-model:employee="employee"
            :options="formOptions"
            :developer="developer"
        />

        <Teleport to="body">
            <iframe title="DTR" ref="dtrPreview" hidden frameborder="0"></iframe>
            <iframe title="Transmittal" ref="transmittalPreview" hidden frameborder="0"></iframe>
        </Teleport>
    </AppLayout>
</template>


<style scoped>
.spinner_9y7u{
    animation: spinner_fUkk 2.4s linear infinite;
    animation-delay:-2.4s
}
 .spinner_DF2s{
    animation-delay:-1.6s
}
 .spinner_q27e{
    animation-delay:-.8s
}
 @keyframes spinner_fUkk{
    8.33%{
        x:13px;
        y:1px
    }
    25%{
        x:13px;
        y:1px
    }
    33.3%{
        x:13px;
        y:13px
    }
    50%{
        x:13px;
        y:13px
    }
    58.33%{
        x:1px;
        y:13px
    }
    75%{
        x:1px;
        y:13px
    }
    83.33%{
        x:1px;
        y:1px
    }
}
</style>
