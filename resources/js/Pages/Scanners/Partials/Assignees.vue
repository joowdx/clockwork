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
                        <img :id="`user.${user.id}`" class="absolute top-0 object-cover w-8 h-8 rounded-full" :src="user.profile_photo_url" alt="user.name" />
                        <JetLabel class="uppercase" :for="`user.${user.id}`" :value="user.name" style="margin-top:7.5px!important;margin-left:40px;" />
                    </div>
                    <JetDangerButton v-if="!user.new" class="col-span-3 w-fit" @click="showConfirmationDialog(user.pivot.id)" >
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

            <JetSecondaryButton @click="showConfirmationDialog(true)" >
                Add
            </JetSecondaryButton>
        </template>
    </JetFormSection>

    <!-- Assignment Confirmation Modal -->
    <JetDialogModal :show="confirmation" @close="hideConfirmationDialog">
        <template #title>
            {{ confirmation === true ? 'New' : 'Delete' }} Assignee
        </template>

        <template #content>
            <div v-if="confirmation === true">
                <div class="mt-4">
                    <JetLabel value="User" />
                    <TailwindSelect class="block w-3/4 mb-2" :options="$page.props.users.map(e => ({name: `@${e.username.toUpperCase()}`, value: e.id}))" v-model="form.users" />
                    <JetInputError :message="form.errors.users" class="mt-2" />
                </div>
                <div class="mt-4">
                    <JetLabel value="Password" />
                    <JetInput type="password" class="block w-3/4 mt-1" placeholder="Password"
                                ref="password"
                                v-model="form.password"
                                @keyup.enter="add" />
                    <JetInputError :message="form.errors.password" class="mt-2" />
                </div>
                <p class="w-3/4 mt-4 text-yellow-600"> Assigned users will have read/write access to this scanner profile. Please enter your password to continue. </p>
            </div>
            <div v-else class="mt-4">
                To prevent any accidental deletion, please enter your password.
                <div class="mt-2">
                    <JetInput type="password" class="block w-3/4 mt-1" placeholder="Password"
                                ref="password"
                                v-model="form.password"
                                @keyup.enter="remove()" />

                    <JetInputError :message="form.errors.password" class="mt-2" />
                </div>
            </div>
        </template>

        <template #footer>
            <JetSecondaryButton @click="hideConfirmationDialog">
                Cancel
            </JetSecondaryButton>

            <JetButton v-if="confirmation === true" class="ml-3" @click="add" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </JetButton>

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
                form: this.$inertia.form({
                    password: null,
                    scanner: this.$page.props.scanner.id,
                    users: null,
                }),
            }
        },

        methods: {
            add() {
                this.form
                    .post(route('assignment.store'), {
                        preserveScroll: true,
                        onSuccess: () => this.hideConfirmationDialog(),
                    });
            },
            remove() {
                this.form.transform(() => ({ password: this.form.password }))
                    .delete(route('assignment.destroy', { assignment: this.confirmation }), {
                        preserveScroll: true,
                        onSuccess: () => this.hideConfirmationDialog(),
                    });
            },
            showConfirmationDialog(assignment) {
                this.confirmation = assignment

                setTimeout(() => this.$refs.password.focus(), 250)
            },
            hideConfirmationDialog() {
                this.confirmation = false

                this.form.reset()

                this.form.clearErrors('password')
            },
        },
    })
</script>
