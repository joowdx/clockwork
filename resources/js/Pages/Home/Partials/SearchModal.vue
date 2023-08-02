<script setup>
import Modal from '@/Components/Modal.vue'
import { ref, watch } from 'vue'

const modelValue = defineModel()

const loading = ref(false)

const results = ref([])

const input = ref(null)

const uid = ref('')

const error = ref('')

const search = async () => {
    loading.value = true

    results.value = await axios.get('api/uid', { params: { uid: uid.value } })
        .catch(err => error.value = err.response.data.message)
        .then(result => {
            if (result.status === 200) {
                error.value = null

                return result.data
            }
        })
        .finally(() => {
            loading.value = false
            focus()
        })
}

const focus = () => input.value.focus()

const reset = () => {
    uid.value = null
    error.value = null
    results.value = []
}

watch(modelValue, (show) => {
    if (show) {
        setTimeout(focus, 250)

        return
    }

    setTimeout(reset, 250)
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            Search
        </template>

        <div class="form-control">
            <label for="uid-search-input-field" class="px-0 pt-0 label">
                <span class="label-text">Search by uid…</span>

                <div v-if="loading" class="flex items-center mr-3 align-middle">
                    <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                        <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                    </svg>
                </div>
            </label>
            <div class="w-full mb-3 input-group input-group-sm">
                <input
                    v-model="uid"
                    ref="input"
                    id="uid-search-input-field"
                    type="number"
                    placeholder="Enter Uid"
                    class="w-full input input-bordered input-sm"
                    oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');"
                    :readonly="loading"
                    @keyup.enter="search"
                />
                <button @click="search" title="Search" class="btn btn-square btn-sm" :disabled="loading">
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

            <label class="text-sm text-error">
                {{ error }}
            </label>

            <div class="flex flex-col gap-2 overflow-y-auto max-h-96" v-if="results?.length">
                <div v-for="employee in results" class="flex flex-col w-full" :class="{'opacity-50': ! employee.active}">
                    <a target="blank " class="no-underline link">
                        {{ employee.name }}
                        <span class="font-mono text-sm lowercase opacity-70" :class="{'italic': ! employee.office}"> ({{ employee.office ? employee.office : 'no office set' }}) </span>
                    </a>

                    <div class="flex gap-2">
                        <a
                            v-for="scanner in employee.scanners"
                            :href="route('scanners.index', { search: scanner.name, show: scanner.id })"
                            target="blank"
                            class="p-1 font-mono tracking-tighter no-underline lowercase link badge badge-sm badge-primary"
                        >
                            {{ scanner.name }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<style scoped>
/* Chrome, Safari, Edge, Opera */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* Firefox */
input[type=number] {
    appearance: textfield;
    -moz-appearance: textfield;
}
</style>
