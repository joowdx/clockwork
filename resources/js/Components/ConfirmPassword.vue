<template>
    <span>
        <span @click="startConfirmingPassword">
            <slot />
        </span>

        <Modal v-model="confirmingPassword">
            <template #header>
                {{ title }}
            </template>

            {{ content }}

            <div class="mt-4">
                <input type="password" class="block w-3/4 mt-1 input input-bordered" placeholder="Password"
                            ref="password"
                            v-model="form.password"
                            @keyup.enter="confirmPassword" />

                <InputError :message="form.error" class="mt-2" />
            </div>

            <template #action>
                <button class="btn" @click="confirmingPassword = false">
                    Cancel
                </button>

                <button class="ml-2 btn btn-secondary" @click="confirmPassword" :disabled="form.processing">
                    {{ button }}
                </button>
            </template>
        </Modal>
    </span>
</template>

<script>
import { defineComponent } from 'vue'
import Modal from './Modal.vue'
import InputError from './InputError.vue'

export default defineComponent({
    emits: ['confirmed'],

    props: {
        title: {
            default: 'Confirm Password',
        },
        content: {
            default: 'For your security, please confirm your password to continue.',
        },
        button: {
            default: 'Confirm',
        }
    },

    components: {
        Modal,
        InputError,
    },

    data() {
        return {
            confirmingPassword: false,
            form: {
                password: '',
                error: '',
            },
        }
    },

    methods: {
        startConfirmingPassword() {
            axios.get(route('password.confirmation')).then(response => {
                if (response.data.confirmed) {
                    this.$emit('confirmed');
                } else {
                    this.confirmingPassword = true;

                    setTimeout(() => this.$refs.password.focus(), 250)
                }
            })
        },

        confirmPassword() {
            this.form.processing = true;

            axios.post(route('password.confirm'), {
                password: this.form.password,
            }).then(() => {
                this.form.processing = false;
                this.closeModal()
                this.$nextTick(() => this.$emit('confirmed'));
            }).catch(error => {
                this.form.processing = false;
                this.form.error = error.response.data.errors.password[0];
                this.$refs.password.focus()
            });
        },

        closeModal() {
            this.confirmingPassword = false
            this.form.password = '';
            this.form.error = '';
        },
    }
})
</script>
