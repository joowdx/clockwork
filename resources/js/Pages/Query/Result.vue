<script setup>
import InputError from '@/Components/InputError.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { onMounted } from 'vue';

const props = defineProps(['employee', 'pin', 'timelogs', 'month', 'reload'])

const form = useForm({
    month: props.month,
})

onMounted(() => {
    if (props.reload) {
        router.post(route('query.result', props.employee.id), { pin: props.pin }, { replace: true })
    }
})
</script>

<template>
    <GuestLayout class="flex">
        <Head :title="`Query | ${employee.name_format.shortStartLastInitialFirst}`" />

        <div class="flex content-center flex-1">
            <div class="hero">
                <div class="p-12 bg-gradient-to-bl via-50% via-base-100/80 to-100% to-base-200/80 from-base-300/80 rounded-none sm:rounded-sm">
                    <div class="flex-col hero-content lg:flex-row-reverse lg:gap-20">
                        <div class="text-center lg:text-left">
                            <h1 class="text-5xl font-bold">
                                Employee Query
                            </h1>

                            <span class="py-6 font-mono text-sm text-primary-focus">
                                {{ $page.props.app.label }}
                            </span>

                            <p class="py-6">
                                Hello <span class="font-bold">{{ employee.name_format.fullInitialMiddle }}</span>,
                                select what month you want to get started.
                            </p>

                            <p class="text-error">
                                <span class="font-mono text-lg italic font-extrabold">
                                    what-you-see-is-what-you-get...
                                </span>

                                <span class="whitespace-nowrap">
                                    <span class="text-sm">
                                        data reflected as is
                                    </span>

                                    <span class="font-mono text-sm font-extrabold">
                                        (!)
                                    </span>
                                </span>
                            </p>

                            <p class="py-3 text-justify lg:text-left">
                                If you have any concerns or questions regarding your records, please don't hesitate to report them directly to us. We're here to assist you, and we'll do our utmost to resolve any issues you may have. Your feedback and questions are important to us, and we appreciate your communication.
                            </p>
                        </div>

                        <div class="flex-shrink-0 w-full max-w-lg rounded-sm card bg-gradient-to-br from-base-300/80">
                            <div class="card-body min-h-[492px]">
                                <div class="items-end form-control">
                                    <label for="Month" class="block text-sm font-medium sr-only text-base-content">
                                        Month
                                    </label>

                                    <input
                                        v-model="form.month"
                                        id="Month"
                                        type="month"
                                        class="max-w-xs input input-bordered input-sm"
                                    />

                                    <InputError class="mt-0.5" :message="form.errors.month" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>
