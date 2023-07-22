<script setup>
const modelValue = defineModel('modelValue', {
    type: Boolean,
    default: false
})

const closeable = defineModel('closeable', {
    type: Boolean,
    default: true
})

const disableClosing = defineModel('disableClosing', {
    type: Boolean,
    default: false
})

const toggle = () => (modelValue.value = !modelValue.value)
</script>

<template>
    <Teleport to="body">
        <div class="modal modal-bottom sm:modal-middle" :class="{ 'modal-open': modelValue }">
            <div class="modal-box bg-base-100">
                <div v-if="closeable" class="absolute right-3 top-3">
                    <button v-if="closeable" :disabled="disableClosing" type="button" @click="toggle" class="btn btn-sm btn-circle" style="display: initial;">
                        ✕
                    </button>
                </div>

                <h3 class="mb-4 text-lg font-bold" v-if="$slots.header">
                    <slot name="header"> </slot>
                </h3>

                <slot> </slot>

                <div v-if="$slots.action" class="modal-action">
                    <slot name="action">

                    </slot>
                </div>
            </div>
        </div>
    </Teleport>
</template>
