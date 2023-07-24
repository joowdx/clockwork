<template>
    <jet-action-section>
        <template #title>
            Download Timelogs
        </template>

        <template #description>
            Download attlogs from the scanner.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600">
                Be sure to properly configure the scanner's driver, ip address, and port before proceeding.
            </div>

            <div class="mt-5">
                <jet-button @click="downloadAttlogs" :disabled="form.processing">
                    Download Timelogs
                </jet-button>
            </div>
        </template>
    </jet-action-section>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetActionSection from '@/Jetstream/ActionSection.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'

    import Swal from 'sweetalert2'

    export default defineComponent({
        components: {
            JetActionSection,
            JetButton,
            JetDialogModal,
            JetInput,
            JetInputError,
            JetSecondaryButton,
        },

        data() {
            return {
                form: this.$inertia.form()
            }
        },

        methods: {
            downloadAttlogs() {
                this.form.post(route('scanners.download', this.$page.props.scanner.id), {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire(
                            'Download successful',
                            'All the scanner\'s timelog records have been successfully downloaded!',
                            'success'
                        )
                    },
                    onError: (exception) => {
                        Swal.fire(
                            'Download failed',
                            exception.message,
                            'warning'
                        )
                    }
                })
            },
        },
    })
</script>
