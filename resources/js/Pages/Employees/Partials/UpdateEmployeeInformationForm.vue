<template>
    <jet-form-section @submitted="update">
        <template #title>
            Employee Information
        </template>

        <template #description>
            Update employee's basic information.
        </template>

        <template #form>
            <!-- Last Name -->
            <div class="col-span-6 sm:col-span-4">
                <jet-label for="last_name" value="Last Name" />
                <jet-input id="last_name" type="text" class="block w-full mt-1 uppercase" v-model="form.name.last" autocomplete="name.last" />
                <jet-input-error :message="form.errors['name.last']" class="mt-2" />
            </div>

            <!-- First Name -->
            <div class="col-span-6 sm:col-span-4">
                <jet-label for="first_name" value="First Name" />
                <jet-input id="first_name" type="text" class="block w-full mt-1 uppercase" v-model="form.name.first" autocomplete="name.first" />
                <jet-input-error :message="form.errors['name.first']" class="mt-2" />
            </div>

            <!-- Middle Initial -->
            <div class="col-span-6 sm:col-span-4">
                <jet-label for="middle_name" value="Middle Name" />
                <jet-input id="middle_name" type="text" class="block w-full mt-1 uppercase" v-model="form.name.middle" autocomplete="name.middle" />
                <jet-input-error :message="form.errors['name.middle']" class="mt-2" />
            </div>

            <!-- Name Extension -->
            <div class="col-span-6 sm:col-span-4">
                <jet-label for="name_extension" value="Name Extension" />
                <jet-input id="name_extension" type="text" class="block w-full mt-1 uppercase" v-model="form.name.extension" autocomplete="name.extension" />
                <jet-input-error :message="form.errors['name.extension']" class="mt-2" />
            </div>

            <!-- Office -->
            <div class="col-span-6 sm:col-span-4">
                <jet-label for="office" value="Office" />
                <jet-input id="office" type="text" class="block w-full mt-1 uppercase" v-model="form.office" />
                <jet-input-error :message="form.errors.office" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <!-- Regular -->
                <div class="col-span-6">
                    <jet-label for="biometrics_id" value="Regular" />
                    <tailwind-select class="w-full" :options="[{name: 'REGULAR', value: true}, {name: 'NONREGULAR', value: false}]" v-model="form.regular" />
                    <jet-input-error :message="form.errors.regular" class="mt-2" />
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <!-- Active -->
                <div class="col-span-6">
                    <jet-label for="biometrics_id" value="Active" />
                    <tailwind-select class="w-full" :options="[{name: 'ACTIVE', value: true}, {name: 'INACTIVE', value: false}]" v-model="form.active" />
                    <jet-input-error :message="form.errors.active" class="mt-2" />
                </div>
            </div>

            <jet-button class="hidden" :disabled="form.processing">
                Save
            </jet-button>
        </template>

        <template #actions>
            <jet-action-message :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </jet-action-message>

            <jet-button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </jet-button>
        </template>
    </jet-form-section>
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

        data() {
            return {
                form: this.$inertia.form({
                    _method: 'PUT',
                    id: this.$page.props.employee.id,
                    name: {
                        last: this.$page.props.employee.name.last,
                        first: this.$page.props.employee.name.first,
                        middle: this.$page.props.employee.name.middle,
                        extension: this.$page.props.employee.name.extension,
                    },
                    office: this.$page.props.employee.office,
                    regular: this.$page.props.employee.regular,
                    active: this.$page.props.employee.active,
                }),
            }
        },

        methods: {
            update() {
                this.form.post(route('employees.update', { employee: this.form.id }), {
                    preserveScroll: true,
                });
            },
        },
    })
</script>
