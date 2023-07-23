<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { onMounted, ref } from 'vue'

const input = ref(null)

const form = useForm({
    password: ''
})

const submit = () => {
    form.post(route('password.confirm'), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => form.reset()
    })
}

onMounted(() => setTimeout(() => input.value.focus(), 200))
</script>

<template>
    <GuestLayout class="flex">
        <Head title="Confirm Password" />

        <div class="flex items-center flex-1 px-6">
            <div class="grid gap-4">
                <p class="text-sm text-base-content">
                    This is a secure area of the application. Please confirm your password before continuing.
                </p>

                <form @submit.prevent="submit">
                    <div class="form-control">
                        <label for="password" class="label">
                            <span class="label-text">Password</span>
                        </label>
                        <input
                            ref="input"
                            v-model="form.password"
                            id="password"
                            type="password"
                            class="input input-bordered input-primary"
                        />
                        <InputError class="mt-3" :message="form.errors.password" />
                    </div>
                    <div class="flex justify-end mt-4">
                        <button
                            class="ml-4 btn btn-primary"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Confirm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>
