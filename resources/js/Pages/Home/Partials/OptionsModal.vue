<script setup>
import Modal from '@/Components/Modal.vue'
import { ref, watch } from 'vue'

const modelValue = defineModel()

const data = defineModel('data')

const tab = ref('schedule')

const defaultValue = {
    weekdays: {
        am: {
            in: '08:00',
            out: null,
        },
        pm: {
            in: null,
            out: '16:00',
        },
        excluded: false,
    },
    weekends: {
        am: {
            in: '08:00',
            out: '12:00',
        },
        pm: {
            in: '13:00',
            out: '17:00',
        },
        excluded: false,
        regular: false,
    },
    calculate: false,
    days: [],
}

const include = ref({
    weekdays: true,
    weekends: true,
})

const value = ref(JSON.parse(JSON.stringify(defaultValue)))

const presetTime = ref({
    weekdays: null,
    weekends: null,
})

const clear = () => {
    include.value.weekdays = true
    include.value.weekends = true

    presetTime.value.weekdays = null
    presetTime.value.weekends = null

    value.value.weekdays.am.in = null
    value.value.weekdays.am.out = null
    value.value.weekdays.pm.in = null
    value.value.weekdays.pm.out = null
    value.value.weekends.am.in = null
    value.value.weekends.am.out = null
    value.value.weekends.pm.in = null
    value.value.weekends.pm.out = null
    value.value.weekends.regular = false
    value.value.calculate = false
    value.value.days = []
}

const reset = () => value.value = JSON.parse(JSON.stringify(defaultValue))

const unselectAllDays = () => value.value.days = []

const selectAllDays = () => value.value.days = Array.from({length: 31}, (_, i) => i + 1)

const switchTab = (to) => tab.value = to

watch(presetTime, (time) => {
    if (time.weekdays === null && time.weekends === null) return

    function switchTime(week) {
        switch (time[week]) {
            case '0816': {
                value.value[week].am.in = '08:00'
                value.value[week].am.out = null
                value.value[week].pm.in = null
                value.value[week].pm.out = '16:00'
                break
            }
            case '08121317': {
                value.value[week].am.in = '08:00'
                value.value[week].am.out = '12:00'
                value.value[week].pm.in = '13:00'
                value.value[week].pm.out = '17:00'
                break
            }
            case '06121315': {
                value.value[week].am.in = '06:00'
                value.value[week].am.out = '12:00'
                value.value[week].pm.in = '13:00'
                value.value[week].pm.out = '15:00'
                break
            }
        }
    }

    switchTime('weekdays')
    switchTime('weekends')

    time.weekdays = null
    time.weekends = null
}, { deep: true })

watch(() => include.value.weekdays, (include) => data.value.weekdays.excluded = !include, { flush: 'sync' })

watch(() => include.value.weekends, (include) => data.value.weekends.excluded = !include, { flush: 'sync' })

watch(value, (value) => data.value = value, { deep: true })

data.value = JSON.parse(JSON.stringify(defaultValue))
</script>

<template>

    <Modal v-model="modelValue">
        <template #header>
            Options
        </template>

        <div class="grid gap-3">
            <div class="w-full tabs">
                <button @click="switchTab('schedule')" class="tab tab-bordered" :class="{'tab-active': tab === 'schedule'}">Schedule</button>
                <button @click="switchTab('exclusion')" class="tab tab-bordered" :class="{'tab-active': tab === 'exclusion'}">Exclusion</button>
                <button @click="switchTab('calculation')" class="tab tab-bordered" :class="{'tab-active': tab === 'calculation'}">Calculation</button>
            </div>

            <div class="flex flex-col gap-4 mt-2 min-h-16">
                <template v-if="tab == 'schedule'">
                    <div>
                        <div class="grid grid-cols-2 gap-6">
                            <label class="flex items-center w-full text-sm cursor-pointer select-none">
                                <input v-model="include.weekdays" type="checkbox" class="checkbox checkbox-xs" />
                                <span class="flex-grow ml-2">Weekdays</span>
                            </label>
                            <select v-model="presetTime.weekdays" class="font-mono tracking-tighter select select-xs select-bordered">
                                <option selected hidden disabled :value="null">Select Preset Time</option>
                                <option value="0816">08:00-16:00</option>
                                <option value="08121317">08:00-12:00 | 13:00-17:00</option>
                                <option value="06121315">06:00-12:00 | 13:00-15:00</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-12 col-span-12 mt-2 rounded-md sm:col-span-6 gap-y-2 gap-x-3">
                            <fieldset class="grid grid-cols-12 col-span-12 px-3 pb-3 text-sm border rounded-md border-base-content/40 sm:col-span-6 gap-y-2 gap-x-3">
                                <legend class="ml-3 text-xs">AM</legend>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            In
                                        </sub>
                                        <button @click="value.weekdays.am.in = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekdays.am.in" type="time" class="input input-sm input-bordered" />
                                </div>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            Out
                                        </sub>
                                        <button @click="value.weekdays.am.out = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekdays.am.out" type="time" class="input input-sm input-bordered" />
                                </div>
                            </fieldset>

                            <fieldset class="grid grid-cols-12 col-span-12 px-3 pb-3 text-sm border rounded-md border-base-content/40 sm:col-span-6 gap-y-2 gap-x-3">
                                <legend class="ml-3 text-xs">PM</legend>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            In
                                        </sub>
                                        <button @click="value.weekdays.pm.in = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekdays.pm.in" type="time" class="input input-sm input-bordered" />
                                </div>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            Out
                                        </sub>
                                        <button @click="value.weekdays.pm.out = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekdays.pm.out" type="time" class="input input-sm input-bordered" />
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div>
                        <div class="grid grid-cols-2 gap-6">
                            <label class="flex items-center w-full text-sm cursor-pointer select-none">
                                <input v-model="include.weekends" type="checkbox" class="checkbox checkbox-xs" />
                                <span class="flex-grow ml-2">Weekends</span>
                            </label>
                            <select v-model="presetTime.weekends" class="font-mono tracking-tighter select select-xs select-bordered">
                                <option selected hidden disabled :value="null">Select Preset Time</option>
                                <option value="0816">08:00-16:00</option>
                                <option value="08121317">08:00-12:00 | 13:00-17:00</option>
                                <option value="06121315">06:00-12:00 | 13:00-15:00</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-12 col-span-12 mt-2 rounded-md sm:col-span-6 gap-y-2 gap-x-3">
                            <fieldset class="grid grid-cols-12 col-span-12 px-3 pb-3 text-sm border rounded-md border-base-content/40 sm:col-span-6 gap-y-2 gap-x-3">
                                <legend class="ml-3 text-xs">AM</legend>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            In
                                        </sub>
                                        <button @click="value.weekends.am.in = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekends.am.in" type="time" class="input input-sm input-bordered" />
                                </div>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            Out
                                        </sub>
                                        <button @click="value.weekends.am.out = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekends.am.out" type="time" class="input input-sm input-bordered" />
                                </div>
                            </fieldset>

                            <fieldset class="grid grid-cols-12 col-span-12 px-3 pb-3 text-sm border rounded-md border-base-content/40 sm:col-span-6 gap-y-2 gap-x-3">
                                <legend class="ml-3 text-xs">PM</legend>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            In
                                        </sub>
                                        <button @click="value.weekends.pm.in = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekends.pm.in" type="time" class="input input-sm input-bordered" />
                                </div>
                                <div class="col-span-6 space-y-2">
                                    <div class="flex justify-between place-items-end">
                                        <sub class="text-base">
                                            Out
                                        </sub>
                                        <button @click="value.weekends.pm.out = null" class="p-0 border-none btn-xs btn btn-square btn-primary">
                                            ×
                                        </button>
                                    </div>
                                    <input v-model="value.weekends.pm.out" type="time" class="input input-sm input-bordered" />
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <span class="text-xs tracking-tight text-warning">
                        Uncheck to exclude from printouts.
                    </span>
                </template>

                <template v-if="tab == 'exclusion'">
                    <div>
                        <label class="flex items-center text-sm">
                            <input :checked="value.days.length" onclick="return false;" type="checkbox" class="checkbox checkbox-xs" />
                            <span class="ml-2">Days</span>
                        </label>
                        <fieldset class="grid grid-cols-7 gap-1 p-3 mt-2 border rounded-md border-base-content/40">
                            <legend class="mb-0 ml-3 text-xs">Days of the month</legend>
                            <div v-for="day in 31" class="col-span-2 rounded-md sm:col-span-1 border-base-content/40">
                                <label class="flex items-center justify-center py-1 font-mono cursor-pointer">
                                    <input v-model="value.days" :value="day" class="checkbox checkbox-xs" type="checkbox" >
                                    <span class="ml-2 text-sm">{{ String(day).padStart(2, '0') }}</span>
                                </label>
                            </div>
                            <div class="col-span-2 px-1">
                                <button @click="unselectAllDays" class="w-full btn btn-xs">
                                    Clear
                                </button>
                            </div>
                            <div class="col-span-2 px-1">
                                <button @click="selectAllDays" class="w-full btn btn-xs">
                                    All
                                </button>
                            </div>
                        </fieldset>
                    </div>

                    <span class="block text-xs tracking-tight text-warning">Unchecked `nth` days are excluded on printout, unless, none is checked.</span>
                </template>

                <div v-if="tab == 'calculation'" class="flex space-x-5">
                    <div>
                        <label class="flex items-center text-sm">
                            <input v-model="value.calculate" type="checkbox" class="checkbox-xs checkbox" />
                            <span class="ml-2">Calculate</span>
                        </label>
                        <span class="text-xs tracking-tight text-warning">Calculate total days and tardy/undertime.</span>
                    </div>

                    <div :class="{'opacity-50': !value.calculate}">
                        <label class="flex items-center text-sm">
                            <input :disabled="!value.calculate" v-model="value.weekends.regular" type="checkbox" class="checkbox-xs checkbox" />
                            <span class="ml-2">Weekends</span>
                        </label>
                        <span class="text-xs tracking-tight text-warning">Calculate weekends for regular employees.</span>
                    </div>
                </div>
            </div>
        </div>

        <template #action>
            <button type="button" class="btn btn-sm" @click="clear">Clear</button>
            <button type="button" class="btn btn-sm" @click="reset">Reset</button>
        </template>
    </Modal>
</template>
