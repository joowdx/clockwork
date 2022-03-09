<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <jet-section-title>
            <template #title>
                <div class="dark:text-gray-100">
                    <slot name="title"></slot>
                </div>
            </template>

            <template #description>
                <div class="dark:text-gray-500">
                    <slot name="description"></slot>
                </div>
            </template>
        </jet-section-title>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <div class="px-4 py-5 bg-white shadow sm:p-6 dark:bg-gray-900 sm:rounded-lg">
                <slot name="content"></slot>
            </div>
        </div>
    </div>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetSectionTitle from './SectionTitle.vue'

    export default defineComponent({
        components: {
            JetSectionTitle,
        }
    })
</script>
