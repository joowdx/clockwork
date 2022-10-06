<template>
    <jet-action-section>
        <template #title>
            Clear Timelogs
        </template>

        <template #description>
            Clear stored timelog records.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600">
                Only try to clear timelog records when you have imported the wrong file. Please proceed with caution.
            </div>

            <div class="mt-5">
                <jet-danger-button @click="confirmScannerDeletion">
                    Clear Timelogs
                </jet-danger-button>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <jet-dialog-modal :show="confirmingScannerDeletion" @close="closeModal">
                <template #title>
                    Clear Timelogs
                </template>

                <template #content>
                    Are you sure you clear this scanner's timelog records? Once these records are deleted, it cannot be undone. Please enter your password to confirm you would like to proceed.

                    <div class="mt-4">
                        <jet-input type="password" class="block w-3/4 mt-1" placeholder="Password"
                                    ref="password"
                                    v-model="form.password"
                                    @keyup.enter="deleteScanner"
                                    :disabled="! allowed" />

                        <jet-input-error :message="form.errors.password" class="mt-2" />

                        <jet-input-error v-if="! allowed" message="Not enough privilege." class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <jet-secondary-button @click="closeModal">
                        Cancel
                    </jet-secondary-button>

                    <jet-danger-button class="ml-3" @click="deleteScanner" :class="{ 'opacity-25': form.processing }" :disabled="form.processing || ! allowed">
                        Clear Timelogs
                    </jet-danger-button>
                </template>
            </jet-dialog-modal>
        </template>
    </jet-action-section>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetActionSection from '@/Jetstream/ActionSection.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetDangerButton from '@/Jetstream/DangerButton.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'

    import Swal from 'sweetalert2'

    export default defineComponent({
        components: {
            JetActionSection,
            JetDangerButton,
            JetDialogModal,
            JetInput,
            JetInputError,
            JetSecondaryButton,
        },

        data() {
            return {
                allowed: this.$page.props.user.administrator || this.$page.props.user.id === this.$page.props.scanner.created_by,

                confirmingScannerDeletion: false,

                form: this.$inertia.form({
                    timelogs: true,
                    password: '',
                })
            }
        },

        methods: {
            confirmScannerDeletion() {
                this.confirmingScannerDeletion = true;

                setTimeout(() => this.$refs.password.focus(), 250)
            },

            deleteScanner() {
                this.form.delete(route('scanners.destroy', this.$page.props.scanner.id), {
                    preserveScroll: true,
                    onError: () => this.$refs.password.focus(),
                    onFinish: () => this.form.reset(),
                    onSuccess: () => {
                        this.closeModal();

                        Swal.fire(
                            'Clear successful',
                            'All the scanner\'s timelog records have been successfully cleared!',
                            'success'
                        )
                    },
                })
            },

            closeModal() {
                this.confirmingScannerDeletion = false

                this.form.reset()
            },
        },
    })
</script>
