<script setup>
import Alert from './Alert.vue'
import { toasts } from '@/Composables/toasts'

</script>

<template>
    <Teleport to="body">
        <div class="z-[99] items-end gap-0 toast">
            <div class="text-right w-fit" v-for="toast in toasts" :key="toast.title+toast.message+toast.type">
                <Alert class="mt-2.5" :alert="toast"/>
            </div>
        </div>
    </Teleport>
</template>
