<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue'
import InputError from '@/Components/InputError.vue'
import Modal from '@/Components/Modal.vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { nextTick, onMounted, ref } from 'vue'

const props = defineProps(['encrypted', 'employee', 'name', 'proceed'])

const modal = ref(false)

const name = useForm({
    name: {
        first: '',
        middle: '',
        last: '',
        extension: '',
    }
})

const pin = useForm({
    pin: '',
})

const search = () => {
    name.post(route('query.search'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            modal.value = true

            if (props.proceed) {
                document.getElementById('pin').focus()
            }
        },
    })
}

const check = () => {
    pin.patch(route('pin.check', props.employee), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: async () => {
            const encrypted = await axios.post(route('encrypt'), { message: '1234' }).then(({data}) => data.encrypted)

            const link = route('query.result', props.employee)

            const headers = {'X-Employee-Token': encrypted}

            router.visit(link, { headers })
        },
        onError: () => {
            pin.reset()

            document.getElementById('pin').focus()
        },

    })
}

onMounted(() => {
    router.reload({ data: { employee: props.employee }, replace: true })

    nextTick(() => {
        modal.value = Boolean(props.employee)

        document.getElementById(props.proceed ? 'pin' : 'name_first').focus()
    })
})
</script>

<template>
    <GuestLayout class="flex">
        <Head title="Query" />

        <div class="flex content-center flex-1">
            <div class="hero">
                <div class="p-12 bg-gradient-to-bl via-50% via-base-100/80 to-100% to-base-200/80 from-base-300/80 rounded-none sm:rounded-sm">
                    <div class="flex-col hero-content lg:flex-row lg:gap-20">
                        <div class="text-center lg:text-left" style="text-wrap: balance;">
                            <h1 class="text-5xl font-bold">
                                Download the app!
                            </h1>
                            <span class="py-4 font-mono text-sm text-primary-focus">{{ $page.props.app.name }}</span>

                            <p class="py-4">
                                Check your attendance records as we update them via your mobile device.

                                <a class="link" href="">
                                    Click here to download.
                                </a>
                            </p>

                            <p class="py-4 text-warning">
                                <span class="font-bold">Note:</span> Some features are not yet available and is only available for Android devices.
                            </p>

                            <h1 class="py-4 text-5xl font-bold">
                                Need Help ?
                            </h1>

                            <div class="w-full join join-vertical">
                                <div class="border collapse collapse-arrow join-item border-base-300">
                                    <input type="radio" name="help" checked="checked" />
                                    <div class="text-xl font-medium collapse-title">
                                        What are these fields for?
                                    </div>
                                    <div class="collapse-content">
                                        <p>First, you need to tell us who you are so we know whom we are searching for. Please fill out all the necessary fields, namely: your first name, last name, middle name, and name extension.</p>
                                    </div>
                                </div>
                                <div class="border collapse collapse-arrow join-item border-base-300">
                                    <input type="radio" name="help" />
                                    <div class="text-xl font-medium collapse-title">
                                        Not found?
                                    </div>
                                    <div class="collapse-content">
                                        <p>If you're having trouble finding your record, try leaving out your middle name, using just the initial, or excluding any name extensions like Jr., Sr., III. Adjusting these fields might help locate your information in the database.</p>
                                    </div>
                                </div>
                                <div class="border collapse collapse-arrow join-item border-base-300">
                                    <input type="radio" name="help" />
                                    <div class="text-xl font-medium collapse-title">
                                        Already tried, still not found?
                                    </div>
                                    <div class="collapse-content">
                                        <p>Please don't hesitate to report it directly to us. We're here to assist you, and we'll do our utmost to resolve this issue you may have. Also, your feedback and questions are important to us, and we appreciate your communication.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0 w-full max-w-sm rounded-sm card bg-gradient-to-br from-base-300/80">
                            <div class="card-body">
                                <form @submit.prevent="search" class="flex flex-col">
                                    <div class="mt-0 divider">
                                        Name
                                    </div>

                                    <div class="pb-3 form-control">
                                        <label for="name_first" class="block text-sm font-medium text-base-content">
                                            First
                                        </label>

                                        <input
                                            v-model="name.name.first"
                                            id="name_first"
                                            type="text"
                                            class="mt-1 input input-bordered"
                                        />
                                        <InputError class="mt-0.5" :message="name.errors['name.first']" />
                                    </div>

                                    <div class="pb-3 form-control">
                                        <label for="name_middle" class="block text-sm font-medium text-base-content">
                                            Middle
                                        </label>

                                        <input
                                            v-model="name.name.middle"
                                            id="name_middle"
                                            type="text"
                                            class="mt-1 input input-bordered"
                                        />
                                        <InputError class="mt-0.5" :message="name.errors['name.middle']" />
                                    </div>

                                    <div class="pb-3 form-control">
                                        <label for="name_last" class="block text-sm font-medium text-base-content">
                                            Last
                                        </label>

                                        <input
                                            v-model="name.name.last"
                                            id="name_last"
                                            type="text"
                                            class="mt-1 input input-bordered"
                                        />
                                        <InputError class="mt-0.5" :message="name.errors['name.last']" />
                                    </div>

                                    <div class="pb-3 form-control">
                                        <label for="extension" class="block text-sm font-medium text-base-content">
                                            Extension
                                        </label>

                                        <input
                                            v-model="name.name.extension"
                                            id="extension"
                                            type="text"
                                            class="mt-1 input input-bordered"
                                        />
                                        <InputError class="mt-0.5" :message="name.errors['name.extension']" />
                                    </div>

                                    <InputError class="mt-0.5" :message="name.errors.employee" />

                                    <div class="mt-3 form-control">
                                        <button class="btn btn-primary">
                                            Submit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal v-model="modal">
            <template #header>
                {{ proceed ? 'Verification' : 'Action Required'}}
            </template>

            <form v-if="proceed" class="space-y-3" @submit.prevent="check">
                <div class="space-y-1">
                    <p class="text-lg">
                        Hello, <span class="font-bold">{{ $page.props.name }} !</span>
                    </p>

                    <p class="text-base">
                        Save time typing your name by <span class="italic underline">bookmarking</span> this page!
                    </p>
                </div>

                <div class="form-control">
                    <label for="pin" class="block text-sm font-medium text-base-content">
                        Please enter your pin to continue...
                    </label>

                    <input
                        v-model="pin.pin"
                        id="pin"
                        type="password"
                        class="mt-1 input input-bordered input-sm"
                    />

                    <InputError class="mt-1" :message="pin.errors.pin" />
                </div>

                <div class="text-sm text-warning">
                    If you happen to have forgotten your password, you can click the reset button below.
                    Just follow to procedure just like the first time you set it up.
                </div>

                <div class="space-x-3 text-right">
                    <Link as="button" type="button" :href="route('pin.reset', {employee, action: 'reset'})" replace class="btn btn-sm">
                        Reset
                    </Link>

                    <Link as="button" type="button" :href="route('query.search')" replace class="btn btn-sm">
                        Cancel
                    </Link>

                    <button class="btn btn-primary btn-sm">
                        Submit
                    </button>
                </div>
            </form>

            <div v-else-if="employee" class="flex flex-col gap-3">
                <div class="text-lg text-center">
                    Welcome, <span class="font-bold">{{ $page.props.name }} !!</span>
                </div>

                <div class="text-warning">
                    You are required to set up your pin first before you will be able to use this feature.
                    Please do so by clicking the setup button below.
                </div>

                <div class="space-x-3 text-right">
                    <Link as="button" :href="route('query.search')" replace class="btn btn-sm">
                        Cancel
                    </Link>

                    <Link :href="route('pin.initialize', employee)" class="btn btn-primary btn-sm">
                        Setup
                    </Link>
                </div>
            </div>
        </Modal>
    </GuestLayout>
</template>
