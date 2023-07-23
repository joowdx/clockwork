<script>
import { defineComponent } from 'vue'
import ActionSection from '@/Components/ActionSection.vue'
import InputError from '@/Components/InputError.vue'
import Modal from '@/Components/Modal.vue'

export default defineComponent({
    props: ['sessions'],

    components: {
        ActionSection,
        InputError,
        Modal,
    },

    data() {
        return {
            confirmingLogout: false,

            form: this.$inertia.form({
                password: '',
            })
        }
    },

    methods: {
        confirmLogout() {
            this.confirmingLogout = true

            setTimeout(() => this.$refs.password.focus(), 250)
        },

        logoutOtherBrowserSessions() {
            this.form.delete(route('other-browser-sessions.destroy'), {
                preserveScroll: true,
                onSuccess: () => this.closeModal(),
                onError: () => this.$refs.password.focus(),
                onFinish: () => this.form.reset(),
            })
        },

        closeModal() {
            this.confirmingLogout = false

            this.form.reset()

            this.form.clearErrors()
        },
    },

    watch: {
        confirmingLogout(show) {
            if (show) {
                this.form.reset()

                this.form.clearErrors()
            }
        }
    }
})
</script>

<template>
    <ActionSection>
        <template #title>
            Browser Sessions
        </template>

        <template #description>
            Manage and log out your active sessions on other browsers and devices.
        </template>

        <template #content>
            <div class="max-w-xl text-sm">
                If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.
            </div>

            <!-- Other Browser Sessions -->
            <div class="mt-5 space-y-6" v-if="sessions.length > 0">
                <div class="flex items-center" v-for="(session, i) in sessions" :key="i">
                    <div>
                        <svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" stroke="currentColor" class="w-8 h-8 text-gray-500" v-if="session.agent.is_desktop">
                            <path d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>

                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" class="w-8 h-8 text-gray-500" v-else>
                            <path d="M0 0h24v24H0z" stroke="none"></path><rect x="7" y="4" width="10" height="16" rx="1"></rect><path d="M11 5h2M12 17v.01"></path>
                        </svg>
                    </div>

                    <div class="ml-3">
                        <div class="text-sm">
                            {{ session.agent.platform ? session.agent.platform : 'Unknown' }} - {{ session.agent.browser ? session.agent.browser : 'Unknown' }}
                        </div>

                        <div>
                            <div class="text-xs">
                                {{ session.ip_address }}

                                <span class="font-semibold text-success" v-if="session.is_current_device">This device</span>
                                <span v-else>Last active {{ session.last_active }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center mt-5">
                <button class="mr-3 btn btn-secondary btn-sm" @click="confirmLogout">
                    Log Out Other Browser Sessions
                </button>

                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <p v-if="form.recentlySuccessful" class="mr-3 text-sm opacity-50 text-base-content">Done.</p>
                </Transition>
            </div>

            <!-- Log Out Other Devices Confirmation Modal -->
            <Modal v-model="confirmingLogout">
                <template #header>
                    Log Out Other Browser Sessions
                </template>

                Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.

                <div class="mt-4">
                    <input type="password" class="block w-3/4 mt-1 input input-bordered" placeholder="Password"
                                ref="password"
                                v-model="form.password"
                                @keyup.enter="logoutOtherBrowserSessions" />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <template #action>
                    <button class="ml-3 btn btn-secondary" @click="logoutOtherBrowserSessions">
                        Log Out Other Browser Sessions
                    </button>
                </template>
            </Modal>
        </template>
    </ActionSection>
</template>
