<script setup>
import SectionTitle from './SectionTitle.vue'
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
            <div class="px-4 py-5 shadow sm:p-6 bg-base-200/70 sm:rounded-[--rounded-box]">
                <slot name="content"></slot>
            </div>
        </div>
    </div>
</template>
