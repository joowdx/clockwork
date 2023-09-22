<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import UserInformationModal from './Partials/UserInformationModal.vue'
import { nextTick, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
    users: Object,
    search: String,
    types: Object,
})

const modal = ref(false)

const user = ref(null)

watch(() => usePage().props.flash?.user, (user) => {
    if (user === undefined) {
        return
    }

    nextTick(() => showUserModal(user))
})

const showUserModal = (e) => {
    user.value = e
    modal.value = true
}
</script>

<template>
    <AppLayout title="Users">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Users
            </h2>
        </template>

        <div class="px-4 py-5 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <DataTable
                :items="users"
                :rowAction="showUserModal"
                ref="dataTable"
                class="table-sm"
                wrapperClass="h-[calc(100vh-329px)] min-h-[29em]"
            >
                <template #actions>
                    <div class="flex items-end content-end justify-end flex-1 order-first w-full md:order-none">
                        <div class="tooltip" data-tip="Create">
                            <button @click="showUserModal(null)" class="tracking-tighter btn btn-sm btn-square btn-primary">
                                <svg class="w-4 h-4 fill-current" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                                    <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM232 344V280H168c-13.3 0-24-10.7-24-24s10.7-24 24-24h64V168c0-13.3 10.7-24 24-24s24 10.7 24 24v64h64c13.3 0 24 10.7 24 24s-10.7 24-24 24H280v64c0 13.3-10.7 24-24 24s-24-10.7-24-24z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>

                <template #head>
                    <tr>
                        <th class="px-4 py-3 w-[48px]"> # </th>
                        <th class="w-48 px-2 py-3">Username</th>
                        <th class="px-2 py-3 w-96 min-w-96">Name</th>
                        <th class="px-2 py-3 w-36">Title</th>
                        <th class="px-2 py-3 w-36">Type</th>
                        <th class="w-16 px-2">
                            <button type="button" class="py-0 opacity-0 cursor-default btn btn-xs btn-primary">
                                Edit
                            </button>
                        </th>
                    </tr>
                </template>

                <template #default="{row, index}">
                    <tr class="hover group">
                        <td>
                            {{ index }}
                        </td>
                        <td class="lowercase">
                            {{ row.username }}
                        </td>
                        <td>
                            {{ row.name }}
                        </td>
                        <td>
                            {{ row.title }}
                        </td>
                        <td class="capitalize">
                            {{ Object.keys(types).find(k => types[k] === row.role)?.toLowerCase() ?? 'administrator' }}
                        </td>
                        <td class="px-2 py-0 text-right">
                            <button @click="showUserModal(row)" class="hidden group-hover:block btn btn-primary btn-xs">
                                Edit
                            </button>
                        </td>
                    </tr>
                </template>
            </DataTable>
        </div>

        <UserInformationModal v-model="modal" v-model:user="user" :types="types" />
    </AppLayout>
</template>
