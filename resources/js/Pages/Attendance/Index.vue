<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import SearchModal from '../Home/Partials/SearchModal.vue'
import SynchronizeModal from '../Home/Partials/SynchronizeModal.vue'
import ImportModal from '../Home/Partials/ImportModal.vue'
import OptionsModal from './Partials/OptionsModal.vue'
import DatesModal from './Partials/DatesModal.vue'
import ScannersModal from './Partials/ScannersModal.vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from '@/Components/DataTable.vue'

const props = defineProps([
    'category',
    'scanners',
    'groups',
    'offices',
])

const queryStrings = ref({
    category: props.category,
})

const args = ref({
    dates: [],
    scanners: [],
    transmittal: true,
})

const list = computed(() => queryStrings.value.category === 'group' ? props.groups : props.offices)

const officeTable = ref(null), groupTable = ref(null)

const selection = ref([])

const loading = ref(false)

const attendancePreview = ref(null)

const modal = ref({
    dates: false,
    import: false,
    search: false,
    options: false,
    scanners: false,
    sync: false,
})

const load = (query) => router.reload({ data: query, only: [`${query.category}s`] })

const clear = () => selection.value = []

const loadPreview = async () => {
    return await fetch(route('print', { by: 'office' }), {
        method: 'POST',
        body: JSON.stringify({
            ...args.value,
            [`${queryStrings.value.category}s`]: selection.value
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
            attendancePreview.value.setAttribute("src", URL.createObjectURL(response))
        })
        .catch(function () {
            attendancePreview.value.removeAttribute("src")

            loading.value = false
        })
}

const closePreview = () => {
    attendancePreview.value?.removeAttribute('src')
}

const print = async () => {
    if (attendancePreview.value.hasAttribute('src')) {
        attendancePreview.value.contentWindow.print()

        return
    }

    loading.value = true

    await loadPreview()
}

watch(queryStrings, load, { deep: true })

watch(() => queryStrings.value.category, clear)

watch([selection, args], closePreview, { deep: true })

onBeforeUnmount(() => {
    attendancePreview.value.onload = null
})

onMounted(() => {
    attendancePreview.value.onload = () => {
        if (attendancePreview.value.hasAttribute('src')) {
            loading.value = false

            nextTick(() => setTimeout(() => attendancePreview.value.contentWindow.print(), 250))
        }
    }

    nextTick(() => {
        router.reload({
            data: queryStrings.value,
            only: [`${props.category}s`],
        })
    })
})

const formOptions = {
    only: ['errors', 'groups', 'offices', 'scanners']
}
</script>

<template>
    <AppLayout title="Attendance">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Attendance
            </h2>
        </template>

        <div class="px-4 py-5 sm:px-0">
            <div class="mx-auto space-y-3 max-w-7xl sm:px-6 lg:px-8">
                <div class="flex flex-wrap self-end justify-end order-first col-span-12 gap-2 pt-2 mt-3 lg:col-span-4 lg:order-none justify-self-end">
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

                    <div class="tooltip" data-tip="Scanners">
                        <button @click.exact="modal.scanners = true" class="tracking-tighter btn btn-sm btn-square btn-primary">
                            <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                <path d="M128 64v96h64V64H386.7L416 93.3V160h64V93.3c0-17-6.7-33.3-18.7-45.3L432 18.7C420 6.7 403.7 0 386.7 0H192c-35.3 0-64 28.7-64 64zM0 160V480c0 17.7 14.3 32 32 32H64c17.7 0 32-14.3 32-32V160c0-17.7-14.3-32-32-32H32c-17.7 0-32 14.3-32 32zm480 32H128V480c0 17.7 14.3 32 32 32H480c17.7 0 32-14.3 32-32V224c0-17.7-14.3-32-32-32zM256 256a32 32 0 1 1 0 64 32 32 0 1 1 0-64zm96 32a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32 96a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM224 416a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="tooltip" data-tip="Dates">
                        <button @click.exact="modal.dates = true" class="tracking-tighter btn btn-sm btn-square btn-primary">
                            <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512">
                                <path d="M152 24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H64C28.7 64 0 92.7 0 128v16 48V448c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V192 144 128c0-35.3-28.7-64-64-64H344V24c0-13.3-10.7-24-24-24s-24 10.7-24 24V64H152V24zM48 192h80v56H48V192zm0 104h80v64H48V296zm128 0h96v64H176V296zm144 0h80v64H320V296zm80-48H320V192h80v56zm0 160v40c0 8.8-7.2 16-16 16H320V408h80zm-128 0v56H176V408h96zm-144 0v56H64c-8.8 0-16-7.2-16-16V408h80zM272 248H176V192h96v56z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="tooltip" data-tip="Options">
                        <button @click.exact="modal.options = true" class="tracking-tighter btn btn-sm btn-square btn-primary">
                            <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                <path d="M0 416c0 17.7 14.3 32 32 32l54.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 448c17.7 0 32-14.3 32-32s-14.3-32-32-32l-246.7 0c-12.3-28.3-40.5-48-73.3-48s-61 19.7-73.3 48L32 384c-17.7 0-32 14.3-32 32zm128 0a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zM320 256a32 32 0 1 1 64 0 32 32 0 1 1 -64 0zm32-80c-32.8 0-61 19.7-73.3 48L32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l246.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48l54.7 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-54.7 0c-12.3-28.3-40.5-48-73.3-48zM192 128a32 32 0 1 1 0-64 32 32 0 1 1 0 64zm73.3-64C253 35.7 224.8 16 192 16s-61 19.7-73.3 48L32 64C14.3 64 0 78.3 0 96s14.3 32 32 32l86.7 0c12.3 28.3 40.5 48 73.3 48s61-19.7 73.3-48L480 128c17.7 0 32-14.3 32-32s-14.3-32-32-32L265.3 64z"/>
                            </svg>
                        </button>
                    </div>

                    <div class="tooltip" data-tip="Print">
                        <button
                            @click.exact="print"
                            class="tracking-tighter btn btn-sm btn-square btn-primary"
                            :disabled="loading || selection.length === 0 || args.dates.length === 0 || args.scanners.length === 0"
                        >
                            <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                <path d="M128 0C92.7 0 64 28.7 64 64v96h64V64H354.7L384 93.3V160h64V93.3c0-17-6.7-33.3-18.7-45.3L400 18.7C388 6.7 371.7 0 354.7 0H128zM384 352v32 64H128V384 368 352H384zm64 32h32c17.7 0 32-14.3 32-32V256c0-35.3-28.7-64-64-64H64c-35.3 0-64 28.7-64 64v96c0 17.7 14.3 32 32 32H64v64c0 35.3 28.7 64 64 64H384c35.3 0 64-28.7 64-64V384zM432 248a24 24 0 1 1 0 48 24 24 0 1 1 0-48z"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <DataTable
                    ref="officeTable"
                    class="table-sm"
                    :class="{'opacity-50 pointer-events-none': officeTable?.processing}"
                    :items="list"
                    :queryStrings="queryStrings"
                    :wrapperClass="`h-[calc(100vh-425px)] min-h-[29em]`"
                    :options="formOptions"
                >
                    <template #pre>
                        <div class="flex justify-between px-4 mb-2 select-none group">
                            <div class="flex gap-3">
                                {{ selection.length === 0 ? 'No' : selection.length }} {{ `${queryStrings.category}${selection.length === 1 ? '' : 's'}` }} selected

                                <button @click="clear" class="items-center hidden place-content-center btn btn-primary btn-xs group-hover:flex">
                                    Clear
                                </button>
                            </div>

                            <div class="font-mono tracking-tighter lowercase">
                                Scanners: {{ args.scanners.length }} / Dates: {{ args.dates.length }}
                            </div>
                        </div>
                    </template>

                    <template #actions>
                        <div class="flex justify-end">
                            <div class="w-full gap-3 md:max-w-xs">
                                <div class="form-control">
                                    <label for="period" class="p-0 label">
                                        <span class="label-text">Categorize By</span>
                                    </label>
                                    <select aria-label="Status" class="select select-bordered select-sm" v-model="queryStrings.category">
                                        <option>office</option>
                                        <option>group</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template #head>
                        <tr>
                            <th class="p-0 w-[40px] max-w-[40px] z-10">
                                <label class="flex justify-center">
                                    <input ref="checkbox" class="mx-2 checkbox checkbox-xs" type="checkbox" disabled>
                                </label>
                            </th>
                            <th class="px-2 py-3 w-36">Name</th>
                            <th class="w-12 px-2 py-3">Employees</th>
                            <th class="px-2 py-3 w-96 min-w-96">Scanners</th>
                            <th class="px-2 py-3 w-96 min-w-96">{{ queryStrings.category == 'office' ? 'Groups' : 'Offices' }}</th>
                        </tr>
                    </template>

                    <template #default="{row}">
                        <tr class="group bg-opacity-40 hover">
                            <th class="p-0 bg-[transparent!important;]">
                                <label class="flex justify-center px-2 py-1.5 cursor-pointer">
                                    <input
                                        :id="`category-${row[queryStrings.category]}`"
                                        v-model="selection"
                                        :value="row[queryStrings.category]"
                                        class="checkbox checkbox-xs"
                                        type="checkbox"
                                    >
                                </label>
                            </th>
                            <td class="p-0">
                                <label :for="`category-${row[queryStrings.category]}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                                    {{ row[queryStrings.category] }}
                                </label>
                            </td>
                            <td class="p-0">
                                <label :for="`category-${row[queryStrings.category]}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                                    {{ row.employees }}
                                </label>
                            </td>
                            <td class="p-0">
                                <label :for="`category-${row[queryStrings.category]}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                                    <template v-if="row.scanners">
                                        {{ row.scanners }}
                                    </template>
                                    <template v-else>
                                        &nbsp;
                                    </template>
                                </label>
                            </td>
                            <td class="p-0">
                                <label :for="`category-${row[queryStrings.category]}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                                    <template v-if="row[queryStrings.category == 'office' ? 'groups' : 'offices']">
                                        {{ row[queryStrings.category == 'office' ? 'groups' : 'offices'] }}
                                    </template>
                                    <template v-else>
                                        &nbsp;
                                    </template>
                                </label>
                            </td>
                        </tr>
                    </template>
                </DataTable>
            </div>
        </div>

        <SearchModal
            v-model="modal.search"
        />

        <ScannersModal
            v-model="modal.scanners"
            v-model:data="args.scanners"
            :scanners="scanners.all"
            :options="formOptions"
        />

        <DatesModal
            v-model="modal.dates"
            v-model:data="args.dates"
        />

        <ImportModal
            v-model="modal.import"
            :scanners="scanners.assigned"
            :options="formOptions"
        />

        <SynchronizeModal
            v-model="modal.sync"
            :scanners="scanners.assigned"
            :options="formOptions"
        />

        <OptionsModal
            v-model="modal.options"
            v-model:data="args"
        />

        <Teleport to="body">
            <iframe title="DTR" ref="attendancePreview" hidden frameborder="0"></iframe>
        </Teleport>
    </AppLayout>
</template>


<style scoped>
.table :where(thead, tbody) :where(tr:not(:last-child)), .table :where(thead, tbody) :where(tr:first-child:last-child) {
    border-bottom: none;
}
.table tr.active, .table tr.active:nth-child(even), .table-zebra tbody tr:nth-child(even) {
    --tw-bg-opacity: 0.5;
    background-color: hsl(var(--b2) / var(--tw-bg-opacity));
}
.table tr.hover {
    --tw-bg-opacity: 0.5 !important;
}
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
