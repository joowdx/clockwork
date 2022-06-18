<template>
    <AppLayout title="Employees">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Employees
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 bg-gray" style="margin-top:-20px!important">
                <div class="grid grid-cols-12 px-6 mb-6 justify-items-end gap-y-2 gap-x-3 sm:px-0">
                    <div class="flex self-end col-span-12 mt-3 space-x-3">
                        <JetSecondaryButton style="width:90px">
                            Create
                        </JetSecondaryButton>
                        <JetSecondaryButton @click="showImportDialog" style="width:90px" :disabled="importDialog">
                            Import
                        </JetSecondaryButton>
                        <JetSecondaryButton style="width:90px" :disabled="true">
                            Export
                        </JetSecondaryButton>
                    </div>
                </div>

                <div class="grid grid-cols-12 px-6 mb-6 gap-y-2 gap-x-3 sm:px-0">
                    <div class="col-span-12 lg:col-span-6">
                        <JetLabel value="Name" />
                        <JetInput type="text" class="block w-full uppercase disabled:opacity-60" autocomplete="name" placeholder="Search" v-model="name" />
                    </div>
                    <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                        <JetLabel value="Office" />
                        <TailwindSelect class="w-full" :options="$page.props.offices" v-model="office" />
                    </div>
                    <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                        <JetLabel value="Regular" />
                        <TailwindSelect class="w-full" :options="[{name: 'ALL', value: -1}, {name: 'REGULAR', value: 1}, {name: 'NONREGULAR', value: 0}]" v-model="regular" />
                    </div>
                    <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                        <JetLabel value="Active" />
                        <TailwindSelect class="w-full" :options="[{name: 'ALL', value: -1}, {name: 'ACTIVE', value: 1}, {name: 'INACTIVE', value: 0}]" v-model="active" />
                    </div>
                </div>

                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 bg-gray">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle">
                            <div class="overflow-hidden">
                                <table class="min-w-full">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="py-2 pr-6 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                Name
                                            </th>
                                            <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                Office
                                            </th>
                                            <th scope="col" class="py-2 pl-6 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                Regular
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-if="employees.length === 0">
                                            <td colspan="2" class="sm:pl-1.5 pl-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                WE'VE COME UP EMPTY!
                                        </td>
                                        </tr>
                                        <tr v-for="employee in employees" :key="employee.id" class="cursor-pointer dark:hover:bg-gray-700 hover:bg-gray-200">
                                            <td class="pr-6 whitespace-nowrap">
                                                <Link :href="route('employees.edit', employee.id)">
                                                    <div class="flex items-center text-base font-medium tracking-wide text-gray-900 dark:text-white">
                                                        {{ employee.name_format.fullStartLastInitialMiddle }}
                                                    </div>
                                                </Link>
                                            </td>
                                            <td class="px-6 whitespace-nowrap">
                                                <Link :href="route('employees.edit', employee.id)">
                                                    <div class="text-sm font-thin">
                                                        <p v-if="employee.office" class="text-black dark:text-gray-100">
                                                            {{ employee.office }}
                                                        </p>
                                                        <p v-else class="text-gray-500 dark:text-gray-500">
                                                            Not set
                                                        </p>
                                                    </div>
                                                </Link>
                                            </td>
                                            <td class="pl-6 whitespace-nowrap">
                                                <Link :href="route('employees.edit', employee.id)">
                                                    <div class="text-sm font-thin">
                                                        <p class="text-black dark:text-gray-100">
                                                            {{ employee.regular ? 'Yes' : 'No'}}
                                                        </p>
                                                    </div>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <JetDialogModal :show="importDialog" @close="closeImportDialog">
            <template #title>
                Upload
            </template>

            <template #content>
                <div class="mb-3">
                    <div class="flex justify-center px-6 pt-5 pb-6 mt-1 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path class="text-gray-400 stroke-current" stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label class="relative font-medium text-indigo-600 rounded-md cursor-pointer hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500 dark:focus-within:ring-gray-500 dark:text-white">
                                    <span @click="importFile"> Upload a file </span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                CSV file up to 10MB
                            </p>
                        </div>
                    </div>
                    <p v-if="form.file" class="mt-1 text-lg tracking-tighter text-indigo-600 dark:text-white">
                        {{ form.file.name }}
                        <span class="float-right p-0">
                            <JetButton class="rounded-md" style="padding:0.25em!important" @click="resetForm"> &nbsp;&times;&nbsp; </JetButton>
                        </span>
                    </p>
                </div>
                <p class="text-sm text-red-600 uppercase">
                    Warning: Importing csv will replace all existing data with the new one!
                </p>

                <JetInputError :message="form.errors.file" class="mt-2" />
            </template>

            <template #footer>
                <JetSecondaryButton @click="closeImportDialog">
                    Cancel
                </JetSecondaryButton>

                <JetButton :class="{ 'opacity-25': form.processing }" class="ml-3" @click="uploadFile" :disabled="form.processing || waitForFile">
                    Import
                </JetButton>
            </template>
        </JetDialogModal>

        <create-form :scanners="$page.props.scanners" :show="createDialog" @close="closeCreateDialog" />

        <!-- <edit-form :employee="employees.filter(e => selected.includes(e.id))" :show="editDialog" @close="closeEditDialog" @deleted="clearSelection" /> -->
    </AppLayout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetButton from '@/Jetstream/Button.vue'
    import JetCheckbox from '@/Jetstream/Checkbox.vue'
    import JetDialogModal from '@/Jetstream/DialogModal.vue'
    import JetLabel from '@/Jetstream/Label.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import JetInput from '@/Jetstream/Input.vue'
    import JetInputError from '@/Jetstream/InputError.vue'
    import TailwindSelect from '@/Tailwind/Select.vue'

    import CreateForm from '@/Pages/Employees/Partials/CreateForm.vue'
    import EditForm from '@/Pages/Employees/Partials/EditForm.vue'

    import Swal from 'sweetalert2'
    import fuzzysort from 'fuzzysort'

    export default defineComponent({
        components: {
            Link,
            AppLayout,
            JetButton,
            JetCheckbox,
            JetDialogModal,
            JetLabel,
            JetInput,
            JetInputError,
            JetSecondaryButton,
            TailwindSelect,
            CreateForm,
            EditForm,
        },

        data() {
            return {
                selected: [],
                name: '',
                office: 'ALL',
                regular: -1,
                active: 1,
                month: this.$page.props.month,
                period: 'full',
                createDialog: false,
                editDialog: false,
                importDialog: false,
                loadingPreview: true,
                toggleAllCheckbox: false,
                form: this.$inertia.form({
                    file: null,
                }),
                waitForFile: true,
            }
        },

        methods: {
            uploadFile() {
                this.form.post(route('employees.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire(
                            'Import successful',
                            'Employees updated.',
                            'success'
                        )
                        this.closeImportDialog()

                        this.resetForm()
                    },
                });
            },

            resetForm() {
                this.form.reset()

                this.form.clearErrors()
            },

            showCreateDialog() {
                this.createDialog = true
            },

            closeCreateDialog() {
                this.createDialog = false
            },

            async importFile() {
                this.waitForFile = true

                const file = await window.showOpenFilePicker({
                    types: [
                        {
                            description: 'CSV File',
                            accept: {
                                'text/csv': '.csv'
                            },
                        },
                    ],
                    excludeAcceptAllOption: true,
                    multiple: false,
                })

                this.form.file = await file[0].getFile()

                this.waitForFile = false
            },

            showImportDialog() {
                this.importDialog = true
            },

            closeImportDialog() {
                this.importDialog = false

                this.resetForm()
            },
        },

        computed: {
            employees() {
                return this.$page.props.employees
                    .filter(e => this.name ? fuzzysort.single(this.name, `${e.name_format.full} ${e.name_format.fullStartLast}`) : true )
                    .filter(e => this.office != 'ALL' ? this.office == e.office : true)
                    .filter(e => this.regular != -1 ? e.regular == this.regular : true)
                    .filter(e => this.active != -1 ? e.active == this.active : true)
            },
        },
    })
</script>
