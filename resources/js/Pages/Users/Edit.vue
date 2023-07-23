<template>
    <AppLayout title="Users">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                <!-- <Link class="underline" :href="route('users.index')">Users</Link> / {{ updateUser.username }} -->
            </h2>
        </template>

        <div>
            <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <UserInformationForm :updateUser="updateUser" />

                <!-- <DeleteUserForm :updateUser="updateUser" /> -->

            </div>
        </div>
    </AppLayout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/vue3'
    import AppLayout from '@/Layouts/AppLayout.vue'
    import UserInformationForm from './Partials/UserInformationForm.vue'
    import DeleteUserForm from './Partials/DeleteUserForm.vue'

    export default defineComponent({
        props: [
            'updateUser'
        ],

        components: {
            AppLayout,
            Link,
            UserInformationForm,
            DeleteUserForm,
        },
    })
</script>
