<template>
    <app-layout title="Profile">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Profile
            </h2>
        </template>

        <div>
            <div class="py-10 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                    <update-profile-information-form :user="$page.props.user" class="pb-4 sm:pb-8" />

                   <hr class="hidden sm:block border-base-content" />
                </div>

                <div v-if="$page.props.jetstream.canUpdatePassword">
                    <update-password-form class="py-4 sm:py-8" />

                   <hr class="hidden sm:block border-base-content" />
                </div>

                <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                    <two-factor-authentication-form class="py-4 sm:py-8" />

                   <hr class="hidden sm:block border-base-content" />
                </div>

                <logout-other-browser-sessions-form :sessions="sessions" class="py-4 sm:py-8" />

                <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                   <hr class="hidden sm:block border-base-content" />

                    <delete-user-form class="py-4 sm:py-8" />
                </template>
            </div>
        </div>
    </app-layout>
</template>

<script>
import { defineComponent } from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue'
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue'
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue'
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue'
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue'

export default defineComponent({
    props: ['sessions'],

    components: {
        AppLayout,
        DeleteUserForm,
        LogoutOtherBrowserSessionsForm,
        TwoFactorAuthenticationForm,
        UpdatePasswordForm,
        UpdateProfileInformationForm,
    },
})
</script>
