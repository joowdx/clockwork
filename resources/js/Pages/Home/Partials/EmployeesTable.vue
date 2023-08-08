<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import DataTable from '@/Components/DataTable.vue'

const emits = defineEmits(['edit', 'timelogs'])

const props = defineProps(['employees', 'groups', 'offices', 'options'])

const data = defineModel()

const queryStrings = defineModel('queryStrings')

const datatable = ref(null)

const checkbox = ref(null)

const results = computed(() => props.employees.data.map(e => e.id))

const selected = computed(() => Object.keys(data.value).filter(e => data.value[e]))

let skipCheck = false

const clearSelection = () => {
    data.value = {}
    checkbox.value.checked = false
    checkbox.value.indeterminate = false
}

const resetFilter = () => {
    clearSelection()
    queryStrings.value.active = true
    queryStrings.value.regular = undefined
    queryStrings.value.office = undefined
    queryStrings.value.group = undefined
    queryStrings.value.search = ''
}

const toggleSelection = () => {
    skipCheck = true

    if (results.value.every(e => data.value[e])) {
        selected.value.forEach(e => {
            if (results.value.includes(e)) {
                delete data.value[e]
            }
        })
    } else {
        results.value.forEach(e => data.value[e] = true)
    }

    skipCheck = false
}

const checkSelection = () => {
    if (skipCheck) {
        return
    }

    if (results.value.every(e => selected.value.includes(e)) && selected.value.length) {
        checkbox.value.indeterminate = false
        checkbox.value.checked = true
    } else if (results.value.some(e => selected.value.includes(e)) && selected.value.length) {
        checkbox.value.indeterminate = true
        checkbox.value.checked = false
    } else {
        checkbox.value.checked = false
        checkbox.value.indeterminate = false
    }
}

watch(data, checkSelection, { deep: true })

onMounted(() => router.reload({ only: ['employees', 'groups', 'offices'] }))
</script>

<template>
    <DataTable
        ref="datatable"
        class="table-sm"
        :class="{'opacity-50 pointer-events-none': datatable?.processing}"
        :items="employees"
        :queryStrings="queryStrings"
        :wrapperClass="`h-[calc(100vh-425px)] min-h-[29em]`"
        :options="options"
        @updated="checkSelection"
    >
        <template #pre>
            <div class="flex gap-3 px-4 mb-2 select-none group">
                {{ selected.length === 0 ? 'No' : selected.length }} {{ selected.length === 1 ? 'employee' : 'employees' }} selected

                <button @click="clearSelection" class="items-center hidden place-content-center btn btn-primary btn-xs group-hover:flex">
                    Clear
                </button>

                <button @click="resetFilter" class="items-center hidden place-content-center btn btn-primary btn-xs group-hover:flex">
                    Reset
                </button>
            </div>
        </template>

        <template #actions>
            <div>
                <div class="grid grid-cols-12 col-span-12 gap-3">
                    <div class="col-span-6 form-control sm:col-span-3">
                        <label for="period" class="p-0 label">
                            <span class="label-text">Status</span>
                        </label>
                        <select aria-label="Status" class="select select-bordered select-sm" v-model="queryStrings.regular" :disabled="datatable?.processing">
                            <option :value="undefined">status</option>
                            <option :value="true">regular</option>
                            <option :value="false">jo, cos, etc.</option>
                        </select>
                    </div>

                    <div class="col-span-6 form-control sm:col-span-3">
                        <label for="period" class="p-0 label">
                            <span class="label-text">Office</span>
                        </label>
                        <select aria-label="Office" class="select-sm select select-bordered" v-model="queryStrings.office" :disabled="datatable?.processing">
                            <option :value="undefined">office</option>
                            <option v-for="office in offices"> {{ office }} </option>
                        </select>
                    </div>

                    <div class="col-span-6 form-control sm:col-span-3">
                        <label for="period" class="p-0 label">
                            <span class="label-text">Group</span>
                        </label>
                        <select aria-label="Group" v-model="queryStrings.group" class="select-sm select select-bordered" :disabled="datatable?.processing">
                            <option :value="undefined">group</option>
                            <option v-for="group in groups"> {{ group }} </option>
                        </select>
                    </div>


                    <div class="col-span-6 form-control sm:col-span-3">
                        <label for="period" class="p-0 label">
                            <span class="label-text">Active</span>
                        </label>

                        <select aria-label="Active" class="select-sm select select-bordered" v-model="queryStrings.active" :disabled="datatable?.processing">
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
                        <input @change="toggleSelection" ref="checkbox" class="mx-2 checkbox checkbox-xs" type="checkbox">
                    </label>
                </th>
                <th class="px-2 py-3 w-96 min-w-96">Name</th>
                <th class="px-2 py-3 w-36">Status</th>
                <th class="w-48 px-2 py-3">Office</th>
                <th class="px-2 py-3 w-36">Groups</th>
                <th class="w-16 px-2">
                    <button type="button" class="py-0 opacity-0 cursor-default btn btn-xs btn-primary">
                        Edit
                    </button>
                </th>
            </tr>
        </template>

        <template #default="{row}">
            <tr class="group bg-opacity-40 hover">
                <th class="p-0 bg-[transparent!important;]">
                    <label class="flex justify-center px-2 py-1.5 cursor-pointer">
                        <input :id="`employee-selection-${row.id}`" v-model="data[row.id]" :value="row.id" class="checkbox checkbox-xs" type="checkbox">
                    </label>
                </th>
                <td class="p-0">
                    <label :for="`employee-selection-${row.id}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                        {{ row.name_format.fullStartLastInitialMiddle }}
                    </label>
                </td>
                <td class="p-0">
                    <label :for="`employee-selection-${row.id}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                        {{ row.regular ? 'regular' : 'non-regular' }}
                    </label>
                </td>
                <td class="p-0">
                    <label :for="`employee-selection-${row.id}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                        <template v-if="row.office">
                            {{ row.office?.toLowerCase() }}
                        </template>

                        <template v-else>
                            &nbsp;
                        </template>
                    </label>
                </td>
                <td class="overflow-visible text-ellipsis whitespace-nowrap min-w-[fit-content] p-0">
                    <label :for="`employee-selection-${row.id}`" class="block w-full px-2 py-1.5 cursor-pointer select-none font-mono">
                        <template v-if="row.groups?.length">
                            {{ row.groups?.map(e => e.toLowerCase()).join(', ') }}
                        </template>

                        <template v-else>
                            &nbsp;
                        </template>
                    </label>
                </td>
                <th class="p-0 px-2 text-right bg-[transparent!important;]">
                    <button @click.exact="emits('edit', row)" @click.alt.ctrl.shift.exact="emits('timelogs', row)" class="justify-center hidden group-hover:flex btn btn-xs btn-primary">
                        Edit
                    </button>
                </th>
            </tr>
        </template>
    </DataTable>
</template>
