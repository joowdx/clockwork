<template>
    <app-layout title="Biometrics">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Scanners
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 bg-gray">
                <div class="flex flex-col">
                    <div class="flex items-center justify-end px-6 mb-5 space-x-3 sm:px-0">
                        <jet-input type="text" class="block w-full disabled:opacity-60" v-model="search" style="padding: .25rem .5em!important" autocomplete="name" placeholder="Search" />
                        <Link class="flex-none" :href="route('scanners.create')">
                            <jet-secondary-button>
                                Add New Scanner
                            </jet-secondary-button>
                        </Link>
                    </div>
                </div>
                <div class="overflow-hidden sm:rounded-lg">
                    <table class="min-w-full table-fixed">
                        <thead>
                            <tr>
                                <th scope="col" class="overflow-hidden w-[20%] py-3 pr-6 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                    Scanner
                                </th>
                                <th scope="col" class="overflow-hidden w-[30%] px-3 pl-6 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                    Assignee
                                </th>
                                <th scope="col" class="overflow-hidden w-[50%] px-3 pl-6 text-xs font-bold tracking-wider text-left text-gray-500 uppercase">
                                    Remarks
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="scanner in scanners" :key="scanner.id" :class="[! scanner.users.map(e => e.id).includes($page.props.user.id) ? 'text-gray-400 dark:text-gray-600' : 'text-gray-800 dark:text-gray-200']" >
                                <td class="pr-6 overflow-hidden truncate">
                                    <Link v-if="$page.props.user.administrator || scanner.shared || ! scanner.users.length || scanner.users.map(e => e.id).includes($page.props.user.id)" :href="route('scanners.edit', scanner.id)"> {{ scanner.name }} </Link>
                                    <template v-else> {{ scanner.name }} </template>
                                </td>
                                <td class="pl-6 overflow-hidden tracking-tighter">
                                    {{ scanner.users.map(e => e.username).join(', ') }}
                                </td>
                                <td class="pl-6 overflow-hidden tracking-tighter truncate">
                                    {{ scanner.remarks }}
                                    <!-- <p v-if="user.verified_by" class="lowercase">
                                        by <Link class="text-indigo-600 hover:text-indigo-900 dark:text-gray-400 dark:hover:text-gray-600" :href="route('users.show', user.verified_by.id)" v-html="'@' + user.verified_by.username" />
                                    </p> -->
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import JetInput from '@/Jetstream/Input.vue'

    export default defineComponent({
        components: {
            Link,
            AppLayout,
            JetInput,
            JetSecondaryButton,
        },

        props: {
            'scanners': Array,
        },

        data: function() {
            return {
                search: this.$page.props.search,
                active: [],
            };
        },

        watch: {
            search: _.debounce(function(search) {
                this.$inertia.get(route('scanners.index'), search ? { search: search } : {}, {
                    preserveState: true,
                    preserveScroll: true,
                })
            }, 500)
        },
    })
</script>
