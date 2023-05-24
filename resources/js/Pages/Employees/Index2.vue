<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

const args = ref({
    office: null,
})
</script>

<template>
    <AppLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Employees
            </h2>
        </template>

        <div class="px-4 py-12 sm:px-0">
            <div class="mx-auto space-y-3 max-w-7xl sm:px-6 lg:px-8 bg-gray" style="margin-top:-20px!important">
                <div class="flex justify-between col-span-4 mt-3 space-x-3">
                    <div class="flex space-x-3 align-bottom">
                        <input type="checkbox" class="toggle toggle-sm" checked />
                        <label for="print-format" class="p-0 label">
                            <span class="label-text">All</span>
                        </label>
                    </div>

                    <div class="gap-3 space-x-3 align-middle">
                        <label for="import-modal" class="tracking-tighter btn btn-xs">
                            Import
                        </label>

                        <button disabled class="tracking-tighter btn btn-xs">
                            Export
                        </button>
                    </div>
                </div>
                <div class="grid grid-cols-12 col-span-12 gap-3 pb-3">
                </div>
            </div>
        </div>
    </AppLayout>
</template>
