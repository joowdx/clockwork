<template>
    <JetFormSection @submitted="save">
        <template #title>
            Employee Groups
        </template>

        <template #description>
            Add and remove this employee to groups of your liking.
        </template>

        <template #form>
            <!-- Employee Groups -->
            <div v-for="(group, index) in form.groups" class="col-span-6 sm:col-span-4">
                <JetInput :id="`${group}.${index}`" type="text" v-model="form.groups[index]" class="block w-full mt-1 uppercase" />
                <JetInputError :message="form.errors[`groups.${index}`]" class="mt-2" />
            </div>

            <JetButton class="hidden" :disabled="form.processing">
                Save
            </JetButton>
        </template>

        <template #actions>
            <JetActionMessage :on="form.recentlySuccessful" class="mr-3">
                Saved.
            </JetActionMessage>

            <JetSecondaryButton @click="add">
                Add
            </JetSecondaryButton>

            <JetButton class="ml-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
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
                    groups: this.$page.props.employee.groups ?? [],
                }),
            }
        },

        methods: {
            add() {
                this.form.groups.push('')
            },

            save() {
                this.form.transform(data =>
                    ({
                        groups: data.groups?.filter(g => g !== null || g.trim() !== ''),
                        _method: 'PUT',
                    })
                ).post(this.link, {
                    preserveScroll: true,
                });
            },
        },

        computed: {
            link() {
                return route(`employees.update`, {employee: this.$page.props.employee.id})
            }
        }
    })
</script>
