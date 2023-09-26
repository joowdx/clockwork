<script setup>
import Modal from '@/Components/Modal.vue'
import { useForm } from '@inertiajs/vue3'
import { nextTick, onMounted, ref, watch } from 'vue'

const props = defineProps(['options', 'scanners'])

const modelValue = defineModel()

const data = defineModel('data')

const selection = ref([])

const input = ref(null)

const form = useForm({
    scanner: '',
})

let reinit = false

const search = () => {
    form.post(route('attendance'), {
        preserveScroll: true,
        preserveState: true,
        only: ['scanners']
    })
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
    const prop = mount ? data : selection

    prop.value = props.scanners
        .filter(e => e.priority)
        .map(e => e.id)
}

watch(modelValue, (show) => {
    if (show) {
        nextTick(() => input.value.focus())

        if (reinit) {
            search()
        }

        return
    }

    if (form.scanner) {
        form.scanner = ''
        reinit = true
    }
})

watch(data, data => selection.value = [...data])

onMounted(() => preselect(true))
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            Scanners
        </template>

        <div class="grid gap-3">
            <div class="w-full mb-3 input-group input-group-sm">
                <input
                    v-model="form.scanner"
                    ref="input"
                    type="text"
                    placeholder="Search"
                    class="w-full input input-bordered input-sm"
                    @keyup.enter="search"
                />
                <button @click="search" title="Search" class="btn btn-square btn-sm">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-6 h-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </button>
            </div>

            <div class="space-y-2">
                <div class="text-sm">
                    Select scanners
                </div>

                <div class="bg-base-200 pb-1 rounded-[--rounded-box]">
                    <div class="flex justify-between px-2 py-1 font-mono text-sm tracking-tighter">
                        Count: {{ selection.length }}
                    </div>

                    <div class="overflow-y-auto max-h-[10.75em]">
                        <table class="table table-xs table-zebra table-pin-rows">
                            <thead>
                                <tr v-if="scanners.length">
                                    <th class="w-[30px] max-w-[40px]"></th>
                                    <th>Name</th>
                                </tr>
                                <tr v-else>
                                    <th>No scanners...</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template v-if="scanners.length">
                                    <tr class="select-none hover h-[1px]" v-for="scanner in scanners">
                                        <td class="p-0 h-[inherit]">
                                            <label class="flex items-center justify-center h-full cursor-pointer">
                                                <input
                                                    v-model="selection"
                                                    :id="`scanner-${scanner.id}`"
                                                    type="checkbox"
                                                    class="checkbox checkbox-xs"
                                                    :value="scanner.id"
                                                >
                                            </label>
                                        </td>
                                        <td class="p-0">
                                            <label :for="`scanner-${scanner.id}`" class="block w-full px-2 py-1.5 select-none font-mono cursor-pointer">
                                                {{ scanner.name }}
                                            </label>
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
