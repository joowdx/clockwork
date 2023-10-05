<script setup>
import Modal from '@/Components/Modal.vue'
import { usePage } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

const modelValue = defineModel()

const data = defineModel('data')

const user = usePage().props.auth.user

const value = ref({
    transmittal: Boolean(data.value.transmittal)
})

const apply = () => {
    data.value = { ...data.value, ...value.value }

    localStorage.setItem(`transmittal-inclusion-${user.id}`, value.value.transmittal)

    localStorage.setItem(`digital-signature-${user.id}`, value.value.sign)

    modelValue.value = false
}

onMounted(() => {
    let transmittal = localStorage.getItem(`transmittal-inclusion-${user.id}`)

    let sign = localStorage.getItem(`digital-signature-${user.id}`)

    if (transmittal) {
        value.value.transmittal = transmittal
    }

    if (sign) {
        value.value.sign = sign
    }

    if (transmittal || sign) {
        apply()
    }
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            Options
        </template>

        <div class="flex-1 w-full">
            <div class="pb-4 form-control">
                <label class="cursor-pointer label">
                    <span class="label-text">
                        Digitally <b>sign</b> documents
                    </span>
                    <input v-model="value.sign" type="checkbox" class="toggle">
                </label>
                <label class="px-1 opacity-50 label-text-alt">
                    Automatically sign all document printouts electronically.
                </label>
            </div>

            <div class="pb-4 form-control">
                <label class="cursor-pointer label">
                    <span class="label-text">
                        Include transmittal
                    </span>
                    <input v-model="value.transmittal" type="checkbox" class="toggle">
                </label>

                <label class="px-1 opacity-50 label-text-alt">
                    Include a copy of tramsittal in printout.
                </label>
            </div>
        </div>

        <template #action>
            <button type="button" class="btn btn-sm" @click="apply">Apply</button>
        </template>
    </Modal>
</template>
