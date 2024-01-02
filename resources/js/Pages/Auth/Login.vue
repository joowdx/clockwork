<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

defineProps({
    canResetPassword: {
        type: Boolean
    },
    status: {
        type: String
    }
})

const username = ref(null)

const form = useForm({
    username: '',
    password: '',
    remember: false
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password')
    })
}

onMounted(() => {
    setTimeout(() => username.value.focus(), 100)
})
</script>

<template>
    <GuestLayout class="flex">
        <Head title="Log in" />

        <div class="flex flex-col flex-1">
            <div class="w-full text-center">
                <h1 class="pb-3 text-5xl font-bold pt-9">Login</h1>
            </div>

            <div class="flex content-center flex-1">
                <div class="hero">
                    <div
                        class="p-12 bg-gradient-to-bl via-50% via-base-100/80 to-100% to-base-200/80 from-base-300/80 rounded-none sm:rounded-sm"
                    >
                        <div class="flex-col hero-content lg:flex-row-reverse lg:gap-20">
                            <div class="text-center lg:text-left" style="text-wrap: balance;">
                                <h1 class="text-5xl font-bold">Download the app!</h1>
                                <span class="py-6 font-mono text-sm text-primary-focus">{{ $page.props.app.name }}</span>
                                <p class="py-6">
                                    Check your attendance records as we update them via your mobile device.

                                    <a class="link" href="">
                                        Click here to download.
                                    </a>
                                </p>

                                <p class="py-6 text-warning">
                                    <span class="font-bold">Note:</span> Some features are not yet available and is only available for Android devices.
                                </p>
                            </div>

                            <div class="flex-shrink-0 w-full max-w-sm rounded-sm card bg-gradient-to-br from-base-300/80">
                                <div class="card-body">
                                    <form class="space-y-3" @submit.prevent="submit">
                                        <div class="form-control">
                                            <label for="username" class="block text-sm font-medium text-base-content">
                                                Username
                                            </label>
                                            <input
                                                v-model="form.username"
                                                ref="username"
                                                id="username"
                                                type="text"
                                                class="mt-1 input input-bordered"
                                            />
                                            <InputError class="mt-0.5" :message="form.errors.username" />
                                        </div>
                                        <div class="form-control">
                                            <label for="password" class="block text-sm font-medium text-base-content">
                                                Password
                                            </label>
                                            <input
                                                v-model="form.password"
                                                id="password"
                                                type="password"
                                                class="input input-bordered"
                                            />
                                            <InputError class="mt-0.5" :message="form.errors.password" />
                                        </div>
                                        <div class="form-control">
                                            <label
                                                class="px-0 space-x-3 cursor-pointer label justify-normal"
                                                for="remember"
                                            >
                                                <input
                                                    v-model="form.remember"
                                                    class="checkbox checkbox-sm"
                                                    type="checkbox"
                                                    id="remember"
                                                />
                                                <span class="label-text">Remember me</span>
                                            </label>
                                        </div>
                                        <div class="mt-6 form-control">
                                            <button type="submit" class="btn btn-primary">Login</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
