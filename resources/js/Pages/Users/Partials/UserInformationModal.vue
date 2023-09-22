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
    role: null,
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

const employeeForm = useForm({
    employee_id: null,
})

const q = ref('')

const r = ref({})

const s = ref({})

const deleteForm = useForm({
    password: null,
})

const forUpdate = computed(() => user.value !== null)

const profileFormLink = computed(() => forUpdate.value ? route('users.update', { id: user.value.id }) : route('users.store'))

const availableRoles = computed(() => {
    if (user.value?.role === -1) {
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
    } else if (tab.value === 'employee') {
        if (user.value.employee_id) {
            return
        }

        nextTick(() => document.getElementById('employee_search').focus())
    } else if (tab.value === 'delete') {
        nextTick(() => document.getElementById('delete_form_password').focus())
    }
}

const search = async (p = 1) => {
    nextTick(() => document.getElementById('employee_search').focus())

    if (q.value == "") {
        r.value = {}

        return
    }

    const headers = new Headers({"Accept": "application/json"});

    const requestOptions = {
        method: 'GET',
        headers: headers,
        redirect: 'follow'
    };

    r.value = await fetch(route('home', { search: q.value, page: p, all: true, paginate: 10 }), requestOptions)
        .then(response => response.json())
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

const link = () => {
    employeeForm.post(route('user.employee.link', { user: user.value.id }), {
        preserveScroll: true,
        preserveState: true,
        only: ['errors', 'users'],
        onSuccess: () => {
            user.value.employee_id = employeeForm.employee_id
            Object.assign(user.value.employee, s.value)

            employeeForm.reset()
            employeeForm.clearErrors()
        }
    })
}

const unlink = () => {
    employeeForm.delete(route('user.employee.unlink', { user: user.value.id }), {
        preserveScroll: true,
        preserveState: true,
        only: ['errors', 'users'],
        onSuccess: () => {
            user.value.employee_id = null
            user.employee = undefined

            nextTick(() => document.getElementById('employee_search').focus())
        }
    })
}

watch(() => employeeForm.employee_id, (id) => s.value = r.value.employees.data.find(e => e.id === id))

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

            q.value = ''
            r.value = {}
            s.value = {}
        }, 250)

        return
    }

    profileForm.name = user.value?.name
    profileForm.username = user.value?.username
    profileForm.title = user.value?.title
    profileForm.role = user.value?.role ?? 0
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
            <button v-if="forUpdate && user.role !== 2" @click="switchTab('employee')" class="tab tab-bordered" :class="{'tab-active': tab === 'employee', 'text-error': passwordForm.hasErrors}">Employee</button>
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
                    <input @keyup.enter="submit" v-model="profileForm.title" id="user_title" type="text" class="mt-1 input-sm input input-bordered" />
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
                        <label for="user_type" class="block text-sm font-medium text-base-content"> Role </label>
                        <select id="user_type" v-model="profileForm.role" class="mt-1 capitalize select select-sm select-bordered">
                            <option v-for="(value, name) in availableRoles" :value="value">{{ name.toLowerCase() }}</option>
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

                <div v-if="profileForm.role === 3" class="form-control">
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

        <div v-if="tab === 'employee' && user.role !== 2" class="space-y-2">
            <div v-if="! user.employee_id" class="form-control">
                <label for="employee_search" class="px-0 pt-0 label">
                    <span class="label-text">Search</span>

                    <div v-if="false" class="flex items-center mr-3 align-middle">
                        <svg class="w-4 h-4 fill-current stroke-current animate-spin" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                            <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                        </svg>
                    </div>
                </label>

                <div class="w-full mb-3 input-group input-group-sm">
                    <input
                        v-model="q"
                        id="employee_search"
                        type="search"
                        class="w-full input input-bordered input-sm"
                        @keyup.enter.exact="search"
                    />
                    <button @click="search" title="Search" class="btn btn-square btn-sm" :disabled="false">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </button>
                </div>

                <div class="flex flex-col gap-2 pr-3 overflow-y-scroll max-h-96" v-if="r.employees?.data?.length">
                    <label
                        v-for="employee in r.employees?.data" :key="employee.id"
                        class="flex flex-col w-full cursor-pointer label-text-alt rounded-[--rounded-box]"
                        :class="{'opacity-50': ! employee.active}"
                    >
                        <div class="flex">
                            <div class="pr-3 form-control">
                                <input
                                    v-model="employeeForm.employee_id"
                                    :id="`employee_profile-${employee.id}`"
                                    :value="employee.id"
                                    type="radio"
                                    class="radio radio-primary radio-sm"
                                    name="employee_profile"
                                >
                            </div>

                            <div>
                                {{ employee.name_format.full }}
                                <span class="font-mono text-sm lowercase opacity-70" :class="{'italic': ! employee.office}"> ({{ employee.office ? employee.office : 'no office set' }}) </span>
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <div v-else>
                <div class="font-mono tracking-tighter text-base-/50">
                    Currently linked to:
                </div>
                <label
                    class="flex flex-col w-full label-text-alt rounded-[--rounded-box]"
                >
                    <div class="flex">
                        <div>
                            {{ user.employee.name_format.full }}
                            <span class="font-mono text-sm lowercase opacity-70" :class="{'italic': ! user.employee.office}"> ({{ user.employee.office ? user.employee.office : 'no office set' }}) </span>
                        </div>
                    </div>
                </label>
            </div>
        </div>

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
            <template v-if="tab === 'employee'">
                <div>
                    <InputError class="mt-0.5" :message="employeeForm.errors.employee_id" />
                </div>

                <button
                    v-if="! user.employee_id"
                    type="button"
                    class="btn btn-sm"
                    @click="search(r.employees?.current_page - 1)"
                    :disabled="r.employees?.current_page <= 1"
                    :class="{hidden: r.employees?.data == null}"
                >
                    Prev
                </button>

                <button
                    v-if="! user.employee_id"
                    type="button"
                    class="btn btn-sm"
                    @click="search(r.employees?.current_page + 1)"
                    :disabled="r.employees?.current_page >= r.employees?.last_page"
                    :class="{hidden: r.employees?.data == null}"
                >
                    Next
                </button>

                <button v-if="user.employee_id" type="button" class="btn btn-sm btn-primary" @click="unlink">
                    Unlink
                </button>

                <button v-else type="button" class="btn btn-sm btn-primary" @click="link" :disabled="! employeeForm.employee_id">
                    Link
                </button>
            </template>

            <template v-if="tab === 'profile' || tab === 'password'">
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p
                        v-if="profileForm.recentlySuccessful || passwordForm.recentlySuccessful"
                        class="flex items-center justify-end flex-1 mr-3 text-sm opacity-50 text-base-content"
                    >
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
