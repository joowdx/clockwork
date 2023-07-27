<script>
import { defineComponent } from 'vue'
import SectionTitle from './SectionTitle.vue'

export default defineComponent({
    emits: ['submitted'],

    components: {
        SectionTitle
    },

    computed: {
        hasActions() {
            return !!this.$slots.actions
        }
    }
})
</script>

<template>
    <div class="md:grid md:grid-cols-4 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title"></slot>
            </template>

            <template #description>
                <slot name="description"></slot>
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-3">
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="px-4 py-5 bg-base-200/70 sm:p-6"
                    :class="
                        hasActions
                            ? 'sm:rounded-tl-[--rounded-box] sm:rounded-tr-[--rounded-box]'
                            : 'sm:rounded-[--rounded-box]'
                    "
                >
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form"></slot>
                    </div>
                </div>

                <div
                    class="flex items-center justify-end px-4 py-3 text-right shadow bg-base-300/70 sm:px-6 sm:rounded-bl-[--rounded-box] sm:rounded-br-[--rounded-box]"
                    v-if="hasActions"
                >
                    <slot name="actions"></slot>
                </div>
            </form>
        </div>
    </div>
</template>
