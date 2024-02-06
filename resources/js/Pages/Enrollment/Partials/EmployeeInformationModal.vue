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
            {{ employee ? `Enrolled scanners for ${employee?.name_format?.shortStartLastInitialFirst}` : '' }}
        </template>

        <div class="w-full mb-5 tabs">
            <button class="tab tab-bordered tab-active">Scanners</button>
        </div>

        <div class="space-y-2">
            <div v-if="employee?.scanners.length > 1" class="grid grid-cols-2 px-2 pb-2 pl-1 gap-x-3 gap-y-2">
                <div v-for="scanner in employee.scanners" class="form-control">
                    <label :for="`scanner_form-${scanner.id}`" class="flex items-center justify-between text-sm font-medium text-base-content">
                        <span class="tracking-tighter lowercase">
                            {{ scanner.name }}
                        </span>
                    </label>
                    <input :value="scanner.pivot.uid" readonly type="text" class="mt-1 uppercase input-sm input input-bordered" />
                </div>
            </div>
        </div>
    </Modal>
</template>
