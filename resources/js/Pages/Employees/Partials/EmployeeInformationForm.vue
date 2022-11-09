<template>
    <JetFormSection @submitted="save">
        <template #title>
            Employee Information
        </template>

        <template #description>
            Update employee's basic information.
        </template>

        <template #form>
            <!-- Last Name -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="last_name" value="Last Name" />
                <JetInput id="last_name" type="text" class="block w-full mt-1 uppercase" v-model="form.name.last" autocomplete="name.last" />
                <JetInputError :message="form.errors['name.last'] ?? form.errors.name" class="mt-2" />
            </div>

            <!-- First Name -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="first_name" value="First Name" />
                <JetInput id="first_name" type="text" class="block w-full mt-1 uppercase" v-model="form.name.first" autocomplete="name.first" />
                <JetInputError :message="form.errors['name.first'] ?? form.errors.name" class="mt-2" />
            </div>

            <!-- Middle Initial -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="middle_name" value="Middle Name" />
                <JetInput id="middle_name" type="text" class="block w-full mt-1 uppercase" v-model="form.name.middle" autocomplete="name.middle" />
                <JetInputError :message="form.errors['name.middle'] ?? form.errors.name" class="mt-2" />
            </div>

            <!-- Name Extension -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="name_extension" value="Name Extension" />
                <JetInput id="name_extension" type="text" class="block w-full mt-1 uppercase" v-model="form.name.extension" autocomplete="name.extension" />
                <JetInputError :message="form.errors['name.extension'] ?? form.errors.name" class="mt-2" />
            </div>

            <!-- Office -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="office" value="Office" />
                <JetInput id="office" type="text" class="block w-full mt-1 uppercase" v-model="form.office" />
                <JetInputError :message="form.errors.office" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <!-- Regular -->
                <div class="col-span-6">
                    <JetLabel for="biometrics_id" value="Regular" />
                    <TailwindSelect class="w-full" :options="[{name: 'REGULAR', value: true}, {name: 'NONREGULAR', value: false}]" v-model="form.regular" />
                    <JetInputError :message="form.errors.regular" class="mt-2" />
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <!-- Active -->
                <div class="col-span-6">
                    <JetLabel for="active" value="Active" />
                    <TailwindSelect id="active" class="w-full" :options="[{name: 'ACTIVE', value: true}, {name: 'INACTIVE', value: false}]" v-model="form.active" />
                    <JetInputError :message="form.errors.active" class="mt-2" />
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <!-- CSC Format -->
                <div class="col-span-6">
                    <JetLabel for="csc_format" value="Use CSC DTR Print Format" />
                    <TailwindSelect id="csc_format" class="w-full" :options="[{name: 'Yes.', value: true}, {name: 'No.', value: false}]" v-model="form.csc_format" />
                    <JetInputError :message="form.errors.csc_format" class="mt-2" />
                </div>
            </div>


            <JetButton class="hidden" :disabled="form.processing">
                Save
            </JetButton>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <JetButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </JetButton>
        </template>
    </JetFormSection>
</template>

<script>
    import { defineComponent } from 'vue'
    import JetActionMessage from '@/Jetstream/ActionMessage.vue'
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
                    id: this.$page.props.employee?.id,
                    name: {
                        last: this.$page.props.employee?.name?.last,
                        first: this.$page.props.employee?.name?.first,
                        middle: this.$page.props.employee?.name?.middle,
                        extension: this.$page.props.employee?.name?.extension,
                    },
                    office: this.$page.props.employee?.office,
                    regular: this.$page.props.employee?.regular,
                    active: this.$page.props.employee?.active,
                    csc_format: this.$page.props.employee?.csc_format
                }),
            }
        },

        methods: {
            save() {
                this.form.transform(data => this.$page.props.employee ? {...data, _method: 'PUT'} : data).post(this.link, {
                    preserveScroll: true,
                });
            },
        },

        computed: {
            link() {
                return route(`employees.${this.$page.props.employee?'update':'store'}`, this.$page.props.employee ? {employee: this.form.id} :  {})
            }
        }
    })
</script>
