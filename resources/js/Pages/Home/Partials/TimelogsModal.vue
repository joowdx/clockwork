<script setup>
import Modal from '@/Components/Modal.vue'
import { usePage } from '@inertiajs/vue3'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import dayjs from 'dayjs'
import axios from 'axios'

const modelValue = defineModel()

const employee = defineModel('employee')

const props = defineProps(['options'])

const allowed = computed(() => usePage().props.user.type === -1)

const tab = ref('query')

const timelogs = ref([])

const loading = ref(false)

const date = ref(dayjs().format('YYYY-MM-DD'))

const inserted = ref(false)

const insertData = ref({
    time: null,
    scanner_id: null,
    state: null,
})

const switchTab = (to) => {
    tab.value = to

    if (tab.value == 'query') {
        nextTick(() => document.getElementById('timelogs_date_query').focus())
    }
}

const toggleHidden = async (timelog) => {
    loading.value = true

    await axios.put(route('timelogs.update', { timelog: timelog.id }), {
        hidden: ! Boolean(timelog.hidden),
    }).finally(() => {
        loading.value = false
        loadTimelogs(date.value)
    })
}

const insertTimelog = async () => {
    await axios.post(route('timelogs.store'), {
        ...insertData.value,
        uid: employee.value.scanners.find(e => e.id === insertData.value.scanner_id).pivot.uid
    }).finally(() => {
        inserted.value = true

        insertData.value.time = null
        insertData.value.scanner_id = null
        insertData.value.state = null

        loadTimelogs(date.value)

        setTimeout(() => inserted.value = false, 750)
    })
}

const deleteTimelog = async (timelog) => {
    await axios.delete(route('timelogs.destroy', { timelog: timelog.id })).finally(() => {
        loadTimelogs(date.value)
    })
}

const loadTimelogs = async (date) => {
    if (employee.value == null) {
        return
    }

    loading.value = true

    timelogs.value = await fetch(route('employees.timelogs.index', { employee: employee.value.id, date }), {
        headers: { 'Accept': 'application/json' }
    })
        .then(data => data.json())
        .finally(() => loading.value = false)
}

watch(date, loadTimelogs)

watch(modelValue, (show) => {
    if (show) {
        if (!allowed.value) {
            modelValue.value = false
        }

        nextTick(() => document.getElementById('timelogs_date_query').focus())

        return
    }

    setTimeout(() => {
        tab.value = 'query'
        date.value = dayjs().format('YYYY-MM-DD')
        timelogs.value = []
    }, 250)
})

onMounted(() => {
    if (allowed.value) {
        loadTimelogs(date.value)
    }
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            {{ employee?.name_format.shortStartLastInitialFirst }}
        </template>

        <div class="grid gap-3 mb-5">
            <div class="w-full tabs">
                <button @click="switchTab('query')" class="tab tab-bordered" :class="{'tab-active': tab === 'query'}">Query</button>
                <button @click="switchTab('insert')" class="tab tab-bordered" :class="{'tab-active': tab === 'insert'}">Insert</button>
            </div>
        </div>

        <template v-if="tab === 'insert'">
            <div class="form-control">
                <label for="timelogs_scanner_insert" class="px-0 pt-0 label">
                    <span class="label-text">Scanners</span>
                </label>
                <div class="w-full mb-3">
                    <select id="timelogs_scanner_insert" v-model="insertData.scanner_id" class="w-full select select-sm select-bordered">
                        <option :value="null"></option>
                        <option v-for="scanner in employee.scanners" :value="scanner.id">{{ scanner.name }}</option>
                    </select>
                </div>
            </div>

            <div class="form-control">
                <label for="timelogs_state_query" class="px-0 pt-0 label">
                    <span class="label-text">State</span>
                </label>
                <div class="w-full mb-3">
                    <select id="timelogs_state_query" v-model="insertData.state" class="w-full select select-sm select-bordered">
                        <option :value="null"></option>
                        <option :value="0">In</option>
                        <option :value="1">Out</option>
                    </select>
                </div>
            </div>

            <div class="form-control">
                <label for="timelogs_date_insert" class="px-0 pt-0 label">
                    <span class="label-text">Date</span>
                </label>
                <div class="w-full mb-3">
                    <input
                        v-model="insertData.time"
                        id="timelogs_date_insert"
                        type="datetime-local"
                        class="w-full input input-bordered input-sm"
                        step="1"
                    />
                </div>
            </div>
        </template>

        <template v-if="tab === 'query'">
            <div class="form-control">
                <label for="timelogs_date_query" class="px-0 pt-0 label">
                    <span class="label-text">Select date…</span>

                    <div v-if="loading" class="flex items-center mr-3 align-middle">
                        <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                        </svg>
                    </div>

                </label>
                <div class="w-full mb-3">
                    <input
                        v-model="date"
                        id="timelogs_date_query"
                        type="date"
                        class="w-full input input-bordered input-sm"
                    />
                </div>
            </div>

            <div class="pb-3 bg-base-200 rounded-[--rounded-box]">
                <div class="p-2 font-mono">
                    {{ dayjs(date).format('dddd YYYY-MMMM-DD') }}
                </div>
                <div class="overflow-y-auto min-h-[6em] max-h-[10em]">
                    <table class="table table-xs table-zebra table-pin-rows">
                        <thead>
                            <tr v-if="timelogs.length">
                                <th class="w-4/12"> Scanner </th>
                                <th class="w-4/12"> Time </th>
                                <th class="w-2/12"> Uid </th>
                                <th class="w-2/12"> State </th>
                            </tr>

                            <tr v-else>
                                <th>Results</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="timelogs.length">
                                <tr
                                    v-for="timelog in timelogs"
                                    @click.alt.exact="toggleHidden(timelog)"
                                    @click.alt.ctrl.exact="deleteTimelog(timelog)"
                                    class="select-none hover"
                                    :class="{'opacity-50': timelog.hidden}"
                                >
                                    <td>{{ timelog.scanner.name }}</td>
                                    <td class="font-mono tabular-nums">
                                        {{ dayjs(timelog.time).format('HH:mm:ss') }}
                                    </td>
                                    <td>{{ timelog.uid }}</td>
                                    <td>{{ timelog.type }}</td>
                                </tr>
                            </template>

                            <template v-else>
                                <tr>
                                    <td class="italic tracking-wide text-base-content/50">
                                        EMPTY
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template #action>
            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                <p v-if="inserted" class="flex items-center justify-end flex-1 mr-3 text-sm opacity-50 text-base-content">
                    Success.
                </p>
            </Transition>

            <button
                v-if="tab === 'insert'"
                class="btn btn-sm btn-primary"
                type="button"
                @click="insertTimelog"
                :disabled="! insertData.time || insertData.scanner_id === null || insertData.state === null"
            >
                Save
            </button>
        </template>
    </Modal>
</template>

<style scoped>
div:has(table)::-webkit-scrollbar {
    display: none;
}
</style>
