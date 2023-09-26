<script setup>
import Modal from '@/Components/Modal.vue'
import dayjs from 'dayjs'
import { onMounted, ref, watch } from 'vue'

import advancedFormat from 'dayjs/plugin/advancedFormat'
import duration from 'dayjs/plugin/duration'

dayjs.extend(advancedFormat)
dayjs.extend(duration)

const modelValue = defineModel()

const data = defineModel('data')

const selection = ref([])

const date = ref('')

const input = ref(null)

const add = () => {
    if (!date.value) {
        return
    }

    if (selection.value.includes(date.value)) {
        date.value = ''

        input.value.showPicker()

        return
    }

    selection.value.push(date.value)

    selection.value.sort()

    date.value = ''

    input.value.showPicker()
}

const remove = (date) => {
    selection.value.splice(selection.value.indexOf(date), 1)
}

const reset = () => {
    preselect()
    save()
}

const clear = () => {
    selection.value = []
    save()
}

const save = () => {
    data.value = [...selection.value]
    modelValue.value = false
}

const preselect = (mount = false) => {
    const today = dayjs()

    const previous = today.date() <= 15

    const end = previous ? today.subtract(1, 'month').endOf('month').date() : 15

    const prop = mount ? data : selection

    prop.value = [...Array(end).keys()].map(e => e + 1)
        .map(e => dayjs().startOf('month').set('date', e))
        .filter(e => e.day() === 1)
        .map(e => e.format('YYYY-MM-DD'))
}

watch(data, data => selection.value = [...data])

watch(modelValue, (show) => {
    if (show) {
        selection.value = [...data.value]
    }
})

onMounted(() => preselect(true))
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            Dates
        </template>

        <div class="grid gap-3">
            <div class="form-control">
                <label for="scanner-import" class="px-0 pt-0 label">
                    <span class="label-text">Add a date</span>
                </label>
                <div class="flex w-full gap-3">
                    <input
                        v-model="date"
                        ref="input"
                        type="date"
                        class="flex-1 input input-bordered input-sm"
                        @keyup.enter="add"
                    >
                    <button
                        class="btn btn-sm btn-primary"
                        @click="add"
                    >
                        Add
                    </button>
                </div>
            </div>

            <div class="space-y-2">
                <div class="text-sm">
                    Selected dates
                </div>

                <div class="bg-base-200 pb-1 rounded-[--rounded-box]">
                    <div class="flex justify-between px-2 py-1 font-mono text-sm tracking-tighter">
                        Count: {{ selection.length }}
                    </div>

                    <div class="overflow-y-auto max-h-[10.75em]">
                        <table class="table table-xs table-zebra table-pin-rows">
                            <thead>
                                <tr v-if="selection.length">
                                    <th>Weekday</th>
                                    <th>Day</th>
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th></th>
                                </tr>
                                <tr v-else>
                                    <th>No selection...</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="selection.length" >
                                    <tr class="select-none hover" v-for="date in selection">
                                        <td>{{ dayjs(date).format('dddd') }}</td>
                                        <td>{{ dayjs(date).format('DD') }}</td>
                                        <td>{{ dayjs(date).format('MMMM') }}</td>
                                        <td>{{ dayjs(date).format('YYYY') }}</td>
                                        <td class="w-1/12 text-right">
                                            <button @click="remove(date)" class="btn btn-xs btn-primary">
                                                Remove
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <tr v-else>
                                    <td class="font-mono italic tracking-wide text-base-content/50 h-[33px]">
                                        ----------------------------------------------------------------
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <template #action>
            <button @click="reset" class="btn btn-sm">
                Reset
            </button>

            <button @click="clear" class="btn btn-sm">
                Clear
            </button>

            <button @click="save" class="btn btn-sm btn-primary">
                Save
            </button>
        </template>
    </Modal>
</template>

<style scoped>
.table :where(thead, tbody) :where(tr:not(:last-child)), .table :where(thead, tbody) :where(tr:first-child:last-child) {
    border-bottom: none;
}
</style>
