<script setup>
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import { useForm } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'

const modelValue = defineModel()

const employee = defineModel('employee', {
    type: Object,
    default: null,
})

const tab = ref('info')

const informationForm = useForm({
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

const switchTab = (to) => tab.value = to

const forUpdate = computed(() => employee.value !== null )

const link = computed(() => forUpdate.value ? route('employees.update', { id: employee.value.id }) : route('employees.store'))

const submit = () => {
    informationForm
        .transform((d) => ({ ...d, ...(forUpdate.value ? {'id': employee.value.id, '_method': 'PUT'} : {})}))
        .post(link.value, {
            preserveScroll: true,
            preserveState: true,
        })
}

watch(employee, (employee) => {
    informationForm.name.last = employee?.name.last
    informationForm.name.first = employee?.name.first
    informationForm.name.middle = employee?.name.middle
    informationForm.name.extension = employee?.name.extension
    informationForm.office = employee?.office
    informationForm.groups = employee?.groups?.join(', ')
    informationForm.regular = employee?.regular ?? true
    informationForm.active = employee?.active ?? true
    informationForm.csc_format = employee?.csc_format ?? true
})



watch(modelValue, (show) => {
    if (! show) {
        setTimeout(() => {
            informationForm.reset()
            informationForm.clearErrors()
        }, 250)
    }
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            {{ employee ? `Update Employee` : 'Register Employee' }}
        </template>

        <div class="w-full mb-5 tabs">
            <button @click="switchTab('info')" class="tab tab-bordered" :class="{'tab-active': tab === 'info', 'text-error': informationForm.hasErrors}">Information</button>
            <button @click="switchTab('scanners')" class="tab tab-bordered" :class="{'tab-active': tab === 'scanners'}">Scanners</button>
            <button v-if="forUpdate" @click="switchTab('delete')" class="tab tab-bordered" :class="{'tab-active': tab === 'delete'}">Delete</button>
        </div>

        <div v-if="tab == 'info'" class="flex flex-col gap-2">
            <div class="grid grid-cols-2 gap-3.5">
                <div class="form-control">
                    <label for="employee_last_name" class="block text-sm font-medium text-base-content"> Last Name </label>
                    <input @keyup.enter="submit" v-model="informationForm.name.last" id="employee_last_name" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="informationForm.errors['name.last'] ?? informationForm.errors.name" />
                </div>

                <div class="form-control">
                    <label for="employee_first_name" class="block text-sm font-medium text-base-content"> First Name </label>
                    <input @keyup.enter="submit" v-model="informationForm.name.first" id="employee_first_name" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="informationForm.errors['name.first'] ?? informationForm.errors.name" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3.5">
                <div class="form-control">
                    <label for="employee_middle_name" class="block text-sm font-medium text-base-content"> Middle Name </label>
                    <input @keyup.enter="submit" v-model="informationForm.name.middle" id="employee_middle_name" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="informationForm.errors['name.middle'] ?? informationForm.errors.name" />
                </div>

                <div class="form-control">
                    <label for="employee_name_extension" class="block text-sm font-medium text-base-content"> Name Extension </label>
                    <input @keyup.enter="submit" v-model="informationForm.name.extension" id="employee_name_extension" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="informationForm.errors['name.extension'] ?? informationForm.errors.name" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3.5">
                <div class="form-control">
                    <label for="employee_office" class="block text-sm font-medium text-base-content"> Office </label>
                    <input @keyup.enter="submit" v-model="informationForm.office" id="employee_office" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                    <InputError class="mt-0.5" :message="informationForm.errors.office" />
                </div>

                <div class="form-control">
                    <label for="employee_regular" class="block text-sm font-medium text-base-content"> Status </label>
                    <select id="employee_regular" v-model="informationForm.regular" class="mt-1 uppercase select select-sm select-bordered">
                        <option :value="true">regular</option>
                        <option :value="false">JO, COS, etc.</option>
                    </select>
                    <InputError class="mt-0.5" :message="informationForm.errors.regular" />
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3.5">
                <div class="form-control">
                    <label for="employee_active" class="block text-sm font-medium text-base-content"> Active </label>
                    <select id="employee_active" v-model="informationForm.active" class="mt-1 uppercase select select-sm select-bordered">
                        <option :value="true">active</option>
                        <option :value="false">inactive</option>
                    </select>
                    <InputError class="mt-0.5" :message="informationForm.errors.active" />
                </div>

                <div class="form-control">
                    <label for="employee_print" class="block text-sm font-medium text-base-content"> Print </label>
                    <select id="employee_print" v-model="informationForm.csc_format" class="mt-1 uppercase select select-sm select-bordered">
                        <option :value="true">CSC form</option>
                        <option :value="false">default</option>
                    </select>
                    <InputError class="mt-0.5" :message="informationForm.errors.csc_format" />
                </div>
            </div>

            <div class="form-control">
                <label for="employee_groups" class="block text-sm font-medium text-base-content"> Groups </label>
                <input @keyup.enter="submit" v-model="informationForm.groups" id="employee_groups" type="text" class="mt-1 uppercase input-sm input input-bordered" />
                <InputError class="mt-0.5" :message="informationForm.errors.groups" />
            </div>
        </div>

        <template #action>
            <template v-if="tab !== 'delete'">
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="informationForm.recentlySuccessful" class="flex items-center justify-end flex-1 mr-3 text-sm opacity-50 text-base-content">
                        Success.
                    </p>
                </Transition>

                <button type="button" class="btn btn-sm btn-primary" @click="submit">Save</button>
            </template>
        </template>
    </Modal>
</template>
