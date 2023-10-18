<script setup>
import FormSection from '@/Components/FormSection.vue'
import InputError from '@/Components/InputError.vue'
import { useForm, usePage } from '@inertiajs/vue3'

const alerts = usePage().props.alerts

const form = useForm({
    user: {
        type: alerts.user?.type ?? null,
        title: alerts.user?.title,
        message: alerts.user?.message,
        dismissable: alerts.user?.dismissable ?? true,
    },
    guest: {
        type: alerts.guest?.type ?? null,
        title: alerts.guest?.title,
        message: alerts.guest?.message,
        dismissable: alerts.guest?.dismissable ?? true,
    },
})

const submit = () => {
    form.put(route('configuration.set.alert'), {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <FormSection @submitted="submit">
        <template #title>
            Alerts
        </template>

        <template #description>
            Set up banner alerts for standard users and guest visitors.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4 divider">Users</div>

            <!-- Alert Title -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label ref="title" for="title" class="block text-sm font-medium text-base-content"> Title </label>
                <input v-model="form.user.title" id="title" type="text" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors['user.title']" />
            </div>

            <!-- Alert Message -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label ref="message" for="message" class="block text-sm font-medium text-base-content"> Message </label>
                <input v-model="form.user.message" id="message" type="text" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors['user.message']" />
            </div>

            <!-- Alert Type and Dismissable -->
            <div class="grid grid-cols-2 col-span-6 gap-6 sm:col-span-4">
                <div class="form-control">
                    <label ref="type" for="type" class="block text-sm font-medium text-base-content"> Type </label>
                    <select v-model="form.user.type" class="mt-1 select select-bordered">
                        <option :value="null">Normal</option>
                        <option value="error">Error</option>
                        <option value="info">Info</option>
                        <option value="question">Question</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                    </select>
                    <InputError class="mt-0.5" :message="form.errors['user.type']" />
                </div>

                <div class="form-control">
                    <label ref="dismissable" for="dismissable" class="block text-sm font-medium text-base-content"> Dismissable </label>
                    <select v-model="form.user.dismissable" class="mt-1 select select-bordered">
                        <option :value="true">Yes</option>
                        <option :value="false">No</option>
                    </select>
                    <InputError class="mt-0.5" :message="form.errors['user.dismissable']" />
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4 divider">Guests</div>

            <!-- Alert Title -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label ref="title" for="title" class="block text-sm font-medium text-base-content"> Title </label>
                <input v-model="form.guest.title" id="title" type="text" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors['guest.title']" />
            </div>

            <!-- Alert Message -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label ref="message" for="message" class="block text-sm font-medium text-base-content"> Message </label>
                <input v-model="form.guest.message" id="message" type="text" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors['guest.message']" />
            </div>

            <!-- Alert Type and Dismissable -->
            <div class="grid grid-cols-2 col-span-6 gap-6 sm:col-span-4">
                <div class="form-control">
                    <label ref="type" for="type" class="block text-sm font-medium text-base-content"> Type </label>
                    <select v-model="form.guest.type" class="mt-1 select select-bordered">
                        <option :value="null">Normal</option>
                        <option value="error">Error</option>
                        <option value="info">Info</option>
                        <option value="question">Question</option>
                        <option value="success">Success</option>
                        <option value="warning">Warning</option>
                    </select>
                    <InputError class="mt-0.5" :message="form.errors['guest.type']" />
                </div>

                <div class="form-control">
                    <label ref="dismissable" for="dismissable" class="block text-sm font-medium text-base-content"> Dismissable </label>
                    <select v-model="form.guest.dismissable" class="mt-1 select select-bordered">
                        <option :value="true">Yes</option>
                        <option :value="false">No</option>
                    </select>
                    <InputError class="mt-0.5" :message="form.errors['guest.dismissable']" />
                </div>
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
