<template>
    <JetFormSection @submitted="showConfirmationDialog(true)">
        <template #title>
            Scanners
        </template>

        <template #description>
            Manage employee's scanner uid information.
        </template>

        <template #form>
            <!-- Scanners -->
            <div v-for="scanner in $page.props.employee.scanners" :key="scanner.id" class="col-span-6 sm:col-span-4">
                <JetLabel class="uppercase" :for="`scanner.${scanner.id}`" :value="scanner.name" />
                <div class="grid grid-cols-12 space-x-3 align-bottom">
                    <JetInput :id="`scanner.${scanner.id}`" type="text" class="block w-full col-span-9" v-model="form.scanners[scanner.id].uid" />
                    <JetDangerButton class="col-span-3" @click="showConfirmationDialog(scanner.pivot.id)" >
                        Remove
                    </JetDangerButton>
                </div>
                <JetInputError :message="form.errors[`scanners.${scanner.id}.uid`]" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <JetSecondaryButton class="mr-3" >
                Add
            </JetSecondaryButton>

            <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </JetButton>
        </template>
    </JetFormSection>

    <!-- Update/Delete Enrollment Confirmation Modal -->
    <JetDialogModal :show="confirmation" @close="hideConfirmationDialog">
        <template #title>
            {{ confirmation === true ? 'Update' : 'Delete' }} Enrollment
        </template>

        <template #content>
            To prevent any accidental {{ confirmation === true ? 'modification' : 'deletion' }}, please enter your password.

            <div class="mt-4">
                <jet-input type="password" class="block w-3/4 mt-1" placeholder="Password"
                            ref="password"
                            v-model="form.password"
                            @keyup.enter="confirmation === true ? save() : remove()" />

                <jet-input-error :message="form.errors.password" class="mt-2" />
            </div>
        </template>

        <template #footer>
            <JetSecondaryButton @click="hideConfirmationDialog">
                Cancel
            </JetSecondaryButton>

            <JetSecondaryButton v-if="confirmation === true" class="ml-3" @click="save" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </JetSecondaryButton>

            <JetDangerButton v-else class="ml-3" @click="remove" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Delete
            </JetDangerButton>
        </template>
    </JetDialogModal>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
    import JetActionSection from '@/Jetstream/ActionSection.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetFormSection from '@/Jetstream/FormSection.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetDangerButton from '@/Jetstream/DangerButton.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import TailwindSelect from '@/Tailwind/Select.vue'

    import Swal from 'sweetalert2';

    export default defineComponent({
        components: {
            JetActionMessage,
            JetActionSection,
            JetButton,
            JetDialogModal,
            JetDangerButton,
            JetFormSection,
            JetInput,
            JetInputError,
            JetLabel,
            JetSecondaryButton,
            TailwindSelect,
        },

        data() {
            return {
                confirmation: false,
                password: '',
                form: this.$inertia.form({
                    password: '',
                    employee: this.$page.props.employee.id,
                    scanners: _.mapValues(_.mapKeys(this.$page.props.employee.scanners, e => e.id), e => ({uid:e['pivot']['uid']})),
                }),
            }
        },

        methods: {
            save() {
                this.form.post(route('enrollment.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.hideConfirmationDialog()
                    }
                });
            },
            remove() {
                this.form.transform(() => ({ password: this.form.password }))
                    .delete(route('enrollment.destroy', { enrollment: this.confirmation }), {
                        preserveScroll: true,
                        onSuccess: () => {
                            this.hideConfirmationDialog()

                            Swal.fire(
                                'Import successful',
                                'Employees updated.',
                                'success'
                            )
                        }
                    });
            },
            showConfirmationDialog(enrollment) {
                this.confirmation = enrollment

                setTimeout(() => this.$refs.password.focus(), 250)
            },
            hideConfirmationDialog() {
                this.confirmation = false

                this.form.reset()
            },
        },
    })
</script>
