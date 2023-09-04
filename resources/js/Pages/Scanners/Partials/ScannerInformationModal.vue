<script setup>
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed, nextTick, ref, watch } from 'vue'
import { differenceBy, mapKeys, mapValues, values } from 'lodash'

const modelValue = defineModel()

const scanner = defineModel('scanner', {
    type: Object,
    default: null,
})

const props = defineProps({
    users: Object,
})

const isAdmin = usePage().props.auth.user.administrator

const hasPrivilege = computed(() => isAdmin || scanner.value?.users.map(e => e.id).includes(usePage().props.auth.user.id))

const emits = defineEmits(['saved'])

const tab = ref('information')

const forUpdate = computed(() => scanner.value !== null )

const switchTab = (to) => {
    tab.value = to

    if (tab.value == 'delete') {
        nextTick(() => document.getElementById('delete_form_password').focus())
    } else if (tab.value == 'clear') {
        nextTick(() => document.getElementById('clear_form_password').focus())
    }
}

const informationForm = useForm({
    name: null,
    attlog_file: null,
    remarks: null,
    ip_address: null,
    port: null,
    driver: null,
    shared: false,
    priority: false,
    print_text_colour: '#000000',
    print_background_colour: '#ffffff',
})

const clearForm = useForm({
    timelogs: true,
    password: '',
})

const deleteForm = useForm({
    password: null,
})

const assigneeForm = useForm({
    scanner: null,
    password: null,
    users: null,
})

const newUser = ref({
    user: null,
})

const unassignedScanners = computed(() => differenceBy(props.users, values(assigneeForm.users), 'id'))

const allAssigneesRemoved = computed(() => Object.keys(assigneeForm.users ?? {}).length === 0 && scanner.value.users.length)

const assigneeFormNotEmpty = computed(() => Object.keys(assigneeForm.users ?? {}).length > 0)

const addUser = () => {
    const user = props.users?.find(e => e.id == newUser.value.user)

    assigneeForm.users[newUser.value.user] = {
        id: newUser.value.user,
        name: user.name,
        username: user.username,
        profile_photo_url: user.profile_photo_url,
        new: true,
    }

    newUser.value.user = null
}

const removeUser = (user) => {
    delete assigneeForm.users[user.id]
}

const informationFormLink = computed(() => forUpdate.value ? route('scanners.update', { id: scanner.value.id }) : route('scanners.store'))

const hasErrorsOnConnection = computed(() => informationForm.errors.ip_address || informationForm.errors.port || informationForm.errors.driver)

const hasErrorsOnInformation = computed(() => informationForm.errors.name || informationForm.errors.attlog_file || informationForm.errors.remarks || informationForm.errors.shared || informationForm.errors.priority || informationForm.errors.print_text_colour || informationForm.errors.print_background_colour)

const submit = () => {
    if (tab.value === 'information' || tab.value === 'connection') {
        informationForm
            .transform((d) => ({ ...d, ...(forUpdate.value ? { 'id': scanner.value.id, '_method': 'PUT' } : {}) }))
            .post(informationFormLink.value, {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => emits('saved')
            })
    } else if (tab.value === 'assignees') {
        assigneeForm
            .transform((d) => ({ ...d, users: values(d.users).map(e => e.id) }))
            .post(route('assignment.store'), {
                preserveScroll: true,
                preserveState: true,
                onError: () => {
                    assigneeForm.reset('password')

                    if (assigneeForm.errors.password) {
                        document.getElementById('scanner_form_password').focus()
                    }
                },
                onSuccess: () => {
                    assigneeForm.reset('password')
                    emits('saved')
                }
            })
    } else if (tab.value === 'clear') {
        clearForm.delete(route('scanners.destroy', scanner.value.id), {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                if (clearForm.errors.password) {
                    clearForm.reset('password')
                    document.getElementById('clear_form_password').focus()
                }
            },
            onSuccess: () => {
                clearForm.reset('password')
                document.getElementById('clear_form_password').focus()
            }
        })
    } else if (tab.value === 'delete') {
        deleteForm.delete(route('scanners.destroy', scanner.value.id), {
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
            }
        })
    }
}

watch(modelValue, (show) => {
    if (! show) {
        setTimeout(() => {
            informationForm.reset()
            informationForm.clearErrors()
            clearForm.reset()
            clearForm.clearErrors()
            deleteForm.reset()
            deleteForm.clearErrors()
            assigneeForm.reset()
            assigneeForm.clearErrors()

            tab.value = 'information'
        }, 250)

        return
    }

    informationForm.name = scanner.value?.name
    informationForm.attlog_file = scanner.value?.attlog_file
    informationForm.remarks = scanner.value?.remarks
    informationForm.ip_address = scanner.value?.ip_address
    informationForm.port = scanner.value?.port
    informationForm.driver = scanner.value?.driver
    informationForm.shared = scanner.value?.shared ?? false
    informationForm.priority = scanner.value?.priority ?? false
    informationForm.print_text_colour = scanner.value?.print_text_colour ?? '#000000'
    informationForm.print_background_colour = scanner.value?.print_background_colour ?? '#ffffff'

    assigneeForm.scanner = scanner.value?.id
    assigneeForm.users = scanner.value ? mapValues(mapKeys(scanner.value?.users, e => e.id), e => ({
        id: e.id,
        name: e.name,
        username: e.username,
        profile_photo_url: e.profile_photo_url,
    })) : null
})
</script>

<template>
    <Modal v-model="modelValue">
        <template #header>
            {{ scanner ? `Update ${scanner.name}` : 'New Scanner' }}
        </template>

        <div class="w-full mb-5 tabs">
            <button @click="switchTab('information')" class="px-2 tab tab-bordered" :class="{'tab-active': tab === 'information', 'text-error': hasErrorsOnInformation}">Information</button>
            <button @click="switchTab('connection')" class="px-2 tab tab-bordered" :class="{'tab-active': tab === 'connection', 'text-error': hasErrorsOnConnection}">Connection</button>
            <button v-if="forUpdate" @click="switchTab('assignees')" class="px-2 tab tab-bordered" :class="{'tab-active': tab === 'assignees'}">Assignees</button>
            <button v-if="forUpdate && isAdmin" @click="switchTab('clear')" class="px-2 tab tab-bordered" :class="{'tab-active': tab === 'clear'}">Timelogs</button>
            <button v-if="forUpdate && isAdmin" @click="switchTab('delete')" class="px-2 tab tab-bordered" :class="{'tab-active': tab === 'delete'}">Delete</button>
        </div>

        <div v-if="tab === 'information'" class="space-y-5">
            <div class="flex flex-col gap-2">
                <div class="form-control">
                    <label for="scanner_name" class="block text-sm font-medium text-base-content"> Name </label>
                    <input @keyup.enter="submit" v-model="informationForm.name" id="scanner_name" type="text" class="mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                    <InputError class="mt-0.5" :message="informationForm.errors.name" />
                </div>

                <div class="form-control">
                    <label for="scanner_attlog_file" class="block text-sm font-medium text-base-content"> Attlog File </label>
                    <input @keyup.enter="submit" v-model="informationForm.attlog_file" id="scanner_attlog_file" type="text" class="mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                    <InputError class="mt-0.5" :message="informationForm.errors.attlog_file" />
                </div>

                <div class="form-control">
                    <label for="scanner_remarks" class="block text-sm font-medium text-base-content"> Remarks </label>
                    <textarea @keyup.ctrl.enter.exact="submit" class="leading-tight textarea textarea-bordered" rows="2.5" :disabled="! hasPrivilege"> </textarea>
                    <InputError class="mt-0.5" :message="informationForm.errors.remarks" />
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div class="form-control">
                        <label for="scanner_shared" class="block text-sm font-medium text-base-content"> Shared </label>
                        <select id="scanner_shared" v-model="informationForm.shared" class="mt-1 uppercase select select-sm select-bordered" :disabled="! hasPrivilege">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                        <InputError class="mt-0.5" :message="informationForm.errors.shared" />
                    </div>

                    <div class="form-control">
                        <label for="scanner_priority" class="block text-sm font-medium text-base-content"> Priority </label>
                        <select id="scanner_priority" v-model="informationForm.priority" class="mt-1 uppercase select select-sm select-bordered" :disabled="! hasPrivilege">
                            <option :value="true">Yes</option>
                            <option :value="false">No</option>
                        </select>
                        <InputError class="mt-0.5" :message="informationForm.errors.priority" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3.5">
                    <div class="form-control">
                        <label for="scanner_print_text_colour" class="block text-sm font-medium text-base-content"> Print Text Colour </label>
                        <input @keyup.enter="submit" v-model="informationForm.print_text_colour" id="scanner_print_text_colour" type="color" class="w-full p-0 mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                        <InputError class="mt-0.5" :message="informationForm.errors.print_text_colour" />
                    </div>

                    <div class="form-control">
                        <label for="scanner_print_background_colour" class="block text-sm font-medium text-base-content"> Print Background Colour </label>
                        <input @keyup.enter="submit" v-model="informationForm.print_background_colour" id="scanner_print_background_colour" type="color" class="w-full p-0 mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                        <InputError class="mt-0.5" :message="informationForm.errors.print_background_colour" />
                    </div>
                </div>
            </div>

            <div class="space-y-1">
                <span class="block text-xs tracking-tight text-base-content/70">
                    Setting the attlog file restricts uploads to only accept files that match the specified name,
                    thereby preventing accidental uploads of attlogs to incorrect scanners.
                </span>

                <span class="block text-xs tracking-tight text-base-content/70">
                    All users, whether administrators or not, will have access to shared scanners.
                </span>

                <span class="block text-xs tracking-tight text-base-content/70">
                    Priority scanners will have precedence in dtr regardless of the time.
                </span>
            </div>
        </div>

        <div v-if="tab === 'connection'" class="space-y-5">
            <div class="flex flex-col gap-2">
                <div class="grid grid-cols-2 gap-3.5">
                    <div class="form-control">
                        <label for="scanner_ip_address" class="block text-sm font-medium text-base-content"> Ip Address </label>
                        <input @keyup.enter="submit" v-model="informationForm.ip_address" id="scanner_ip_address" type="text" class="mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                        <InputError class="mt-0.5" :message="informationForm.errors.ip_address" />
                    </div>

                    <div class="form-control">
                        <label for="scanner_port" class="block text-sm font-medium text-base-content"> Port </label>
                        <input @keyup.enter="submit" v-model="informationForm.port" id="scanner_port" type="text" class="mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                        <InputError class="mt-0.5" :message="informationForm.errors.port" />
                    </div>
                </div>

                <div class="form-control">
                    <label for="scanner_driver" class="block text-sm font-medium text-base-content"> Driver </label>
                    <select id="scanner_driver" v-model="informationForm.driver" class="mt-1 select select-sm select-bordered" :disabled="! hasPrivilege">
                        <option :value="null"></option>
                        <option value="zakzk">ZakZk</option>
                        <option value="tadphp">TadPhp</option>
                    </select>
                    <InputError class="mt-0.5" :message="informationForm.errors.driver" />
                </div>
            </div>

            <span v-if="informationForm.driver === 'zakzk'" class="block text-xs tracking-tight text-base-content/70">
                ZakZK driver needs an external service setup. Please contact administrators for help.
            </span>
        </div>

        <div v-if="tab == 'assignees' && forUpdate" class="space-y-5">
            <template v-if="isAdmin">
                <div class="space-y-2">
                    <div class="grid items-end grid-cols-12 gap-3">
                        <div class="col-span-10 form-control">
                            <label for="register_scanners" class="block text-sm font-medium text-base-content"> New Assignee </label>
                            <select v-model="newUser.user" :disabled="! hasPrivilege" id="register_scanners" class="mt-1 uppercase select select-sm select-bordered">
                                <option :value="null" hidden selected>Select</option>
                                <option v-for="user in unassignedScanners" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                        </div>

                        <div class="col-span-2 form-control">
                            <button @click="addUser" :disabled="! newUser.user" class="btn btn-sm btn-primary">
                                Add
                            </button>
                        </div>
                    </div>

                    <InputError v-if="assigneeForm.errors.users" message="Please add some users first." />
                </div>

                <hr class="border-base-content/40">
            </template>

            <div class="space-y-2">
                <template v-if="assigneeFormNotEmpty">
                    <div class="grid gap-3 px-2 pb-2 pl-1 overflow-x-hidden overflow-y-auto max-h-40">
                        <div v-for="user in assigneeForm.users">
                            <div>
                                <div class="flex items-center group">
                                    <img :src="user.profile_photo_url" alt="" class="flex-none w-10 h-10 rounded-full">
                                    <div class="flex-auto ml-4 space-y-0 overflow-x-hidden whitespace-nowrap">
                                        <div class="font-medium">
                                            {{ user.name }}
                                            <sup v-if="user.new" class="p-0 tracking-tighter">*</sup>
                                        </div>
                                        <div class="font-mono text-xs tracking-tighter text-base-content/50">@{{user.username}}</div>
                                    </div>
                                    <button @click="removeUser(user)" class="hidden btn-xs btn btn-error" :class="{'group-hover:inline': isAdmin}">
                                        Remove
                                    </button>
                                </div>
                                <InputError class="mt-0.5 tracking-tighter" :message="assigneeForm.errors[`users.${user.id}.uid`]" />
                            </div>
                        </div>
                    </div>
                </template>

                <span class="block text-sm tracking-tight text-base-content/70" v-if="allAssigneesRemoved">
                    All scanner assignees are to be removed.
                </span>

                <span class="block text-sm tracking-tight text-base-content/70" v-if="! assigneeFormNotEmpty && ! allAssigneesRemoved">
                    Scanner has no assigned users.
                </span>
            </div>

            <template v-if="isAdmin">
                <hr class="border-base-content/40">

                <div class="space-y-2">
                    <div class="form-control">
                        <label for="scanner_form_password" class="block text-sm font-medium text-base-content"> Password </label>
                        <input @keyup.enter="submit" v-model="assigneeForm.password" id="scanner_form_password" type="password" class="mt-1 input-sm input input-bordered" :disabled="! isAdmin" />
                        <InputError class="mt-0.5" :message="assigneeForm.errors.password" />
                    </div>

                    <span class="block text-xs tracking-tight text-base-content/70">
                        To prevent any unauthorized modifications, please enter your password to proceed.
                    </span>
                </div>
            </template>
        </div>

        <div v-if="tab == 'clear' && forUpdate" class="space-y-2">
            <div class="form-control">
                <label for="clear_form_password" class="block text-sm font-medium text-base-content"> Password </label>
                <input @keyup.enter="submit" v-model="clearForm.password" id="clear_form_password" type="password" class="mt-1 input-sm input input-bordered" />
                <InputError class="mt-0.5" :message="clearForm.errors.password" />
            </div>

            <span class="block text-xs tracking-tight text-base-content/70">
                To prevent any unauthorized modifications, please enter your password to proceed.
            </span>
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
            <template v-if="tab == 'delete' || tab == 'clear'">
                <p v-if="clearForm.recentlySuccessful" class="flex items-center justify-end flex-1 mr-3 text-sm opacity-50 text-base-content">
                    Success.
                </p>

                <button type="button" class="btn btn-sm btn-error" @click="submit">
                    Delete
                </button>
            </template>


            <template v-else>
                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="informationForm.recentlySuccessful || assigneeForm.recentlySuccessful" class="flex items-center justify-end flex-1 mr-3 text-sm opacity-50 text-base-content">
                        Success.
                    </p>
                </Transition>

                <button v-if="tab !== 'assignees'" type="button" class="btn btn-sm btn-primary" @click="submit" :disabled="! hasPrivilege">Save</button>

                <button v-else type="button" class="btn btn-sm btn-primary" @click="submit" :disabled="! isAdmin">Save</button>
            </template>
        </template>
    </Modal>
</template>
