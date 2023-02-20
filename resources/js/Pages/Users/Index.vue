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
                        <Link :href="route('users.create')">
                            <JetSecondaryButton style="width:90px">
                                Create
                            </JetSecondaryButton>
                        </Link>
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
                                                Username
                                            </th>
                                            <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                Title
                                            </th>
                                            <th scope="col" class="px-6 py-2 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                                Admin
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="user in users.data" class="cursor-pointer dark:hover:bg-gray-700 hover:bg-gray-200">
                                            <td class="pr-6 whitespace-nowrap">
                                                <Link :href="route('users.edit', user.id)">
                                                    <div class="flex items-center text-base font-medium tracking-wide text-gray-900 dark:text-white">
                                                        {{ user.name }}
                                                    </div>
                                                </Link>
                                            </td>
                                            <td class="px-6 whitespace-nowrap">
                                                <Link :href="route('users.edit', user.id)">
                                                    <div class="text-sm font-thin">
                                                        <p class="text-black dark:text-gray-100">
                                                            {{ user.username }}
                                                        </p>
                                                    </div>
                                                </Link>
                                            </td>
                                            <td class="px-6 whitespace-nowrap">
                                                <Link :href="route('users.edit', user.id)">
                                                    <div class="text-sm font-thin">
                                                        <p class="text-black dark:text-gray-100">
                                                            {{ user.title }}
                                                        </p>
                                                    </div>
                                                </Link>
                                            </td>
                                            <td class="px-6 whitespace-nowrap">
                                                <Link :href="route('users.edit', user.id)">
                                                    <div class="text-sm font-thin">
                                                        <p class="text-black dark:text-gray-100">
                                                            {{ user.administrator ? 'Yes' : 'No' }}
                                                        </p>
                                                    </div>
                                                </Link>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-100 border-t border-gray-200 dark:bg-gray-800 sm:px-6">
                                <div class="flex justify-between flex-1 sm:hidden">
                                    <Link :href="users.prev_page_url" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50" preserve-scroll preserve-state>Previous</Link>
                                    <Link :href="users.next_page_url" class="relative inline-flex items-center px-4 py-2 ml-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50" preserve-scroll preserve-state>Next</Link>
                                </div>
                                <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700 dark:text-gray-200">
                                            Showing
                                            <span class="font-medium">{{ users.from }}</span>
                                            to
                                            <span class="font-medium">{{ users.to }}</span>
                                            of
                                            <span class="font-medium">{{ users.total }}</span>
                                            results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="inline-flex -space-x-px rounded-md shadow-sm isolate" aria-label="Pagination">
                                            <Link :href="users.prev_page_url" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:z-20" preserve-scroll preserve-state>
                                                <span class="sr-only">Previous</span>
                                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                                </svg>
                                            </Link>
                                            <!-- Current: "z-10 bg-indigo-50 border-indigo-500 text-indigo-600", Default: "bg-white border-gray-300 text-gray-500 hover:bg-gray-50" -->
                                            <template v-for="link in users.links">
                                                <Link v-if="filterLink(link.label)" :href="link.url" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 focus:z-20" :class="{'z-10 bg-indigo-50 border-indigo-500 text-indigo-600 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100': link.active}">
                                                    {{ link.label }}
                                                </Link>
                                            </template>
                                            <!-- <a href="#" aria-current="page" class="relative z-10 inline-flex items-center px-4 py-2 text-sm font-medium text-indigo-600 border border-indigo-500 bg-indigo-50 focus:z-20">1</a>
                                            <a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 focus:z-20">2</a>
                                            <a href="#" class="relative items-center hidden px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 focus:z-20 md:inline-flex">3</a>
                                            <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300">...</span>
                                            <a href="#" class="relative items-center hidden px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 focus:z-20 md:inline-flex">8</a>
                                            <a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 focus:z-20">9</a>
                                            <a href="#" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 hover:bg-gray-50 focus:z-20">10</a> -->
                                            <Link :href="users.next_page_url" class="relative inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:z-20" preserve-scroll preserve-state>
                                                <span class="sr-only">Next</span>
                                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                                </svg>
                                            </Link>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3'
    import { Inertia } from '@inertiajs/inertia'
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

    export default defineComponent({
        props: [
            'users'
        ],

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

        methods: {
            filterLink(number) {
                return !isNaN(number) || number === '...'
            }
        }
    })
</script>
