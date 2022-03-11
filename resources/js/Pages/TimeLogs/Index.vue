<template>
    <app-layout title="Logs">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Time Logs
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 bg-gray">
                <div class="grid grid-cols-12 px-6 mb-6 gap-y-2 sm:gap-x-3 sm:px-0">
                    <div class="col-span-6 sm:col-span-3">
                        <jet-label value="From:" />
                        <jet-input class="w-full" type="date" style="padding: 0.25rem 0.5em !important;" />
                    </div>
                    <div class="col-span-6 sm:col-span-3">
                        <jet-label value="To:" />
                        <jet-input class="w-full" type="date" style="padding: 0.25rem 0.5em !important;" />
                    </div>
                </div>

                <div class="flex flex-col">
                    <div class="flex items-center justify-end px-6 mb-5 space-x-3 sm:px-0">
                        <jet-input type="text" class="block w-full disabled:opacity-60" style="padding: .25rem .5em!important" autocomplete="name" placeholder="Search" />
                        <jet-secondary-button @click="showImportDialog">
                            Import
                        </jet-secondary-button>
                    </div>
                </div>
            </div>
        </div>

        <jet-dialog-modal :show="importDialog" @close="closeImportDialog">
            <template #title>
                Upload
            </template>

            <template #content>
                <div class="mb-3">
                    <div class="flex justify-center px-6 pt-5 pb-6 mt-1 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path class="text-gray-400 stroke-current" stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="file-upload" class="relative font-medium text-indigo-600 rounded-md cursor-pointer hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500 dark:text-indigo-400">
                                    <span> Upload a file </span>
                                    <input id="file-upload" name="file-upload" type="file" class="sr-only" accept=".dat,.csv" @change="upload">
                                </label>
                                <!-- <p class="pl-1 dark:text-gray-300">or drag and drop</p> -->
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                CSV and DAT file up to 10MB
                            </p>
                        </div>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Import employee information from csv or device log from dat file
                </p>
                <!-- <jet-input-error message="CSV wrong format" class="mt-2" /> -->
            </template>

            <template #footer>
                <jet-secondary-button @click="closeImportDialog">
                    Cancel
                </jet-secondary-button>

                <jet-button class="ml-3" @click="closeImportDialog" >
                 <!-- :class="{ 'opacity-25': form.processing }" :disabled="form.processing"> -->
                    Import
                </jet-button>
            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import Filters from '@/Pages/TimeLogs/Partials/Filters.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'

    export default defineComponent({
        components: {
            Link,
            AppLayout,
            Filters,
            JetButton,
            JetDialogModal,
            JetLabel,
            JetInput,
            JetInputError,
            JetSecondaryButton,
        },

        data() {
            return {
                importDialog: false,
            }
        },

        methods: {
            showImportDialog() {
                this.importDialog = true
            },

            closeImportDialog() {
                this.importDialog = false
            },

            upload() {

            }
        },
    })
</script>
