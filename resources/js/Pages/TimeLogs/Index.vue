<template>
    <AppLayout title="Time Logs">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Time Logs
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 bg-gray" style="margin-top:-20px!important">
                <div class="grid grid-cols-12 px-6 mb-6 gap-y-2 gap-x-3 sm:px-0">
                    <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                        <JetLabel value="By" />
                        <TailwindSelect class="w-full" :options="[{name: 'EMPLOYEE', value: 'employee'}, {name: 'OFFICE', value: 'office'}]" v-model="by" />
                    </div>
                    <template v-if="by === 'employee'">
                        <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                            <JetLabel value="Period" />
                            <TailwindSelect class="w-full" :options="[{name: 'CUSTOM', value: 'custom'}, {name: 'FULL', value: 'full'}, {name: '1ST HALF', value: '1st'}, {name: '2ND HALF', value: '2nd'}]" v-model="period" />
                        </div>
                        <template v-if="period === 'custom'">
                            <div class="hidden col-span-4 sm:inline-block lg:hidden">

                            </div>
                            <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                                <JetLabel value="From" />
                                <JetInput class="w-full uppercase" type="date" v-model="from" required />
                            </div>
                            <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                                <JetLabel value="To" />
                                <JetInput class="w-full uppercase" type="date" v-model="to" required />
                            </div>
                            <div class="col-span-8 sm:hidden">

                            </div>
                        </template>
                        <template v-else>
                            <div class="hidden col-span-4 sm:inline-block lg:hidden">

                            </div>
                            <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                                <JetLabel value="Month" />
                                <JetInput class="w-full uppercase" type="month" v-model="month" required />
                            </div>
                            <div class="col-span-8 sm:col-span-4 lg:col-span-2">

                            </div>
                        </template>
                    </template>
                    <template v-else >
                        <div class="col-span-6 sm:col-span-4 lg:col-span-2">
                            <JetLabel value="Date" />
                            <JetInput class="w-full uppercase" type="date" v-model="date" required />
                        </div>
                        <div class="col-span-8 sm:hidden lg:col-span-4 lg:inline-block"></div>
                    </template>
                    <div class="flex self-end col-span-4 mt-3 space-x-3 justify-self-end">
                        <Link class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-900 border border-transparent rounded-md hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 dark:border-gray-800" as="button" :href="route('timelogs.index')" preserve-scroll>
                            Reset
                        </Link>
                        <JetSecondaryButton @click="showImportDialog" style="width:90px" :disabled="importDialog">
                            Import
                        </JetSecondaryButton>
                        <JetSecondaryButton ref="print" class="items-center" @click="this.$refs.printPreview.contentWindow.print()" style="width:90px" :disabled="loadingPreview || selected.length === 0">
                            Print
                        </JetSecondaryButton>
                    </div>
                </div>

                <div class="grid grid-cols-12 px-6 mb-6 gap-y-2 gap-x-3 sm:px-0">
                    <div class="col-span-12" :class="{'lg:col-span-6': by === 'employee'}">
                        <JetLabel value="Name" />
                        <JetInput type="text" class="block w-full uppercase disabled:opacity-60" autocomplete="name" placeholder="Search" v-model="name" />
                    </div>
                    <template v-if="by === 'employee'">
                        <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                            <JetLabel value="Office" />
                            <TailwindSelect class="w-full" :options="['ALL', ...offices]" v-model="office" />
                        </div>
                        <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                            <JetLabel value="Status" />
                            <TailwindSelect class="w-full" :options="[{name: 'ALL', value: -1}, {name: 'REGULAR', value: 1}, {name: 'NON-REGULAR', value: 0}]" v-model="regular" />
                        </div>
                        <div class="col-span-12 sm:col-span-4 lg:col-span-2">
                            <JetLabel value="Active" />
                            <TailwindSelect class="w-full" :options="[{name: 'ALL', value: -1}, {name: 'ACTIVE', value: 1}, {name: 'INACTIVE', value: 0}]" v-model="active" />
                        </div>
                    </template>
                </div>

                <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 bg-gray">
                    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                        <div class="inline-block min-w-full py-2 align-middle">
                            <div class="overflow-hidden sm:rounded-lg">
                                <p v-if="selected.length" class="block text-sm font-medium text-right text-gray-700 dark:text-gray-400">
                                    {{ `${selected.length} ${by}${selected.length != 1 ? 's':''} selected` }}
                                </p>
                                <p v-else class="block text-sm font-medium text-right text-gray-700 dark:text-gray-400">
                                    no {{by}} selected
                                </p>
                                <table class="min-w-full">
                                    <thead>
                                        <template v-if="by === 'office'">
                                            <th scope="col" class="w-1 py-2 sm:pl-1.5 pl-6 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                <input class="text-indigo-600 border-gray-300 rounded shadow-sm cursor-pointer dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" type="checkbox" value="all" v-model="all" :disabled="office.length === 0">
                                            </th>
                                            <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                Name
                                            </th>
                                        </template>
                                        <template v-else>
                                            <tr>
                                                <th scope="col" class="w-1 py-2 sm:pl-1.5 pl-6 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    <input class="text-indigo-600 border-gray-300 rounded shadow-sm cursor-pointer dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" type="checkbox" value="all" v-model="all" :disabled="employees.length === 0">
                                                </th>
                                                <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    Name
                                                </th>
                                                <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    Office
                                                </th>
                                                <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    Scanners
                                                </th>
                                                <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    Status
                                                </th>
                                                <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    Print Format
                                                </th>
                                            </tr>
                                        </template>
                                    </thead>
                                    <tbody>
                                        <template v-if="by === 'office'">
                                            <tr v-if="offices.length === 0">
                                                <td colspan="2" class="sm:pl-1.5 pl-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    WE'VE COME UP EMPTY!
                                                </td>
                                            </tr>
                                            <tr v-for="office in offices" :key="office">
                                                <td class="sm:pl-1.5 pl-6 whitespace-nowrap">
                                                    <input class="text-indigo-600 border-gray-300 rounded shadow-sm cursor-pointer dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" type="checkbox" :value="office" v-model="selected">
                                                </td>
                                                <td class="px-6 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                            {{ office }}
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                        <template v-else>
                                            <tr v-if="employees.length === 0">
                                                <td colspan="2" class="sm:pl-1.5 pl-6 py-3 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                    WE'VE COME UP EMPTY!
                                                </td>
                                            </tr>
                                            <tr v-for="employee in employees" :key="employee.id">
                                                <td class="sm:pl-1.5 pl-6 whitespace-nowrap">
                                                    <input class="text-indigo-600 border-gray-300 rounded shadow-sm cursor-pointer dark:border-gray-600 dark:text-gray-500 focus:border-indigo-300 dark:focus:border-gray-600 focus:ring focus:ring-indigo-200 dark:focus:ring-gray-700 focus:ring-opacity-50" type="checkbox" :value="employee.id" v-model="selected">
                                                </td>
                                                <td class="px-6 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                            <Link :href="route('employees.edit', employee.id)">
                                                                {{ employee.name_format.fullStartLastInitialMiddle }}
                                                            </Link>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 whitespace-nowrap">
                                                    <div class="text-sm">
                                                        <div class="font-thin">
                                                            <p class="text-black dark:text-gray-100">
                                                                {{ employee.office }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 whitespace-nowrap">
                                                    <div class="text-sm font-thin">
                                                        <p class="text-black dark:text-gray-100">
                                                            {{
                                                                employee.scanners
                                                                    .map(e => e.name.toLowerCase().startsWith('coliseum-') ? 'coliseum-x' : e.name.toLowerCase())
                                                                    .filter((e, f, g) => g.indexOf(e) === f)
                                                                    .join(', ')
                                                            }}
                                                        </p>
                                                    </div>
                                                </td>
                                                <td class="px-6 whitespace-nowrap">
                                                    <div class="text-sm">
                                                        <div class="font-thin">
                                                            <p class="text-black dark:text-gray-100">
                                                                {{ employee.regular ? 'Regular' : 'Non-regular' }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="pl-6 whitespace-nowrap">
                                                    <div class="text-sm font-thin">
                                                        <p class="text-black dark:text-gray-100">
                                                            {{ employee.dtr_format ? 'CSC Form No. 48' : 'Old format'}}
                                                        </p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <JetDialogModal :show="importDialog" @close="closeImportDialog" :closeable="false">
            <template #title>
                Upload
            </template>

            <template #content>
                <div class="mb-3">
                    <div>
                        <JetLabel value="Scanner" />
                        <TailwindSelect class="w-full mb-2" :options="scanners" v-model="form.scanner" />
                        <JetInputError :message="form.errors.scanner" class="mt-2" />
                    </div>
                    <div class="flex justify-center px-6 pt-5 pb-6 mt-1 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path class="text-gray-400 stroke-current" stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="file-upload" class="relative font-medium text-indigo-600 rounded-md cursor-pointer hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500 dark:focus-within:ring-gray-500 dark:text-white">
                                    <span @click="importFile"> Upload a file </span>
                                </label>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                Select attlog file.
                            </p>
                        </div>
                    </div>
                    <p v-if="form.file" class="mt-1 text-lg tracking-tighter text-indigo-600 dark:text-white">
                        {{ form.file.name }}
                        <span class="float-right p-0">
                            <JetButton class="rounded-md" style="padding:0.25em!important" @click="clearFile"> &nbsp;&times;&nbsp; </JetButton>
                        </span>
                    </p>
                    <JetInputError :message="form.errors.file" class="mt-2" />
                </div>

                <p class="text-yellow-600"> Notice that time logs with unrecognized uid are ignored. Please associate such uids to its corresponding employee before uploading. </p>

            </template>

            <template #footer>
                <JetSecondaryButton @click="closeImportDialog" :disabled="form.processing">
                    Cancel
                </JetSecondaryButton>

                <JetButton :class="{ 'opacity-25': form.processing }" class="ml-3" @click="uploadFile" :disabled="form.processing">
                    Import
                </JetButton>
            </template>
        </JetDialogModal>

        <iframe title="daily time record" class="sr-only" ref="printPreview" :src="src" @load="printPreviewLoaded"/>
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
        },

        data() {
            return {
                src: '',
                all: ! this.$page.props.employees.length,
                selected: [],
                by: 'employee',
                name: '',
                office: 'ALL',
                regular: -1,
                active: 1,
                period: this.$page.props.period,
                date: this.$page.props.date,
                month: this.$page.props.month,
                from: this.$page.props.from,
                to: this.$page.props.to,
                scanners: this.$page.props.scanners.map(e => ({name: e.name.toUpperCase(), value: e.id})),
                importDialog: false,
                loadingPreview: true,
                toggleAllCheckbox: false,
                waitForFile: true,
                form: this.$inertia.form({
                    file: null,
                    scanner: null,
                }),
            }
        },

        watch: {
            by() {
                this.name = '';
                this.selected = [];
            },

            all() {
                if(this.toggleAllCheckbox) {
                    this.toggleAllCheckbox = false
                    return
                }

                this.selected = this.all
                    ? _.uniq(this.selected.concat(this[`${this.by}s`].map(e => this.by === 'employee' ? e.id : e)))
                    : this.selected.filter(e => ! this[`${this.by}s`].map(e => this.by === 'employee' ? e.id : e).includes(e))
            },

            employees() {
                this.updateToggledCheckbox()
            },

            offices() {
                this.updateToggledCheckbox()
            },

            selected() {
                this.updateToggledCheckbox()

                this.updatePrintPreview()
            },

            link() {
                this.debounceLink()
            }
        },

        methods: {
            uploadFile() {
                this.form.post(route('timelogs.store'), {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire(
                            'Import successful',
                            this.form.file.type == 'application/vnd.ms-excel' ? 'Employees updated. Please refresh to see changes.' : 'Time logs successfully imported!',
                            'success'
                        )
                        this.closeImportDialog()
                        this.resetForm()
                    },
                });
            },

            clearFile() {
                this.form.file = null
            },

            resetForm() {
                this.form.reset()

                this.form.clearErrors()
            },

            debounceLink: _.debounce(function () {
                this.src = this.link
            }, 500),

            async importFile() {
                this.waitForFile = true

                const file = await window.showOpenFilePicker({
                    types: [
                        {
                            description: 'DAT File',
                            accept: {
                                'text/dat': '.dat'
                            },
                        },
                    ],
                    excludeAcceptAllOption: true,
                    multiple: false,
                })

                this.form.file = await file[0].getFile()

                const scanner = this.$page.props.scanners.find(e => (e.attlog_file + '.dat') === this.form.file.name)

                if (scanner) {
                    this.form.scanner = scanner.id
                }

                this.waitForFile = false
            },

            showImportDialog() {
                this.importDialog = true
            },

            closeImportDialog() {
                this.importDialog = false

                this.resetForm()
            },

            printPreviewLoaded() {
                this.loadingPreview = false
            },

            updatePrintPreview() {
                this.loadingPreview = true
            },

            updateToggledCheckbox() {
                if (this.$page.props.employees === undefined) {
                    return;
                }

                if(this.all && ! this[`${this.by}s`].map(e => this.by === 'employee' ? e.id : e).every(e => this.selected.includes(e))) {
                    this.toggleAllCheckbox = true
                    this.all = false
                } else if (! this.all && this[`${this.by}s`].map(e => this.by === 'employee' ? e.id : e).every(e => this.selected.includes(e))) {
                    this.toggleAllCheckbox = true
                    this.all = true
                }
            },
        },

        computed: {
            employees() {
                if (this.$page.props.employees === undefined) {
                    return [];
                }

                return this.$page.props.employees
                    .filter(e => this.name ? fuzzysort.single(this.name, `${e.name_format.full} ${e.name_format.fullStartLast}`) : true )
                    .filter(e => this.office != 'ALL' ? this.office == e.office : true)
                    .filter(e => this.regular != -1 ? e.regular == this.regular : true)
                    .filter(e => this.active != -1 ? e.active == this.active : true)
            },

            offices() {
                if (this.$page.props.employees === undefined) {
                    return [];
                }

                switch (this.by) {
                    case 'office': {
                        return this.$page.props.offices
                            .filter(e => this.name ? fuzzysort.single(this.name, e) : true )
                    }
                    case 'employee': {
                        return this.$page.props.offices
                            .filter(e => _.includes(_.uniq(this.$page.props.employees.map(e => e.office)), e))
                    }
                }
            },

            link() {
                switch (this.by) {
                    case 'office': {
                        return route('print', {
                            by: this.by,
                            date: this.date,
                            offices: this.selected,
                        });
                    }
                    case 'employee': {
                        return route('print', {
                            by: this.by,
                            period: this.period,
                            from: this.from,
                            to: this.to,
                            month: this.month,
                            employees: this.selected,
                        });
                    }
                }
            }
        }
    })
</script>
