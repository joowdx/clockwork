<script setup>
import Modal from '@/Components/Modal.vue'
import { usePage } from '@inertiajs/vue3'
import { onMounted, ref, watch } from 'vue'

const modelValue = defineModel()

const data = defineModel('data')

const user = usePage().props.auth.user

const value = ref({
    all: Boolean(data.value.all),
    unenrolled: Boolean(data.value.unenrolled),
    unenrolled_only: data.value.unenrolled === 'only',
    sign: Boolean(data.value.sign),
})

const apply = () => {
    data.value = {...data.value, ...value.value}

    localStorage.setItem(`employee-filter-all-${user.id}`, value.value.all)

    localStorage.setItem(`employee-filter-unenrolled-${user.id}`, value.value.unenrolled)

    localStorage.setItem(`digital-signature-${user.id}`, value.value.sign)

    if (value.value.unenrolled_only) {
        data.value.unenrolled = 'only'
    } else {
        data.value.unenrolled = value.value.unenrolled
    }

    modelValue.value = false
}

onMounted(() => {
    let all = localStorage.getItem(`employee-filter-all-${user.id}`) === "true"

    let unenrolled = localStorage.getItem(`employee-filter-unenrolled-${user.id}`)

    let sign = localStorage.getItem(`digital-signature-${user.id}`)

    unenrolled = unenrolled === "true" || unenrolled === "only"

    if (all) {
        value.value.all = all
    }

    if (unenrolled) {
        value.value.unenrolled = unenrolled
    }

    if (sign) {
        value.value.sign = sign
    }

    if (all || unenrolled || sign) {
        apply()
    }
})

watch(() => value.value.all, () => value.value.unenrolled = false, { flush: 'sync' })

watch(() => value.value.unenrolled, () => value.value.unenrolled_only = false, { flush: 'sync' })
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            Settings
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
                        Show <b>all</b> employees
                    </span>
                    <input v-model="value.all" type="checkbox" class="toggle">
                </label>
                <label class="px-1 opacity-50 label-text-alt">
                    This will not include those who are not enrolled to any scanner.
                </label>
            </div>

            <div class="pb-4 form-control">
                <label class="cursor-pointer label">
                    <span class="label-text">Show <b>unenrolled</b> employees</span>
                    <input v-model="value.unenrolled" type="checkbox" class="toggle" :disabled="! value.all">
                </label>
                <label class="px-1 opacity-50 label-text-alt">
                    Include employees that are not enrolled to any scanners.
                </label>
            </div>

            <div class="form-control">
                <label class="cursor-pointer label">
                    <span class="label-text">Show <b>only</b> unenrolled</span>
                    <input v-model="value.unenrolled_only" type="checkbox" class="toggle" :disabled="! value.unenrolled">
                </label>
                <label class="px-1 opacity-50 label-text-alt">
                    Overrides show all option. Enable to show only all of the unenrolled employees.
                </label>
            </div>
        </div>

        <template #action>
            <button type="button" class="btn btn-sm" @click="apply">Apply</button>
        </template>
    </Modal>
</template>
