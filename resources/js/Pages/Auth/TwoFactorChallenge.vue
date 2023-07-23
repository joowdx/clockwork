<script>
import { defineComponent } from 'vue'
import { Head } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'

export default defineComponent({
    components: {
        GuestLayout,
        InputError,
        Head,
    },

    data() {
        return {
            recovery: false,
            form: this.$inertia.form({
                code: '',
                recovery_code: '',
            })
        }
    },

    methods: {
        toggleRecovery() {
            this.recovery ^= true

            this.$nextTick(() => {
                if (this.recovery) {
                    this.$refs.recovery_code.focus()
                    this.form.code = '';
                } else {
                    this.$refs.code.focus()
                    this.form.recovery_code = ''
                }
            })
        },

        submit() {
            this.form.post(this.route('two-factor.login'))
        }
    }
})
</script>


<template>
    <Head title="Two-factor Confirmation" />

    <GuestLayout class="flex">
        <div class="flex items-center content-center flex-1">
            <div>
                <div class="mb-4 text-sm text-gray-600">
                    <template v-if="! recovery">
                        Please confirm access to your account by entering the authentication code provided by your authenticator application.
                    </template>

                    <template v-else>
                        Please confirm access to your account by entering one of your emergency recovery codes.
                    </template>
                </div>

                <template v-for="error in form.errors">
                    <InputError class="mb-4" :message="error" />
                </template>

                <form @submit.prevent="submit">
                    <div v-if="! recovery">
                        <label for="code" class="block text-sm font-medium text-base-content">
                            Code
                        </label>
                        <input ref="code" id="code" type="text" inputmode="numeric" class="block w-full mt-1 input input-bordered" v-model="form.code" autofocus autocomplete="one-time-code" />
                    </div>

                    <div v-else>
                        <label for="recovery_code" class="block text-sm font-medium text-base-content">
                            Recovery Code
                        </label>
                        <input ref="recovery_code" id="recovery_code" type="text" class="block w-full mt-1 input input-bordered" v-model="form.recovery_code" autocomplete="one-time-code" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <button type="button" class="text-sm text-gray-600 underline cursor-pointer hover:text-gray-900" @click.prevent="toggleRecovery">
                            <template v-if="! recovery">
                                Use a recovery code
                            </template>

                            <template v-else>
                                Use an authentication code
                            </template>
                        </button>

                        <button class="ml-4 btn btn-sm btn-primary" :disabled="form.processing">
                            Log in
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
