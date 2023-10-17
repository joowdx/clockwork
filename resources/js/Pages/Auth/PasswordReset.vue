<script setup>
import InputError from '@/Components/InputError.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { nextTick, onMounted, ref } from 'vue'

const props = defineProps({
    user: Object
})

if (! props.user.needs_password_reset) {
    router.visit(route('home'))
}

const current_password = ref(null)

const password = ref(null)

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
})

const submit = () => {
    if (!props.user.needs_password_reset) {
        return
    }

    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        onError: () => {
            if (form.errors.current_password) {
                form.reset()
                current_password.value.focus()

                return
            }

            form.reset('password', 'password_confirmation')
            password.value.focus()
        },
    })
}

onMounted(() => {
    nextTick(() => current_password.value.focus())
})
</script>

<template>
    <AppLayout class="flex">
        <Head title="Password reset required" />

        <div class="flex content-center flex-1">
            <div class="hero">
                <div
                    class="p-12 bg-gradient-to-bl via-50% via-base-100/80 to-100% to-base-200/80 from-base-300/80 rounded-none sm:rounded-sm"
                >
                    <div class="flex-col hero-content lg:flex-row lg:gap-20">
                        <div class="text-center lg:text-left">
                            <h1 class="text-5xl font-bold">Password Reset</h1>
                            <span class="py-6 font-mono text-sm text-primary-focus">(Required)</span>
                            <p class="py-6" style="text-wrap: balance;">
                                You are required to reset your password before you can proceed.
                            </p>
                        </div>

                        <div class="flex-shrink-0 w-full max-w-sm rounded-sm card bg-gradient-to-br from-base-300/80">
                            <div class="card-body">
                                <form class="space-y-3" @submit.prevent="submit">
                                    <div class="form-control">
                                        <label for="current_password" class="block text-sm font-medium text-base-content">
                                            Current Password
                                        </label>
                                        <input
                                            v-model="form.current_password"
                                            ref="current_password"
                                            id="current_password"
                                            type="password"
                                            class="mt-1 input input-bordered"
                                        />
                                        <InputError class="mt-0.5" :message="form.errors.current_password" />
                                    </div>
                                    <div class="form-control">
                                        <label for="password" class="block text-sm font-medium text-base-content">
                                            New Password
                                        </label>
                                        <input
                                            v-model="form.password"
                                            ref="password"
                                            id="password"
                                            type="password"
                                            class="input input-bordered"
                                        />
                                        <InputError class="mt-0.5" :message="form.errors.password" />
                                    </div>
                                    <div class="form-control">
                                        <label for="password_confirmation" class="block text-sm font-medium text-base-content">
                                            Confirm Password
                                        </label>
                                        <input
                                            v-model="form.password_confirmation"
                                            id="password_confirmation"
                                            type="password"
                                            class="input input-bordered"
                                        />
                                        <InputError class="mt-0.5" :message="form.errors.password_confirmation" />
                                    </div>
                                    <div class="mt-6 form-control">
                                        <div class="py-2"></div>
                                        <button type="submit" class="btn btn-primary">Reset</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
