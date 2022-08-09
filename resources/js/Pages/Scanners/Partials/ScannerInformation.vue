<template>
    <JetFormSection @submitted="save">
        <template #title>
            Scanner Information
        </template>

        <template #description>
            Update biometric scanner's basic information.
        </template>

        <template #form>
            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="name" value="Name" />
                <JetInput id="name" type="text" class="block w-full mt-1 uppercase" v-model="form.name" autocomplete="name" />
                <JetInputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Remarks -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="remarks" value="Remarks" />
                <JetInput id="remarks" type="text" class="block w-full mt-1" v-model="form.remarks" autocomplete="remarks" />
                <JetInputError :message="form.errors.remarks" class="mt-2" />
            </div>

            <!-- Attlog File -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="attlog_file" value="Attlog File" />
                <JetInput id="attlog_file" type="text" class="block w-full mt-1" v-model="form.attlog_file" autocomplete="attlog_file" />
                <JetInputError :message="form.errors.attlog_file" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <!-- Shared -->
                <div class="col-span-6">
                    <JetLabel for="biometrics_id" value="Shared" />
                    <TailwindSelect class="w-full" :options="[{name: 'YES', value: true}, {name: 'NO', value: false}]" v-model="form.shared" />
                    <JetInputError :message="form.errors.shared" class="mt-2" />
                </div>
            </div>

            <!-- Print Text Colour -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="print_text_colour" value="Print Text Colour" />
                <JetInput id="print_text_colour" type="color" class="block w-full mt-1 uppercase" style="height:42px" v-model="form.print_text_colour" autocomplete="print_text_colour" />
                <JetInputError :message="form.errors.print_text_colour" class="mt-2" />
            </div>

            <!-- Print Background Colour -->
            <div class="col-span-6 sm:col-span-4">
                <JetLabel for="print_background_colour" value="Print Background Colour" />
                <JetInput id="print_background_colour" type="color" class="block w-full mt-1 uppercase" style="height:42px" v-model="form.print_background_colour" autocomplete="print_background_colour" />
                <JetInputError :message="form.errors.print_background_colour" class="mt-2" />
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
                    id: this.$page.props.scanner?.id,
                    name: this.$page.props.scanner?.name,
                    remarks: this.$page.props.scanner?.remarks,
                    attlog_file: this.$page.props.scanner?.attlog_file,
                    print_text_colour: this.$page.props.scanner?.print_text_colour ?? '#000000',
                    print_background_colour: this.$page.props.scanner?.print_background_colour ?? '#FFFFFF',
                    shared: this.$page.props.scanner?.shared,
                }),
            }
        },

        methods: {
            save() {
                this.form.transform(data => this.$page.props.scanner ? {...data, _method: 'PUT'} : data).post(this.link, {
                    preserveScroll: true,
                });
            },
        },

        computed: {
            link() {
                return route(`scanners.${this.$page.props.scanner?'update':'store'}`, this.$page.props.scanner ? {scanner: this.form.id} :  {})
            }
        }
    })
</script>
