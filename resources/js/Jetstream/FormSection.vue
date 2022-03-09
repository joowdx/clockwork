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
            <form @submit.prevent="$emit('submitted')">
                <div class="px-4 py-5 bg-white shadow dark:bg-gray-900 sm:p-6"
                    :class="hasActions ? 'sm:rounded-tl-md sm:rounded-tr-md' : 'sm:rounded-md'">
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form"></slot>
                    </div>
                </div>

                <div class="flex items-center justify-end px-4 py-3 text-right shadow bg-gray-50 dark:bg-gray-700 sm:px-6 sm:rounded-bl-md sm:rounded-br-md" v-if="hasActions">
                    <slot name="actions"></slot>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetSectionTitle from './SectionTitle.vue'

    export default defineComponent({
        emits: ['submitted'],

        components: {
            JetSectionTitle,
        },

        computed: {
            hasActions() {
                return !! this.$slots.actions
            }
        }
    })
</script>
