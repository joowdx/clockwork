<template>
    <jet-action-section>
        <template #title>
            Delete Scanner
        </template>

        <template #description>
            Permanently delete this scanner.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600">
                Once this scanner is deleted, all of its resources and related data will be permanently deleted. Before deleting this scanner, please download any data or information that you wish to retain.
            </div>

            <div class="mt-5">
                <jet-danger-button @click="confirmScannerDeletion">
                    Delete Scanner
                </jet-danger-button>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <jet-dialog-modal :show="confirmingScannerDeletion" @close="closeModal">
                <template #title>
                    Delete Scanner
                </template>

                <template #content>
                    Are you sure you want to delete this scanner? Once this scanner is deleted, all of its resources and related data will be permanently deleted. Please enter your password to confirm you would like to permanently delete this scanner.

                    <div class="mt-4">
                        <jet-input type="password" class="block w-3/4 mt-1" placeholder="Password"
                                    ref="password"
                                    v-model="form.password"
                                    @keyup.enter="deleteScanner"
                                    :disabled="! administrator" />

                        <jet-input-error :message="form.errors.password" class="mt-2" />

                        <jet-input-error v-if="! administrator" message="Not enough privilege." class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <jet-secondary-button @click="closeModal">
                        Cancel
                    </jet-secondary-button>

                    <jet-danger-button class="ml-3" @click="deleteScanner" :class="{ 'opacity-25': form.processing }" :disabled="form.processing || ! administrator">
                        Delete Scanner
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
                administrator: this.$page.props.user.administrator,

                confirmingScannerDeletion: false,

                form: this.$inertia.form({
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
                    onSuccess: () => this.closeModal(),
                    onError: () => this.$refs.password.focus(),
                    onFinish: () => this.form.reset(),
                })
            },

            closeModal() {
                this.confirmingScannerDeletion = false

                this.form.reset()
            },
        },
    })
</script>
