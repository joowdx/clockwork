<template>
    <jet-dialog-modal :show="show" @close="close">
        <template #content>
            <jet-form-section @submitted="update">
                <template #title>
                    Edit Employee
                </template>

                <template #description>
                    Update employee's information.

                    <div v-if="multiple">
                        <p class="block mt-3 text-xs font-medium text-red-600">
                            You are about to edit <b class="text-sm">{{ `${employee.length} employee${employee.length != 1 ? 's':''}` }}</b>.
                            Once updated, this action cannot be undone.
                        </p>

                        <p class="block mt-3 text-xs font-medium text-gray-700 dark:text-gray-400">
                            Put or select asterisk(*) to leave the field as is.
                        </p>
                    </div>
                </template>

                <template #form>
                    <!-- Biometrics ID -->
                    <div class="col-span-6 -mt-5" :class="{ hidden : multiple }">
                        <jet-label for="biometrics_id" value="Biometrics ID" />
                        <jet-input id="biometrics_id" type="text" class="block w-full mt-1" v-model="form.biometrics_id" />
                        <jet-input-error :message="form.errors.biometrics_id" class="mt-2" />
                    </div>

                    <!-- Last Name -->
                    <div class="col-span-6" :class="{ hidden : multiple }">
                        <jet-label for="last_name" value="Last Name" />
                        <jet-input id="last_name" type="text" class="block w-full mt-1" v-model="form.name.last" autocomplete="name.last" />
                        <jet-input-error :message="form.errors['name.last']" class="mt-2" />
                    </div>

                    <!-- First Name -->
                    <div class="col-span-6" :class="{ hidden : multiple }">
                        <jet-label for="first_name" value="First Name" />
                        <jet-input id="first_name" type="text" class="block w-full mt-1" v-model="form.name.first" autocomplete="name.first" />
                        <jet-input-error :message="form.errors['name.first']" class="mt-2" />
                    </div>

                    <!-- Middle Initial -->
                    <div class="col-span-6" :class="{ hidden : multiple }">
                        <jet-label for="middle_name" value="Middle Name" />
                        <jet-input id="middle_name" type="text" class="block w-full mt-1" v-model="form.name.middle" autocomplete="name.middle" />
                        <jet-input-error :message="form.errors['name.middle']" class="mt-2" />
                    </div>

                    <!-- Name Extension -->
                    <div class="col-span-6" :class="{ hidden : multiple }">
                        <jet-label for="name_extension" value="Name Extension" />
                        <jet-input id="name_extension" type="text" class="block w-full mt-1" v-model="form.name.extension" autocomplete="name.extension" />
                        <jet-input-error :message="form.errors['name.extension']" class="mt-2" />
                    </div>

                    <!-- Office -->
                    <div class="col-span-6" :class="{ '-mt-5' : multiple }">
                        <jet-label for="office" value="Office" />
                        <jet-input id="office" type="text" class="block w-full mt-1" v-model="form.office" />
                        <jet-input-error :message="form.errors.office" class="mt-2" />
                    </div>

                    <!-- Status -->
                    <div class="col-span-6">
                        <jet-label for="office" value="Status" />
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center" :class="{ hidden: ! multiple}">
                                <input id="regular_no_change" name="regular" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="*" :disabled="! multiple" v-model="form.regular" />
                                <label for="regular_no_change" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> * </label>
                            </div>
                            <div class="flex items-center">
                                <input id="regular" name="regular" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="1" v-model="form.regular" />
                                <label for="regular" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> Regular (Permanent, Casual, etc.) </label>
                            </div>
                            <div class="flex items-center">
                                <input id="non-regular" name="regular" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="0" v-model="form.regular" />
                                <label for="non-regular" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> Non Regular (Job Order, Contract of Service, etc.) </label>
                            </div>
                        </div>
                        <jet-input-error :message="form.errors.status" class="mt-2" />
                    </div>

                    <!-- Active -->
                    <div class="col-span-6">
                        <jet-label for="active" value="Active" />
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center" :class="{ hidden: ! multiple}">
                                <input id="active_no_change" name="active" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="*" :disabled="! multiple" v-model="form.active" />
                                <label for="active_no_change" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> * </label>
                            </div>
                            <div class="flex items-center">
                                <input id="active" name="active" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="1" v-model="form.active" />
                                <label for="active" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> True </label>
                            </div>
                            <div class="flex items-center">
                                <input id="inactive" name="active" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="0" v-model="form.active" />
                                <label for="inactive" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> False </label>
                            </div>
                        </div>
                        <jet-input-error :message="form.errors.status" class="mt-2" />
                    </div>

                    <jet-button class="hidden" :disabled="form.processing">
                        Save
                    </jet-button>
                </template>
            </jet-form-section>
        </template>

        <template #footer>
            <div class="flex flex-row-reverse items-end">
                <jet-button :class="{ 'opacity-25': form.processing }" @click="update" :disabled="form.processing">
                    Update
                </jet-button>

                <jet-danger-button :class="{ 'opacity-25': form.processing }" class="mr-3" @click="showDeleteDialog" :disabled="form.processing">
                    Delete
                </jet-danger-button>

                <jet-secondary-button class="mr-3" @click="close">
                    Cancel
                </jet-secondary-button>

                <jet-action-message class="mr-3" :on="form.recentlySuccessful">
                    Saved.
                </jet-action-message>
            </div>
        </template>
    </jet-dialog-modal>

    <jet-dialog-modal :show="deleteDialog" @close="closeDeleteDialog">
        <template #title>
            Delete Employee
        </template>

        <template #content>
            Are you sure you want to delete {{ `${employee.length > 1 ? 'all the ' + employee.length : 'the'} selected employee${employee.length != 1 ? 's':''}` }}?
            Once {{ `${employee.length > 1 ? 'these are' : 'this is'}` }} deleted, all of its resources and data will be permanently deleted.
            Please enter your password to confirm you would like to continue this action.

            <div class="mt-4">
                <jet-input type="password" class="block w-3/4 mt-1" placeholder="Password"
                            ref="password"
                            v-model="form.password"
                            @keyup.enter="destroy" />

                <jet-input-error :message="form.errors.password" class="mt-2" />
            </div>
        </template>

        <template #footer>
            <jet-secondary-button @click="closeDeleteDialog">
                Cancel
            </jet-secondary-button>

            <jet-danger-button class="ml-3" @click="destroy" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Delete Account
            </jet-danger-button>
        </template>
    </jet-dialog-modal>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
    import JetActionSection from '@/Jetstream/ActionSection.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetFormSection from '@/Jetstream/FormSection.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetDangerButton from '@/Jetstream/DangerButton.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import TailwindSelect from '@/Tailwind/Select.vue'

    export default defineComponent({
        components: {
            JetActionMessage,
            JetActionSection,
            JetButton,
            JetDialogModal,
            JetDangerButton,
            JetFormSection,
            JetInput,
            JetInputError,
            JetLabel,
            JetSecondaryButton,
            TailwindSelect,
        },

        emits: ['close'],

        props: {
            show: {
                default: false
            },

            employee: {
                default: [],
            }
        },

        data() {
            return {
                form: this.$inertia.form({
                    _method: 'PUT',
                    id: null,
                    biometrics_id: '',
                    name: {
                        first: '',
                        middle: '',
                        last: '',
                        extension: '',
                    },
                    office: '',
                    regular: false,
                    active: false,
                    password: '',
                }),

                multiple: false,

                deleteDialog: false,
            }
        },

        methods: {
            setForm() {
                this.multiple = this.employee.length > 1

                if (this.multiple) {
                    var office = _.uniq(this.employee.map(e => e.office))
                    var regular = _.uniq(this.employee.map(e => e.regular))
                    var active = _.uniq(this.employee.map(e => e.active))
                }

                this.form.id = this.employee.map(e => e.id)
                this.form.biometrics_id = ! this.multiple ? this.employee[0].biometrics_id : null
                this.form.name.last = ! this.multiple ? this.employee[0].name.last : null
                this.form.name.first = ! this.multiple ? this.employee[0].name.first : null
                this.form.name.middle = ! this.multiple ? this.employee[0].name.middle : null
                this.form.name.extension = ! this.multiple ? this.employee[0].name.extension : null
                this.form.office = ! this.multiple ? this.employee[0].office : office.length == 1 ? office[0] : '*'
                this.form.regular = ! this.multiple ? this.employee[0].regular ? 1 : 0 : regular.length == 1 ? regular[0] ? 1 : 0 : '*'
                this.form.active = ! this.multiple ? this.employee[0].active ? 1 : 0 : active.length == 1 ? active[0] ? 1 : 0 : '*'
            },

            showDeleteDialog() {
                this.deleteDialog = true;

                setTimeout(() => this.$refs.password.focus(), 250)
            },

            closeDeleteDialog()
            {
                this.deleteDialog = false
            },

            update() {
                this.form.post(route('employees.update', { employee: this.form.id }), {
                    preserveScroll: true,
                });
            },

            destroy() {
                this.form.delete(route('employees.destroy', { employee: this.form.id }), {
                    preserveScroll: true,
                });
            },

            close() {
                this.form.reset()

                this.form.clearErrors()

                this.$emit('close')
            },
        },

        watch: {
            show(show) {
                if (show) {
                    this.setForm()

                    setTimeout(() => document.getElementById(this.multiple  ? 'office' : 'last_name').focus(), 250)
                }
            }
        }
    })

</script>
