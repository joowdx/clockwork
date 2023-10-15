<script setup>
import InputError from '@/Components/InputError.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import { computed, nextTick, onMounted, ref } from 'vue'

const props = defineProps(['action', 'employee', 'scanners'])

const tab = ref(props.action === 'update' ? 2 : 1)

const form = useForm({
    current_pin: '',
    pin: '',
    pin_confirmation: '',
    scanners: {},
})

const submit = () => {
    if (props.action === 'update') {
        form.put(route('pin.update', props.employee.id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => form.reset('pin_confirmation'),
            onError: () => {
                if (form.errors.current_pin) {
                    document.getElementById('current_pin').focus()
                } else if (form.errors.pin) {
                    document.getElementById('pin').focus()
                }
            },
            onSuccess: () => {
                form.reset('current_pin', 'pin', 'pin_confirmation')
            }
        })
    } else {
        form[props.action === 'setup' ? 'post' : 'delete'](route([props.action === 'setup' ? 'pin.store' : 'pin.delete'], props.employee.id), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => form.reset('pin_confirmation'),
            onError: () => {
                if (verifyError.value) {
                    to(1)
                } else if (form.errors.pin) {
                    to(2)
                }
            },
            onSuccess: () => {
                form.reset('pin', 'pin_confirmation')
            }
        })
    }
}

const to = (to, init = false) => {
    if (props.action !== 'update') {
        tab.value = to

        if (tab.value === 1) {
            nextTick(() => document.getElementById(`scanner_uid-${props.scanners[0]?.id}`).focus())
        } else if (tab.value === 2) {
            nextTick(() => document.getElementById('pin').focus())
        }
    } else {
        if (! init) {
            submit()

            return
        } else {
            document.getElementById('current_pin').focus()
        }
    }
}

const verifyError = computed(() => form.errors.scanners || props.scanners.some(scanner => form.errors[`scanners.${scanner.id}`]))

onMounted(() => {
    if (props.action === 'update') {
        props.scanners.forEach(e => form.scanners[e.id] = "")
    }

    router.reload({
        data: { action: props.action },
        onFinish: () => nextTick(() => to(tab.value, true))
    })
})
</script>

<template>
    <GuestLayout class="flex">
        <Head title="Setup your pin" />


        <div class="flex content-center flex-1">
            <div class="hero">
                <div
                    class="p-12 bg-gradient-to-bl via-50% via-base-100/80 to-100% to-base-200/80 from-base-300/80 rounded-none sm:rounded-sm"
                >
                    <div class="flex-col items-stretch lg:flex-row lg:gap-8">
                        <div class="flex flex-col items-center max-w-xl pt-6 pb-0 text-center align-middle lg:py-6 lg:flex-row lg:text-left">
                            <div class="space-y-6">
                                <h1 class="text-5xl font-bold capitalize">
                                    Pin&nbsp;{{ action }}
                                </h1>

                                <span class="py-6 font-mono text-sm text-primary-focus">
                                    {{ employee.name_format.fullStartLastInitialMiddle }} **
                                </span>

                                <p v-if="tab == 1" class="font-mono tracking-tight" style="text-wrap: balance;">
                                    For security purposes, you are required to enter all your registered biometric uids.
                                </p>

                                <p v-else-if="tab == 2" class="font-mono tracking-tight" style="text-wrap: balance;">
                                    Your new pin must be at minimum of four characters and must have a numeric character.
                                </p>

                                <p v-else class="font-mono tracking-tight" style="text-wrap: balance;">
                                    Please review all these first before proceeding to prevent account lockouts.
                                </p>
                            </div>
                        </div>

                        <div class="flex-shrink-0 w-full max-w-xl">
                            <div class="flex flex-col items-center gap-6 lg:flex-row">
                                <div v-if="action !== 'update'" class="flex-none block">
                                    <ul class="mb-3 steps steps-horizontal lg:steps-vertical">
                                        <li :class="{'step-primary': tab >= 1}" class="step">Verify</li>
                                        <li :class="{'step-primary': tab >= 2}" class="step">Setup</li>
                                        <li :class="{'step-primary': tab === 3}" class="step">Confirm</li>
                                    </ul>
                                </div>

                                <div class="w-full h-full rounded-sm card bg-gradient-to-br from-base-300/80">
                                    <div class="card-body">
                                        <form @submit.prevent="submit" class="space-y-3 max-w-md min-h-[190px]">
                                            <template v-if="tab === 1">
                                                <div :class="[scanners.length >= 6 ? 'grid-cols-3' : 'grid-cols-2']" class="grid gap-y-3 gap-x-4">
                                                    <div v-for="scanner in scanners" :class="{'col-span-2': scanners.length === 1}" class="form-control">
                                                        <label :for="`scanner_uid-${scanner.id}`" class="block text-sm font-medium uppercase text-base-content">
                                                            {{ scanner.name }}
                                                        </label>
                                                        <input
                                                            @keyup.ctrl.enter.exact="to(3)"
                                                            @keyup.enter.exact="to(tab+1)"
                                                            v-model="form.scanners[scanner.id]"
                                                            ref="pin"
                                                            :id="`scanner_uid-${scanner.id}`"
                                                            type="number"
                                                            class="mt-1 input input-bordered"
                                                            min="1"
                                                        />
                                                    </div>

                                                    <InputError :class="[scanners.length >= 6 ? 'col-span-3' : 'col-span-2']" :message="form.errors.scanners" />
                                                </div>
                                            </template>

                                            <template v-else-if="tab === 2">
                                                <template v-if="action === 'update'">
                                                    <div class="form-control">
                                                        <label for="current_pin" class="block text-sm font-medium uppercase text-base-content">
                                                            Current Pin
                                                        </label>
                                                        <input
                                                            @keyup.ctrl.enter.exact="to(3)"
                                                            @keyup.enter.exact="to(tab+1)"
                                                            v-model="form.current_pin"
                                                            ref="current_pin"
                                                            id="current_pin"
                                                            type="password"
                                                            class="mt-1 input input-bordered"
                                                        />
                                                        <InputError class="mt-0.5" :message="form.errors.current_pin" />
                                                    </div>
                                                </template>

                                                <div class="form-control">
                                                    <label for="pin" class="block text-sm font-medium uppercase text-base-content">
                                                        New Pin
                                                    </label>
                                                    <input
                                                        @keyup.ctrl.enter.exact="to(3)"
                                                        @keyup.enter.exact="to(tab+1)"
                                                        v-model="form.pin"
                                                        ref="pin"
                                                        id="pin"
                                                        type="password"
                                                        class="mt-1 input input-bordered"
                                                    />
                                                </div>
                                                <InputError v-if="action === 'update'" class="mt-0.5" :message="form.errors.pin" />

                                                <div class="form-control">
                                                    <label for="pin_confirmation" class="block text-sm font-medium uppercase text-base-content">
                                                        Confirm Pin
                                                    </label>
                                                    <input
                                                        @keyup.ctrl.enter.exact="to(3)"
                                                        @keyup.enter.exact="to(tab+1)"
                                                        v-model="form.pin_confirmation"
                                                        ref="pin_confirmation"
                                                        id="pin_confirmation"
                                                        type="password"
                                                        class="mt-1 input input-bordered"
                                                    />
                                                </div>
                                                <InputError v-if="action !== 'update'" class="mt-0.5" :message="form.errors.pin" />
                                            </template>

                                            <template v-else>
                                                <table class="flex-none w-full table-xs">
                                                    <tbody>
                                                        <tr>
                                                            <td class="w-1/3 px-0" >
                                                                PIN
                                                            </td>
                                                            <td :class="{'text-error': !form.pin} " class="font-mono">
                                                                {{ form.pin ? "•".repeat(form.pin.length) : '<blank>' }}
                                                            </td>
                                                        </tr>
                                                        <tr v-for="scanner in scanners">
                                                            <td class="w-1/3 px-0" >
                                                                {{ scanner.name }}
                                                            </td>
                                                            <td :class="{'text-error': ! form.scanners[scanner.id] }" class="font-mono">
                                                                {{ form.scanners[scanner.id] ? form.scanners[scanner.id] : '<blank>' }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </template>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-row justify-end gap-3 pt-3 form-control">
                                <template v-if="action !== 'update'">
                                    <button @click="to(tab-1)" type="button" class="btn" :disabled="tab <= 1">
                                        Back
                                    </button>

                                    <button @click="to(tab+1)" type="button" class="btn" :disabled="tab >= 3">
                                        Next
                                    </button>
                                </template>

                                <button @click="submit" type="button" class="btn btn-primary">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

input[type=number] {
  -moz-appearance: textfield;
  appearance: textfield;
}
</style>
