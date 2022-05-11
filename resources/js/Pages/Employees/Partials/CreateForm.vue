<template>
    <jet-dialog-modal :show="show" @close="close">
        <template #content>
            <jet-form-section @submitted="create">
                <template #title>
                    Create New Employee
                </template>

                <template #description>
                    Enter employee's necessary information.
                </template>

                <template #form>
                    <!-- Biometrics ID -->
                    <div class="col-span-12 -mt-5">
                        <jet-label for="biometrics_id" value="Biometrics ID" />
                        <jet-input id="biometrics_id" type="text" class="block w-full mt-1" v-model="form.biometrics_id" />
                        <jet-input-error :message="form.errors.biometrics_id" class="mt-2" />
                    </div>

                    <!-- Last Name -->
                    <div class="col-span-12">
                        <jet-label for="last_name" value="Last Name" />
                        <jet-input id="last_name" type="text" class="block w-full mt-1" v-model="form.name.last" autocomplete="name.last" />
                        <jet-input-error :message="form.errors['name.last']" class="mt-2" />
                    </div>

                    <!-- First Name -->
                    <div class="col-span-12">
                        <jet-label for="first_name" value="First Name" />
                        <jet-input id="first_name" type="text" class="block w-full mt-1" v-model="form.name.first" autocomplete="name.first" />
                        <jet-input-error :message="form.errors['name.first']" class="mt-2" />
                    </div>

                    <!-- Middle Initial -->
                    <div class="col-span-12">
                        <jet-label for="middle_name" value="Middle Name" />
                        <jet-input id="middle_name" type="text" class="block w-full mt-1" v-model="form.name.middle" autocomplete="name.middle" />
                        <jet-input-error :message="form.errors['name.middle']" class="mt-2" />
                    </div>

                    <!-- Name Extension -->
                    <div class="col-span-12">
                        <jet-label for="name_extension" value="Name Extension" />
                        <jet-input id="name_extension" type="text" class="block w-full mt-1" v-model="form.name.extension" autocomplete="name.extension" />
                        <jet-input-error :message="form.errors['name.extension']" class="mt-2" />
                    </div>

                    <!-- Office -->
                    <div class="col-span-12">
                        <jet-label for="office" value="Office" />
                        <jet-input id="office" type="text" class="block w-full mt-1" v-model="form.office" />
                        <jet-input-error :message="form.errors.office" class="mt-2" />
                    </div>

                    <!-- Status -->
                    <div class="col-span-12">
                        <jet-label for="office" value="Status" />
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center">
                                <input id="regular" name="regular" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="1" v-model="form.regular" />
                                <label for="regular" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> Regular (Permanent, Casual, etc.) </label>
                            </div>
                            <div class="flex items-center">
                                <input id="non-regular" name="regular" type="radio" class="w-4 h-4 text-indigo-600 border-gray-300 rounded-full shadow-sm dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" value="0" v-model="form.regular" />
                                <label for="non-regular" class="block ml-3 text-sm font-medium text-gray-700 dark:text-gray-400"> Non Regular (Job Order, Contract of Service, etc.) </label>
                            </div>
                        </div>
                        <jet-input-error :message="form.errors.regular" class="mt-2" />
                    </div>

                    <jet-button class="hidden" :disabled="form.processing">
                        Save
                    </jet-button>
                </template>
            </jet-form-section>
        </template>

        <template #footer>
            <div class="flex flex-row-reverse items-end">
                <jet-button :class="{ 'opacity-25': form.processing }" @click="create" :disabled="form.processing">
                    Create
                </jet-button>

                <jet-secondary-button class="mr-3" @click="close">
                    Cancel
                </jet-secondary-button>

                <jet-action-message class="mr-3" :on="form.recentlySuccessful">
                    Saved.
                </jet-action-message>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetFormSection from '@/Jetstream/FormSection.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import TailwindSelect from '@/Tailwind/Select.vue'

    export default defineComponent({
        components: {
            JetActionMessage,
            JetButton,
            JetDialogModal,
            JetFormSection,
            JetInput,
            JetInputError,
            JetLabel,
            JetSecondaryButton,
            TailwindSelect,
        },

        emits: ['close', 'created'],

        props: {
            show: {
                default: false
            },
        },

        data() {
            return {
                form: this.$inertia.form({
                    biometrics_id: '',
                    name: {
                        first: '',
                        middle: '',
                        last: '',
                        extension: '',
                    },
                    office: '',
                    regular: false,
                }),
            }
        },

        methods: {
            create() {
                this.form.post(route('employees.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        this.close()

                        Swal.fire(
                            'Delete successful',
                            'Selected employees are deleted.',
                            'success'
                        )

                        this.$emit('created')
                    },
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
                    setTimeout(() => document.getElementById('biometrics_id').focus(), 250)
                }
            }
        }
    })

</script>
