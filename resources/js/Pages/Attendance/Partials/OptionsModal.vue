<script setup>
import Modal from '@/Components/Modal.vue'

const modelValue = defineModel()

const data = defineModel('data')

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
                        Include transmittal
                    </span>
                    <input v-model="data.transmittal" type="checkbox" class="toggle">
                </label>

                <label class="px-1 opacity-50 label-text-alt">
                    Include a copy of tramsittal in printout.
                </label>
            </div>
        </div>
    </Modal>
</template>
