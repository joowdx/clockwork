<template>
    <JetFormSection @submitted="save">
        <template #title>
            User Information
        </template>

        <template #description>
            Update user's basic information.
        </template>

        <template #form>
            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="name" value="Name" />
                <JetInput id="name" type="text" class="block w-full mt-1 uppercase" v-model="form.name" autocomplete="name" />
                <JetInputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Title -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="title" value="Title" />
                <JetInput id="title" type="text" class="block w-full mt-1 uppercase" v-model="form.title" autocomplete="title" />
                <JetInputError :message="form.errors.title" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <!-- Administrator -->
                <div class="col-span-6">
                    <JetLabel class="mb-0" for="biometrics_id" value="Administrator" />
                    <JetLabel class="mb-1 text-xs text-yellow-400 dark:text-yellow-400" for="biometrics_id" value="Administrators can manage users and scanners." />
                    <TailwindSelect class="w-full" :options="[{name: 'YES', value: true}, {name: 'NO', value: false}]" v-model="form.administrator" />
                    <JetInputError :message="form.errors.administrator" class="mt-2" />
                </div>
            </div>

            <!-- Username -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="username" value="Username" />
                <JetLabel class="mb-1 text-xs text-yellow-400 dark:text-yellow-400" for="biometrics_id" value="Users can't edit their own username." />
                <JetInput id="username" type="text" class="block w-full mt-1 lowercase" v-model="form.username" autocomplete="username" />
                <JetInputError :message="form.errors.username" class="mt-2" />
            </div>

            <!-- Password -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="password" value="Password" />
                <JetInput id="password" type="password" class="block w-full mt-1" v-model="form.password" autocomplete="password" />
                <JetInputError :message="form.errors.password" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div v-if="! user" class="col-span-6 sm:col-span-4">
                <JetLabel for="password_confirmation " value="Confirm Password" />
                <JetInput id="password_confirmation " type="password" class="block w-full mt-1" v-model="form.password_confirmation " autocomplete="password_confirmation " />
                <JetInputError :message="form.errors.password_confirmation" class="mt-2" />
            </div>

            <JetButton class="hidden" :disabled="form.processing">
                Save
            </JetButton>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </JetButton>
        </template>
    </JetFormSection>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
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

        props: [
            'updateUser'
        ],

        data() {
            return {
                form: this.$inertia.form({
                    id: this.updateUser?.id,
                    name: this.updateUser?.name,
                    username: this.updateUser?.username,
                    title: this.updateUser?.title,
                    administrator: this.updateUser?.administrator,
                    password: null,
                    password_confirmation : null,
                }),
            }
        },

        methods: {
            save() {
                this.form[this.updateUser ? 'put' : 'post'](this.link, {
                    preserveScroll: true,
                    onSuccess: () => this.form.reset('password')
                });
            },
        },

        computed: {
            link() {
                return route(`users.${this.updateUser?'update':'store'}`, this.updateUser ? {user: this.form.id} :  {})
            }
        }
    })
</script>
