<template>
    <FormSection @submitted="updatePassword">
        <template #title>
            Update Password
        </template>

        <template #description>
            Ensure your account is using a long, random password to stay secure.
        </template>

        <template #form>
            <!-- Current Password -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label ref="current_password" for="current_password" class="block text-sm font-medium text-base-content"> Current Password </label>
                <input v-model="form.current_password" id="current_password" type="password" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors.current_password" />
            </div>

            <!-- New Password -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label ref="password" for="password" class="block text-sm font-medium text-base-content"> New Password </label>
                <input v-model="form.password" id="password" type="password" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors.password" />
            </div>

            <!-- Confirm Password -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label for="password_confirmation" class="block text-sm font-medium text-base-content"> Confirm Password </label>
                <input v-model="form.password_confirmation" id="password_confirmation" type="password" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors.password_confirmation" />
            </div>
        </template>

        <template #actions>
            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                <p v-if="form.recentlySuccessful" class="mr-3 text-sm opacity-50 text-base-content">Saved.</p>
            </Transition>

            <button class="btn btn-primary" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </button>
        </template>
    </FormSection>
</template>

<script>
import { defineComponent } from 'vue'
import FormSection from '@/Components/FormSection.vue'
import InputError from '@/Components/InputError.vue'

export default defineComponent({
    components: {
        InputError,
        FormSection,
    },

    data() {
        return {
            form: this.$inertia.form({
                current_password: '',
                password: '',
                password_confirmation: '',
            }),
        }
    },

    methods: {
        updatePassword() {
            this.form.put(route('user-password.update'), {
                errorBag: 'updatePassword',
                preserveScroll: true,
                onSuccess: () => this.form.reset(),
                onError: () => {
                    if (this.form.errors.password) {
                        this.form.reset('password', 'password_confirmation')
                        this.$refs.password.focus()
                    }

                    if (this.form.errors.current_password) {
                        this.form.reset('current_password')
                        this.$refs.current_password.focus()
                    }
                }
            })
        },
    },
})
</script>
