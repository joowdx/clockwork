<template>
    <JetFormSection @submitted="showConfirmationDialog(true)">
        <template #title>
            Asignees
        </template>

        <template #description>
            Manage users assigned.
        </template>

        <template #form>
            <!-- Scanners -->
            <div v-for="user in $page.props.scanner.users" :key="user.id" class="col-span-6 sm:col-span-4">
                <div class="grid grid-cols-12 space-x-3 align-bottom">
                    <div class="relative col-span-9 overflow-hidden">
                        <img class="absolute top-0 object-cover w-8 h-8 rounded-full" :src="$page.props.user?.profile_photo_url" alt="user.name" />
                        <JetLabel class="uppercase" :for="`user.${user.id}`" :value="user.name" style="margin-top:7.5px!important;margin-left:40px;" />
                    </div>
                    <JetDangerButton v-if="!user.new" class="col-span-3" @click="showConfirmationDialog(user.pivot.id)" >
                        Remove
                    </JetDangerButton>
                </div>
                <JetInputError :message="form.errors[`users.${user.id}.uid`]" class="mt-2" />
            </div>
            <div v-if="! $page.props.scanner.users?.length" class="col-span-6 sm:col-span-4">
                <JetLabel class="uppercase" for="empty" value="empty" />
                <div class="grid grid-cols-12 space-x-3 align-bottom">
                    <JetInput id="empty" type="text" class="block w-full col-span-12" value='Please click the "add" button below.' disabled />
                </div>
            </div>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <JetSecondaryButton @click="showConfirmationDialog(Infinity)" class="mr-3" >
                Add
            </JetSecondaryButton>

            <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </JetButton>
        </template>
    </JetFormSection>

    <!-- Enrollment Confirmation Modal -->
    <!-- <JetDialogModal :show="confirmation" @close="hideConfirmationDialog">
        <template #title>
            {{ confirmation === true ? 'Update' : confirmation === Infinity ? 'New' : 'Delete' }} Enrollment
        </template>

        <template #content>
            <div v-if="confirmation === Infinity">
                <div class="mt-4">
                    <JetLabel value="Scanner" />
                    <TailwindSelect class="block w-3/4 mb-2" :options="$page.props.scanners" v-model="scanner" />
                    <JetLabel value="Please select unique identifier (UID) for this device." />
                    <JetInput type="text" class="block w-3/4 mt-1" placeholder="New UID" v-model="uid" @keyup.enter="add" />
                    <JetInputError :message="error" class="mt-2" />
                </div>
                <p class="mt-4"> Don't forget to click the save button to save changes. </p>
            </div>
            <div v-else class="mt-4">
                To prevent any accidental {{ confirmation === true ? 'modification' : 'deletion' }}, please enter your password.
                <div class="mt-2">
                    <JetInput type="password" class="block w-3/4 mt-1" placeholder="Password"
                                ref="password"
                                v-model="form.password"
                                @keyup.enter="confirmation === true ? save() : remove()" />

                    <JetInputError :message="form.errors.password" class="mt-2" />
                </div>
            </div>
        </template>

        <template #footer>
            <JetSecondaryButton @click="hideConfirmationDialog">
                Cancel
            </JetSecondaryButton>

            <JetSecondaryButton v-if="confirmation === Infinity" class="ml-3" @click="add" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Add
            </JetSecondaryButton>

            <JetSecondaryButton v-else-if="confirmation === true" class="ml-3" @click="save" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </JetSecondaryButton>

            <JetDangerButton v-else class="ml-3" @click="remove" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Delete
            </JetDangerButton>
        </template>
    </JetDialogModal> -->
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
            console.log(this.$page.props.scanner)

            return {
                confirmation: false,
                scanner: null,
                uid: null,
                error: null,
                form: this.$inertia.form({
                    password: null,
                    scanner: this.$page.props.scanner.id,
                    users: _.mapValues(_.mapKeys(this.$page.props.scanner.users, e => e.id), e => ({uid:e['pivot']['uid']})),
                }),
            }
        },

        methods: {
            add() {
                if (this.scanner && this.uid) {

                    this.form.scanners[this.scanner.id] = {uid: this.uid }

                    this.scanner.new = true

                    this.$page.props.employee.scanners.push(this.scanner)

                    this.scanner = null

                    this.uid = null

                    this.error = null

                    this.hideConfirmationDialog()

                    return
                }
                this.error = 'Please complete the fields.'
            },
            save() {
                this.form.post(route('enrollment.store'), {
                    preserveScroll: true,
                    onSuccess: () => this.hideConfirmationDialog(),
                    onError: e => ! e.password ? this.hideConfirmationDialog() : null
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

                if (enrollment !== Infinity) {
                    setTimeout(() => this.$refs.password.focus(), 250)
                }
            },
            hideConfirmationDialog() {
                this.confirmation = false

                this.form.reset('password')

                this.form.clearErrors('password')

                this.error = null
            },
        },
    })
</script>
