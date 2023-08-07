<script setup>
import Modal from '@/Components/Modal.vue'
import preventTabClose from '@/Composables/preventTabClose'
import { computed, ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'

const modelValue = defineModel()

const props = defineProps({
    scanners: Object,
    options: Object,
})

const form = ref({})

props.scanners.forEach(e => form.value[e.id] = useForm({}))

const download = (scanner) => {
    const scannerForm = form.value[scanner]

    scannerForm.post(route('scanners.download', scanner), {
        preserveScroll: true,
        preserveState: true,
        onBefore: () => scannerForm.clearErrors(),
        ...(props.options ?? {})
    })
}

watch(modelValue, (show) => {
    if (show) return

    setTimeout(() => Object.entries(form.value)?.forEach(([scanner, form]) => {
        form.clearErrors()
        form.reset()
    }), 100)
})

const synchronizing = computed(() => Object.entries(form.value)?.some(([scanner, form]) => form.processing))

preventTabClose(() => synchronizing.value)
</script>

<template>
    <Modal v-model="modelValue" v-model:disableClosing="synchronizing">
        <template #header>
            Synchronize Time Logs
        </template>

        <div class="flex flex-col gap-2">
            <p class="flex items-center justify-between font-mono text-sm" v-for="scanner in scanners">
                <span>
                    {{ scanner.name }}
                </span>

                <div class="space-x-3">
                    <span class="text-sm tracking-tighter text-error opacity-90">{{ form[scanner.id].errors.message }}</span>

                    <span v-if="form[scanner.id].processing" class="text-sm opacity-50">Fetching...</span>

                    <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                        <span v-if="form[scanner.id].wasSuccessful && ! form[scanner.id].processing" class="text-sm opacity-50">Success</span>
                    </Transition>

                    <button @click="download(scanner.id)" class="btn btn-xs btn-square btn-primary" :disabled="synchronizing">
                        <svg :class="{'animate-spin': form[scanner.id].processing}" class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                            <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                        </svg>
                    </button>
                </div>
            </p>
        </div>
    </Modal>
</template>
