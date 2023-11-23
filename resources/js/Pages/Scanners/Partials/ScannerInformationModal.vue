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
    }
}

const informationForm = useForm({
    name: null,
    attlog_file: null,
    remarks: null,
    ip_address: null,
    port: null,
    password: null,
    shared: false,
    priority: false,
    print_text_colour: '#000000',
    print_background_colour: '#ffffff',
})

const downloadForm = useForm({})

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

const hasErrorsOnConnection = computed(() => informationForm.errors.ip_address || informationForm.errors.port || informationForm.errors.password)

const hasErrorsOnInformation = computed(() => informationForm.errors.name || informationForm.errors.attlog_file || informationForm.errors.remarks || informationForm.errors.shared || informationForm.errors.priority || informationForm.errors.print_text_colour || informationForm.errors.print_background_colour)

const download = () => {
    downloadForm.post(route('scanners.download', scanner.value.id), {
        preserveScroll: true,
        preserveState: true,
        onBefore: () => downloadForm.clearErrors(),
    })
}

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
            downloadForm.reset()
            downloadForm.clearErrors()
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
    informationForm.password = scanner.value?.password
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
            <button
                @click="switchTab('information')"
                class="px-2 tab tab-bordered"
                :class="{'tab-active': tab === 'information', 'text-error': hasErrorsOnInformation}"
            >
                Information
            </button>
            <button
                @click="switchTab('connection')"
                class="px-2 tab tab-bordered"
                :class="{'tab-active': tab === 'connection', 'text-error': hasErrorsOnConnection}"
            >
                Connection
            </button>
            <button
                v-if="forUpdate"
                @click="switchTab('assignees')"
                class="px-2 tab tab-bordered"
                :class="{'tab-active': tab === 'assignees'}"
            >
                Assignees
            </button>
            <button
                v-if="forUpdate && hasPrivilege"
                @click="switchTab('clear')"
                class="px-2 tab tab-bordered"
                :class="{'tab-active': tab === 'clear'}"
            >
                Timelogs
            </button>
            <button
                v-if="forUpdate && isAdmin"
                @click="switchTab('delete')"
                class="px-2 tab tab-bordered"
                :class="{'tab-active': tab === 'delete'}"
            >
                Delete
            </button>
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
                    <input @keyup.enter="submit" v-model="informationForm.attlog_file" id="scanner_attlog_file" type="text" class="mt-1 input-sm input input-bordered" :disabled="! hasPrivilege" />
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
                <div class="form-control">
                    <div class="form-control">
                        <label for="scanner_ip_address" class="block text-sm font-medium text-base-content"> Ip Address </label>
                        <input @keyup.enter="submit" v-model="informationForm.ip_address" id="scanner_ip_address" type="text" class="mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                        <InputError class="mt-0.5" :message="informationForm.errors.ip_address" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="form-control">
                        <label for="scanner_port" class="block text-sm font-medium text-base-content"> Port </label>
                        <input @keyup.enter="submit" v-model="informationForm.port" id="scanner_port" type="text" class="mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                        <InputError class="mt-0.5" :message="informationForm.errors.port" />
                    </div>

                    <div class="form-control">
                        <label for="scanner_password" class="block text-sm font-medium text-base-content"> Password </label>
                        <input @keyup.enter="submit" v-model="informationForm.password" id="scanner_password" type="text" class="mt-1 uppercase input-sm input input-bordered" :disabled="! hasPrivilege" />
                        <InputError class="mt-0.5" :message="informationForm.errors.password" />
                    </div>
                </div>
            </div>
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

        <template v-if="tab == 'clear' && forUpdate">
            <div class="space-y-2">
                    <div class="space-y-2 form-control">
                        <label for="clear_form_password" class="flex gap-3 text-sm font-medium text-base-content">

                            Synchronize Timelogs

                            <svg :class="{'hidden': (! downloadForm.processing && ! downloadForm.recentlySuccessful)}" class="w-4 h-4 fill-current animate-spin" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                <path d="M142.9 142.9c62.2-62.2 162.7-62.5 225.3-1L327 183c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8H463.5c0 0 0 0 0 0H472c13.3 0 24-10.7 24-24V72c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2L413.4 96.6c-87.6-86.5-228.7-86.2-315.8 1C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5c7.7-21.8 20.2-42.3 37.8-59.8zM16 312v7.6 .7V440c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l41.6-41.6c87.6 86.5 228.7 86.2 315.8-1c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.2 62.2-162.7 62.5-225.3 1L185 329c6.9-6.9 8.9-17.2 5.2-26.2s-12.5-14.8-22.2-14.8H48.4h-.7H40c-13.3 0-24 10.7-24 24z"/>
                            </svg>
                        </label>

                        <div class="flex">
                            <button @click="download" class="btn btn-primary btn-sm w-fit" :disabled="downloadForm.processing || downloadForm.recentlySuccessful">
                                Sync Now
                            </button>

                            <p v-if="downloadForm.processing || downloadForm.recentlySuccessful" class="flex items-center ml-3 text-sm opacity-50 text-base-content">
                                synchronizing...
                            </p>
                        </div>

                        <InputError class="mt-0.5" :message="downloadForm.errors.message" />

                        <span class="block text-xs text-base-content/70">
                            Download all timelogs from the device.
                        </span>

                        <span class="block text-xs text-base-content/70">
                            We'll notify you when the process is finished.
                        </span>
                    </div>

                    <hr class="pt-3 border-base-content/40">

                    <div class="form-control">
                        <label for="clear_form_password" class="block text-sm font-medium text-base-content"> Clear Timelogs </label>
                        <div class="flex gap-3">
                            <input
                                @keyup.enter="submit"
                                v-model="clearForm.password"
                                id="clear_form_password"
                                type="password"
                                class="flex-1 mt-1 input-sm input input-bordered"
                                placeholder="Password"
                            />
                            <button @click="submit" class="mt-1 btn btn-sm btn-error">
                                Clear
                            </button>
                        </div>
                        <InputError class="mt-0.5" :message="clearForm.errors.password" />
                        <span class="block mt-1 text-xs tracking-tight text-base-content/70">
                            To prevent any unauthorized modifications, please enter your password to proceed deleting all timelogs of the device.
                        </span>
                    </div>
            </div>

        </template>

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
            <template v-if="tab == 'delete'">
                <button type="button" class="btn btn-sm btn-error" @click="submit">
                    Delete
                </button>
            </template>


            <template v-else-if="tab !== 'clear'">
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
