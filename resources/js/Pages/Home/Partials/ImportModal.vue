<script setup>
import Modal from '@/Components/Modal.vue'
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/inertia-vue3'

const props = defineProps({
    scanners: Object,
})

const modelValue = defineModel()

const tab = ref('attlogs')

const form = useForm({
    scanner: null,
    file: null,
})

const fileInput = ref(null)

const upload = () => {
    const link =
        tab.value === 'attlogs'
            ? route('timelogs.store')
            : tab.value === 'employees'
                ? route('employees.store')
                : '404'

    form.post(link, {
        preserveScroll: true,
        preserveState: true,
        onStart: () => form.clearErrors(),
        onSuccess: () => clear(),
    })
}

const clear = () => {
    form.reset()
    form.clearErrors()
    fileInput.value.value = ''
}

const switchTab = (to) => {
    if (form.processing) return

    tab.value = to
    clear()
}

watch(() => form.file, (file) => {
    if (file == null) return

    const scanner = props.scanners.find(e => (e.attlog_file + '.dat') === file.name)

    if (scanner) {
        form.scanner = scanner.id
    }
})

watch(modelValue, (show) => {
    if (show) return

    setTimeout(() => {
        clear()
        tab.value = 'attlogs'
    }, 250)
})
</script>

<template>
    <Modal v-model="modelValue" v-model:disableClosing="form.processing">
        <template #header>
            Import
        </template>

        <div class="grid gap-3">
            <div class="w-full tabs">
                <button @click="switchTab('attlogs')" class="tab tab-bordered" :class="{'tab-active': tab === 'attlogs'}">Attlogs</button>
                <button @click="switchTab('employees')" class="tab tab-bordered" :class="{'tab-active': tab === 'employees'}">Employees</button>
            </div>

            <template v-if="tab === 'attlogs'">
                <div class="form-control">
                    <label for="scanner-import" class="px-0 label">
                        <span class="label-text">Scanner</span>
                    </label>
                    <select v-model="form.scanner" :disabled="form.processing" id="scanner-import" aria-label="Scanner" class="w-full select select-bordered select-sm">
                        <option hidden :value="null">Select scanner</option>
                        <option v-for="scanner in scanners" :value="scanner.id">
                            {{ scanner.name }}
                        </option>
                    </select>
                    <label v-if="form.errors.scanner" class="mt-1 text-sm text-error">
                        {{ form.errors.scanner }}
                    </label>
                </div>
            </template>

            <div class="form-control">
                <label for="file-import" class="px-0 label">
                    <span class="label-text">File</span>
                </label>
                <input id="file-import" ref="fileInput" @input="form.file = $event.target.files[0]" :disabled="form.processing" type="file" class="w-full file-input file-input-bordered file-input-sm"/>
                <label v-if="form.errors.file" class="mt-1 text-sm text-error">
                    {{ form.errors.file }}
                </label>
            </div>

        </div>

        <template #action>
            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                <div class="flex items-center">
                    <p v-if="form.recentlySuccessful" class="mr-3 text-sm opacity-50 text-base-content">
                        Success!
                    </p>
                </div>
            </Transition>

            <div v-if="form.processing" class="flex items-center mr-3 align-middle">
                <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                    <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                </svg>
            </div>

            <button type="button" @click="upload" :disabled="form.processing" class="btn btn-sm">Submit</button>
        </template>
    </Modal>
</template>
