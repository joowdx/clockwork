<script setup>
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'
import { differenceBy, mapKeys, mapValues, values } from 'lodash'

const modelValue = defineModel()

const employee = defineModel('employee', {
    type: Object,
    default: null,
})

const props = defineProps({
    scanners: Object,
    options: Object,
})

const emits = defineEmits(['saved'])

const tab = ref('profile')

const profileForm = useForm({
    name: {
        first: null,
        middle: null,
        last: null,
        extension: null,
    },
    office: null,
    groups: null,
    regular: null,
    active: null,
    csc_format: null,
})

const scannerForm = useForm({
    password: null,
    employee: null,
    scanners: null,
})

const deleteForm = useForm({
    password: null,
})

const registerScanner = ref({
    scanner: null,
    uid: null,
})

const unregisteredScanners = computed(() => differenceBy(props.scanners, values(scannerForm.scanners), 'id'))

const addScanner = () => {
    scannerForm.scanners[registerScanner.value.scanner] = {
        id: registerScanner.value.scanner,
        name: props.scanners?.find(e => e.id == registerScanner.value.scanner).name,
        uid: registerScanner.value.uid,
        enabled: true,
        new: true,
    }

    registerScanner.value.scanner = null
    registerScanner.value.uid = null
}

const notAssigned = (scanner) => {
    if (usePage().props.auth.user?.administrator) {
        return false
    }

    return ! props.scanners?.some(e => e.id === scanner.id) || scanner.shared
}

const switchTab = (to) => {
    tab.value = to

    if (tab.value == 'delete') {
        nextTick(() => document.getElementById('delete_form_password').focus())
    }
}

const forUpdate = computed(() => employee.value !== null )

const profileFormLink = computed(() => forUpdate.value ? route('employees.update', { id: employee.value.id }) : route('employees.store'))

const submit = () => {
    if (tab.value === 'profile') {
        profileForm
            .transform((d) => ({ ...d, ...(forUpdate.value ? {'id': employee.value.id, '_method': 'PUT'} : {})}))
            .post(profileFormLink.value, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => emits('saved'),
                ...(props.options ?? {})
            })
    } else if (tab.value === 'scanners') {
        scannerForm
            .transform((d) => ({...d, scanners: mapValues(d.scanners, e => ({ uid: e.uid, enabled: e.enabled }))}))
            .post(route('enrollment.store'), {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    if (scannerForm.errors.password) {
                        scannerForm.reset('password')
                        document.getElementById('scanner_form_password').focus()
                    }
                },
                onSuccess: () => {
                    scannerForm.reset('password')
                    emits('saved')
                },
                ...(props.options ?? {})
            })
    } else if (tab.value === 'delete') {
        deleteForm.delete(route('employees.destroy', employee.value.id), {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                if (deleteForm.errors.password) {
                    deleteForm.reset('password')
                    document.getElementById('delete_form_password').focus()
                }
            },
            onSuccess: () => {
                deleteForm.reset('password')
                emits('saved')
                modelValue.value = false
            },
            ...(props.options ?? {})
        })
    }

}

watch(modelValue, (show) => {
    if (! show) {
        setTimeout(() => {
            profileForm.reset()
            profileForm.clearErrors()
            scannerForm.reset()
            scannerForm.clearErrors()
            deleteForm.reset()
            deleteForm.clearErrors()

            tab.value = 'profile'
        }, 250)

        return
    }

    profileForm.name.last = employee.value?.name.last
    profileForm.name.first = employee.value?.name.first
    profileForm.name.middle = employee.value?.name.middle
    profileForm.name.extension = employee.value?.name.extension
    profileForm.office = employee.value?.office
    profileForm.groups = employee.value?.groups?.join(', ')
    profileForm.regular = employee.value?.regular ?? true
    profileForm.active = employee.value?.active ?? true
    profileForm.csc_format = employee.value?.csc_format ?? true

    scannerForm.employee = employee.value?.id
    scannerForm.scanners = employee.value ? mapValues(mapKeys(employee.value?.scanners, e => e.id), e => ({
        id: e.id,
        name: e.name,
        uid: e.pivot.uid,
        enabled: e.pivot.enabled
    })) : null
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            {{ employee ? `Update ${employee.name_format.shortStartLastInitialFirst}` : 'Register Employee' }}
        </template>

        <div class="w-full mb-5 tabs">
            <button @click="switchTab('profile')" class="tab tab-bordered" :class="{'tab-active': tab === 'profile', 'text-error': profileForm.hasErrors}">Profile</button>
            <button v-if="forUpdate" @click="switchTab('scanners')" class="tab tab-bordered" :class="{'tab-active': tab === 'scanners', 'text-error': scannerForm.hasErrors}">Scanners</button>
            <button v-if="forUpdate" @click="switchTab('delete')" class="tab tab-bordered" :class="{'tab-active': tab === 'delete', 'text-error': deleteForm.hasErrors}">Delete</button>
        </div>

        <div v-if="tab == 'profile'" class="space-y-5">
            <div class="flex flex-col gap-2">
                <div class="grid grid-cols-2 gap-3.5">
                    <div class="form-control">
                        <label for="employee_last_name" class="block text-sm font-medium text-base-content"> Last Name </label>
                        <input @keyup.enter="submit" v-model="profileForm.name.last" id="employee_last_name" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                        <InputError class="mt-0.5" :message="profileForm.errors['name.last'] ?? profileForm.errors.name" />
                    </div>

                    <div class="form-control">
                        <label for="employee_first_name" class="block text-sm font-medium text-base-content"> First Name </label>
                        <input @keyup.enter="submit" v-model="profileForm.name.first" id="employee_first_name" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                        <InputError class="mt-0.5" :message="profileForm.errors['name.first'] ?? profileForm.errors.name" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div class="form-control">
                        <label for="employee_middle_name" class="block text-sm font-medium text-base-content"> Middle Name </label>
                        <input @keyup.enter="submit" v-model="profileForm.name.middle" id="employee_middle_name" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                        <InputError class="mt-0.5" :message="profileForm.errors['name.middle'] ?? profileForm.errors.name" />
                    </div>

                    <div class="form-control">
                        <label for="employee_name_extension" class="block text-sm font-medium text-base-content"> Name Extension </label>
                        <input @keyup.enter="submit" v-model="profileForm.name.extension" id="employee_name_extension" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                        <InputError class="mt-0.5" :message="profileForm.errors['name.extension'] ?? profileForm.errors.name" />
                    </div>
                </div>

                <div class="form-control">
                    <label for="employee_office" class="block text-sm font-medium text-base-content"> Office </label>
                    <input @keyup.enter="submit" v-model="profileForm.office" id="employee_office" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="profileForm.errors.office" />
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div class="form-control">
                        <label for="employee_regular" class="block text-sm font-medium text-base-content"> Status </label>
                        <select id="employee_regular" v-model="profileForm.regular" class="mt-1 uppercase select select-sm select-bordered">
                            <option :value="true">regular</option>
                            <option :value="false">JO, COS, etc.</option>
                        </select>
                        <InputError class="mt-0.5" :message="profileForm.errors.regular" />
                    </div>

                    <div class="form-control">
                        <label for="employee_active" class="block text-sm font-medium text-base-content"> Active </label>
                        <select id="employee_active" v-model="profileForm.active" class="mt-1 uppercase select select-sm select-bordered">
                            <option :value="true">active</option>
                            <option :value="false">inactive</option>
                        </select>
                        <InputError class="mt-0.5" :message="profileForm.errors.active" />
                    </div>
                </div>

                <div class="form-control">
                    <label for="employee_groups" class="block text-sm font-medium text-base-content"> Groups </label>
                    <input @keyup.enter="submit" v-model="profileForm.groups" id="employee_groups" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="profileForm.errors.groups" />
                </div>
            </div>

            <div class="space-y-1">
                <span class="block text-xs tracking-tight text-base-content/70">
                    Inactive employees will be hidden in view unless set.
                </span>

                <span class="block text-xs tracking-tight text-base-content/70">
                    Option set in Print is chosen when print mode is set to preferred.
                </span>

                <span class="block text-xs tracking-tight text-base-content/70">
                    Groups are separated by a comma.
                </span>
            </div>
        </div>

        <div v-if="tab == 'scanners' && forUpdate" class="space-y-5">
            <div class="space-y-2">
                <div class="grid items-end grid-cols-12 gap-3">
                    <div class="col-span-5 form-control">
                        <label for="register_scanners" class="block text-sm font-medium text-base-content"> Register Scanner </label>
                        <select v-model="registerScanner.scanner" id="register_scanners" class="mt-1 uppercase select select-sm select-bordered">
                            <option :value="null" hidden selected>Select</option>
                            <option v-if="unregisteredScanners.length === 0" :value="undefined" disabled>empty</option>
                            <option v-for="scanner in unregisteredScanners" :value="scanner.id">{{ scanner.name }}</option>
                        </select>
                    </div>

                    <div class="col-span-5 form-control">
                        <label for="register_scanner_uid" class="block text-sm font-medium text-base-content"> Uid </label>
                        <input v-model="registerScanner.uid" @keyup.enter="addScanner" id="register_scanner_uid" type="text" class="mt-1 uppercase input-sm input input-bordered" placeholder="####" />
                    </div>

                    <div class="col-span-2 form-control">
                        <button @click="addScanner" :disabled="! registerScanner.scanner || ! registerScanner.uid" class="btn btn-sm btn-primary">
                            Add
                        </button>
                    </div>
                </div>

                <span class="block text-xs tracking-tight text-base-content/70">
                    Newly added entries marked with asterisks are not yet saved.
                </span>

                <InputError v-if="scannerForm.errors.scanners" message="Please add some scanners first." />
            </div>

            <hr class="border-base-content/40">

            <div class="space-y-2">
                <div v-if="employee.scanners.length > 1" class="grid grid-cols-2 px-2 pb-2 pl-1 overflow-x-auto overflow-y-scroll gap-x-3 gap-y-2 max-h-40">
                    <div v-for="scanner in scannerForm.scanners" class="form-control">
                        <label :for="`scanner_form-${scanner.id}`" class="flex items-center justify-between text-sm font-medium text-base-content">
                            <span class="tracking-tighter lowercase">
                                {{ scanner.name }}
                                <sup v-if="scanner.new" class="p-0 tracking-tighter">*</sup>
                            </span>

                            <input v-model="scannerForm.scanners[scanner.id].enabled" :disabled="notAssigned(scanner)" class="toggle toggle-xs" type="checkbox">
                        </label>
                        <input @keyup.enter="submit" v-model="scannerForm.scanners[scanner.id].uid" :readonly="notAssigned(scanner)" :id="`scanner_form-${scanner.id}`" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                        <InputError class="mt-0.5 tracking-tighter" :message="scannerForm.errors[`scanners.${scanner.id}.uid`]" />
                    </div>
                </div>

                <span class="block text-sm tracking-tight text-base-content/70" v-if="employee.scanners.length === 0">
                    Employee is not registered to any scanner device.
                </span>

                <template v-else>
                    <span class="block mt-3 text-xs tracking-tight text-base-content/70">
                        Untoggling will disable the respective scanners' data from being generated in their reports.
                        To remove registration, save an empty value.
                    </span>

                    <span class="block text-xs tracking-tight text-base-content/70">
                        You can't edit employees' registration for scanners you are not assigned to.
                    </span>
                </template>
            </div>

            <hr class="border-base-content/40">

            <div class="space-y-2">
                <div class="form-control">
                    <label for="scanner_form_password" class="block text-sm font-medium text-base-content"> Password </label>
                    <input @keyup.enter="submit" v-model="scannerForm.password" id="scanner_form_password" type="password" class="mt-1 input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="scannerForm.errors.password" />
                </div>

                <span class="block text-xs tracking-tight text-base-content/70">
                    To prevent any unauthorized modifications, please enter your password to proceed.
                </span>
            </div>
        </div>

        <div v-if="tab == 'delete' && forUpdate" class="space-y-2">
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
            <template v-if="tab !== 'delete'">
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="profileForm.recentlySuccessful || scannerForm.recentlySuccessful" class="flex items-center justify-end flex-1 mr-3 text-sm opacity-50 text-base-content">
                        Success.
                    </p>
                </Transition>

                <button type="button" class="btn btn-sm btn-primary" @click="submit">Save</button>
            </template>


            <button v-else type="button" class="btn btn-sm btn-error" @click="submit">
                Delete
            </button>
        </template>
    </Modal>
</template>
