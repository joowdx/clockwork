<script setup>
import Modal from '@/Components/Modal.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import advancedFormat from 'dayjs/plugin/advancedFormat'
import InputError from '@/Components/InputError.vue'

dayjs.extend(advancedFormat)

const modelValue = defineModel()

const employee = defineModel('employee')

const props = defineProps(['options', 'developer'])

const tab = ref('query')

const timelogs = ref([])

const loading = ref(false)

const date = ref(dayjs().format('YYYY-MM-DD'))

const insert = ref({
    time: null,
    scanner_id: null,
    state: null,
})

const insertForm = useForm({
    time: null,
    scanner_id: null,
    state: null,
})

const allowed = computed(() => usePage().props.user.role === -1)

const invalid = computed(() => ! insertForm.time || ! date.value || insertForm.scanner_id === null || insertForm.state === null)

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
    insertForm
        .transform(data => ({
            ...data, time: `${date.value} ${data.time}`, uid: employee.value.scanners.find(e => e.id === data.scanner_id).pivot.uid,
        }))
        .post(route('timelogs.store'), {
            only: ['errors'],
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                insertForm.reset()
                insertForm.clearErrors()
                loadTimelogs(date.value)
            }
        })
}

const deleteTimelog = async (timelog) => {
    if (!timelog.hidden) {
        return
    }

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

    if (! props.developer) {
        timelogs.value = timelogs.value.filter(e => !e.hidden)
    }
}

watch(date, loadTimelogs)

watch(modelValue, (show) => {
    if (show) {
        if (props.developer && !allowed.value) {
            modelValue.value = false
        }

        loadTimelogs(date.value)

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
            Timelogs
        </template>

        <div class="grid gap-3">
            <div :class="{'grid grid-cols-3 gap-3.5': developer}">
                <div class="form-control">
                    <label for="timelogs_date_query" class="px-0 pt-0 pb-0.5 label">
                        <span class="label-text">Date</span>
                    </label>
                    <div class="w-full">
                        <input
                            v-model="date"
                            id="timelogs_date_query"
                            type="date"
                            class="w-full input input-bordered input-sm"
                        />
                    </div>
                </div>

                <template v-if="developer">
                    <div class="form-control">
                        <label for="timelogs_scanner_insert" class="px-0 pt-0 pb-0.5 label">
                            <span class="label-text">Time</span>
                        </label>
                        <div class="w-full">
                            <input
                                v-model="insertForm.time"
                                id="timelogs_date_insert"
                                type="time"
                                step="1"
                                class="w-full input input-bordered input-sm"
                            />
                        </div>
                        <InputError class="mt-0.5" :message="insertForm.errors.time" />
                    </div>

                    <div class="form-control">
                        <label for="timelogs_state_query" class="px-0 pt-0 pb-0.5 label">
                            <span class="label-text">State</span>
                        </label>
                        <div class="w-full">
                            <select id="timelogs_state_query" v-model="insertForm.state" class="w-full select select-sm select-bordered">
                                <option :value="null"></option>
                                <option :value="0">In</option>
                                <option :value="1">Out</option>
                            </select>
                        </div>
                        <InputError class="mt-0.5" :message="insertForm.errors.state" />
                    </div>
                </template>
            </div>

            <div v-if="developer" class="grid grid-cols-12 gap-3.5 items-end">
                <div class="col-span-10 form-control">
                    <label for="timelogs_scanner_insert" class="px-0 pt-0 pb-0.5 label">
                        <span class="label-text">Scanner</span>
                    </label>
                    <div class="w-full">
                        <select id="timelogs_scanner_insert" v-model="insertForm.scanner_id" class="w-full select select-sm select-bordered">
                            <option :value="null"></option>
                            <option v-for="scanner in employee?.scanners" :value="scanner.id">{{ scanner.name }}</option>
                        </select>
                    </div>
                    <InputError class="mt-0.5" :message="insertForm.errors.scanner_id" />
                </div>

                <button
                    type="button"
                    class="w-full col-span-2 font-mono btn btn-sm btn-primary"
                    @click="insertTimelog"
                    :disabled="invalid"
                >
                    Insert
                </button>
            </div>

            <hr class="my-2 border-t border-base-content/50">

            <div class="space-y-2">
                <div class="text-sm">
                    {{ dayjs(date).format('dddd, Do of MMMM YYYY') }}
                </div>

                <div class="bg-base-200 pb-1 rounded-[--rounded-box]">
                    <div class="flex justify-between px-2 py-1 font-mono text-sm tracking-tighter">
                        Count: {{ timelogs.length }}
                        <span v-if="loading || insertForm.processing" class="flex items-center align-middle">
                            <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                            </svg>
                        </span>
                    </div>

                    <div class="overflow-y-auto max-h-[10.75em]">
                        <table class="table table-xs table-zebra table-pin-rows">
                            <thead>
                                <tr v-if="timelogs.length">
                                    <th class="w-2/5 max-w-[40%]"> Scanner </th>
                                    <th> Time </th>
                                    <th> Uid </th>
                                    <th> State </th>
                                    <th v-if="developer"></th>
                                </tr>

                                <tr v-else>
                                    <th>Results</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="timelogs.length">
                                    <tr v-for="timelog in timelogs" class="select-none hover">
                                        <td
                                            :class="{
                                                'opacity-40': timelog.hidden
                                            }"
                                        >
                                            {{ timelog.scanner.name }}
                                        </td>
                                        <td class="font-mono tabular-nums" :class="{'opacity-40': timelog.hidden}">
                                            {{ dayjs(timelog.time).format('HH:mm:ss') }}
                                        </td>
                                        <td :class="{'opacity-40': timelog.hidden}">
                                            {{ timelog.uid }}
                                        </td>
                                        <td :class="{'opacity-40': timelog.hidden}">
                                            {{ timelog.type }}
                                        </td>
                                        <td v-if="developer" class="text-right">
                                            <button
                                                @click.exact="toggleHidden(timelog)"
                                                @click.alt.ctrl.exact="deleteTimelog(timelog)"
                                                class="font-mono btn btn-xs btn-primary"
                                            >
                                                <template v-if="timelog.hidden">
                                                    Show
                                                </template>

                                                <template v-else>
                                                    Hide
                                                </template>
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <template v-else>
                                    <tr>
                                        <td class="font-mono italic tracking-wide text-base-content/50 h-[33px]">
                                            ----------------------------------------------------------------
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
div:has(table)::-webkit-scrollbar {
    display: none;
}
</style>
