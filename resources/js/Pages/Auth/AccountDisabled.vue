<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue';

const props = defineProps({
    user: Object
})

const disallowed = computed(() => props.user.disabled || props.user.type === 2)

onMounted(() => {
    if (! disallowed.value) {

        router.visit(route('home'), {
            method: 'get'
        })
    }
})
</script>

<template>
    <AppLayout class="flex">
        <Head title="Cannot Proceed" />

        <div class="flex items-center justify-center flex-1 px-6">
            <div class="grid gap-4 p-4 bg-base-200 rounded-[--rounded-box]">
                <p class="font-bold text-base-content">
                    <template v-if="user.disabled">
                        YOUR ACCOUNT IS DISABLED
                    </template>

                    <template v-else-if="user.type === 2">
                        SYSTEM ACCOUNT DETECTED
                    </template>
                </p>
                <p v-if="user.type === 2" class="text-sm">
                    System account logins are disabled
                </p>
            </div>
        </div>
    </AppLayout>
</template>
