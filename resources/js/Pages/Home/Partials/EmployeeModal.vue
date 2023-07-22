<script setup>
import Modal from '@/Components/Modal.vue'

const modelValue = defineModel()

const employee = defineModel('employee', {
    type: Object,
    default: null,
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            {{ employee ? `Edit ${employee.name_format.fullStartLastInitialMiddle}` : 'Register' }}
        </template>
    </Modal>
</template>
