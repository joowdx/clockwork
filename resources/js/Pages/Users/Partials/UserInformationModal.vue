<script setup>
import InputError from '@/Components/InputError.vue'
import Modal from '@/Components/Modal.vue'
import { useForm } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'

const modelValue = defineModel()

const user = defineModel('user')

const props = defineProps({
    types: Object,
})

const emits = defineEmits(['saved'])

const tab = ref('profile')

const profileForm = useForm({
    name: null,
    title: null,
    type: null,
    username: null,
    password: null,
    password_confirmation: null,
    disabled: null,
    offices: [],
})

const passwordForm = useForm({
    password: null,
    password_confirmation : null,
})

const twoFaForm = useForm({})

const deleteForm = useForm({
    password: null,
})

const forUpdate = computed(() => user.value !== null)

const profileFormLink = computed(() => forUpdate.value ? route('users.update', { id: user.value.id }) : route('users.store'))

const availableTypes = computed(() => {
    if (user.value?.type === -1) {
        return {...props.types, ADMINISTRATOR: -1}
    }

    return props.types
})

const switchTab = (to) => {
    tab.value = to

    if (tab.value === 'profile') {
        nextTick(() => document.getElementById('user_name').focus())
    } else if (tab.value === 'password') {
        nextTick(() => document.getElementById('user_password').focus())
    } else if (tab.value === 'delete') {
        nextTick(() => document.getElementById('delete_form_password').focus())
    }
}

const submit = () => {
    if (tab.value === 'profile') {
        profileForm
            .transform((d) => ({ ...d, ...(forUpdate.value ? {'id': user.value.id, '_method': 'PUT'} : {})}))
            .post(profileFormLink.value, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => emits('saved')
            })
    } else if (tab.value === 'password') {
        passwordForm.put(profileFormLink.value, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => passwordForm.reset()
        })
    } else if (tab.value === 'delete') {
        deleteForm.delete(route('users.destroy', user.value.id), {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                if (deleteForm.errors.password) {
                    deleteForm.reset('password')
                    document.getElementById('delete_form_password').focus()
                }
            },
            onSuccess: () => {
                modelValue.value = false
                deleteForm.reset()
            }
        })
    }
}

watch(modelValue, (show) => {
    if (!show) {
        setTimeout(() => {
            profileForm.reset()
            profileForm.clearErrors()
            passwordForm.reset()
            passwordForm.clearErrors()
            deleteForm.reset()
            deleteForm.clearErrors()

            tab.value = 'profile'
        }, 250)

        return
    }

    profileForm.name = user.value?.name
    profileForm.username = user.value?.username
    profileForm.title = user.value?.title
    profileForm.type = user.value?.type ?? 0
    profileForm.disabled = user.value?.disabled ?? false
    profileForm.offices = user.value?.offices?.join(", ")

    nextTick(() => document.getElementById('user_name').focus())
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            {{ user ? `Update @${user.username}` : 'Register User' }}
        </template>

        <div class="w-full mb-5 tabs">
            <button @click="switchTab('profile')" class="tab tab-bordered" :class="{'tab-active': tab === 'profile', 'text-error': profileForm.hasErrors}">Profile</button>
            <button v-if="forUpdate" @click="switchTab('password')" class="tab tab-bordered" :class="{'tab-active': tab === 'password', 'text-error': passwordForm.hasErrors}">Password</button>
            <button v-if="forUpdate" @click="switchTab('delete')" class="tab tab-bordered" :class="{'tab-active': tab === 'delete', 'text-error': deleteForm.hasErrors}">Delete</button>
        </div>

        <template v-if="tab === 'profile'">
            <div class="flex flex-col gap-2">
                <div class="form-control">
                    <label for="user_name" class="block text-sm font-medium text-base-content"> Name </label>
                    <input @keyup.enter="submit" v-model="profileForm.name" id="user_name" type="text" class="mt-1 capitalize input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="profileForm.errors.name" />
                </div>

                <div class="form-control">
                    <label for="user_title" class="block text-sm font-medium text-base-content"> Title </label>
                    <input @keyup.enter="submit" v-model="profileForm.title" id="user_title" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="profileForm.errors.title" />
                </div>

                <template v-if="! forUpdate">
                    <div class="form-control">
                        <label for="user_username" class="block text-sm font-medium text-base-content"> Username </label>
                        <input @keyup.enter="submit" v-model="profileForm.username" id="user_username" :disabled="forUpdate" type="text" class="mt-1 lowercase input-sm input input-bordered" />
                        <InputError class="mt-0.5" :message="profileForm.errors.username" />
                    </div>

                    <div class="grid grid-cols-2 gap-3.5">
                        <div class="form-control">
                            <label for="user_password" class="block text-sm font-medium text-base-content"> Password </label>
                            <input @keyup.enter="submit" v-model="profileForm.password" id="user_password" type="password" class="mt-1 input-sm input input-bordered" />
                            <InputError class="mt-0.5" :message="profileForm.errors.password" />
                        </div>

                        <div class="form-control">
                            <label for="user_password_confirmation " class="block text-sm font-medium text-base-content"> Confirm Password </label>
                            <input @keyup.enter="submit" v-model="profileForm.password_confirmation " id="user_password_confirmation " type="password" class="mt-1 capitalize input-sm input input-bordered" />
                            <InputError class="mt-0.5" :message="profileForm.errors.password_confirmation " />
                        </div>
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-3.5">
                    <div class="form-control">
                        <label for="user_type" class="block text-sm font-medium text-base-content"> Type </label>
                        <select id="user_type" v-model="profileForm.type" class="mt-1 capitalize select select-sm select-bordered">
                            <option v-for="(value, name) in availableTypes" :value="value">{{ name.toLowerCase() }}</option>
                        </select>
                        <InputError class="mt-0.5" :message="profileForm.errors.regular" />
                    </div>

                    <div class="form-control">
                        <label for="user_disabled" class="block text-sm font-medium text-base-content"> Disabled </label>
                        <select id="user_disabled" v-model="profileForm.disabled" class="mt-1 capitalize select select-sm select-bordered">
                            <option :value="true">True</option>
                            <option :value="false">False</option>
                        </select>
                        <InputError class="mt-0.5" :message="profileForm.errors.regular" />
                    </div>
                </div>

                <div v-if="profileForm.type === 3" class="form-control">
                    <label for="user_offices" class="block text-sm font-medium text-base-content"> Offices </label>
                    <input @keyup.enter="submit" v-model="profileForm.offices" id="user_offices" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="profileForm.errors.offices" />
                </div>
            </div>
        </template>

        <template v-if="tab === 'password'">
            <div class="flex flex-col gap-2">
                <div class="form-control">
                    <label for="user_password" class="block text-sm font-medium text-base-content"> New Password </label>
                    <input @keyup.enter="submit" v-model="passwordForm.password" id="user_password" type="password" class="mt-1 lowercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="passwordForm.errors.password" />
                </div>

                <div class="form-control">
                    <label for="user_password_confirmation " class="block text-sm font-medium text-base-content"> Confirm Password </label>
                    <input @keyup.enter="submit" v-model="passwordForm.password_confirmation " id="user_password_confirmation " type="password" class="mt-1 capitalize input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="passwordForm.errors.password_confirmation " />
                </div>
            </div>
        </template>

        <div v-if="tab === 'delete'" class="space-y-2">
            <div class="form-control">
                <label for="delete_form_password" class="block text-sm font-medium text-base-content"> Password </label>
                <input @keyup.enter="submit" v-model="deleteForm.password" id="delete_form_password" type="password" class="mt-1 input-sm input input-bordered" />
                <InputError class="mt-0.5" :message="deleteForm.errors.password" />
            </div>

            <span class="block text-xs tracking-tight text-base-content/70">
                To prevent any unauthorized modifications, please enter your password to proceed.
            </span>
        </div>

        <template #action>
            <template v-if="tab === 'profile' || tab === 'password'">
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="profileForm.recentlySuccessful || passwordForm.recentlySuccessful" class="flex items-center justify-end flex-1 mr-3 text-sm opacity-50 text-base-content">
                        Success.
                    </p>
                </Transition>

                <button type="button" class="btn btn-sm btn-primary" @click="submit">Save</button>
            </template>

            <button v-else-if="tab === 'delete'" type="button" class="btn btn-sm btn-error" @click="submit">
                Delete
            </button>
        </template>
    </Modal>
</template>
