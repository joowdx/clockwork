<script setup>
import { usePage } from '@inertiajs/vue3'
import { ref } from 'vue'

const alert = usePage().props.alert

const user = usePage().props.auth.user

const type = ref({
    error: alert.type === 'error',
    info: alert.type === 'info',
    question: alert.type === 'question',
    success: alert.type === 'success',
    warning: alert.type === 'warning',
    normal: alert.type === null || alert.type === '',
})

const dismissAlertWithExpiry = () => {
    const key = `alert_${user?.id ?? 'guest'}`

    const now = new Date()

    const midnight = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 0, 0, 0)

    const ttl = midnight.getTime() - now.getTime()

    const item = {
        value: alert,
        expiry: now.getTime() + ttl,
    }

    localStorage.setItem(key, JSON.stringify(item))

    dismissed.value = true
}

const isAlertDismissed = () => {
    const key = `alert_${user?.id ?? 'guest'}`

    if (alert.dismissable) {
        const itemStr = localStorage.getItem(key)

        if (!itemStr) {
            return false
        }

        const item = JSON.parse(itemStr)

        if (item.value.type !== alert.type || item.value.title !== alert.title || item.value.message !== alert.message) {
            localStorage.removeItem(key)

            return false
        }

        const now = new Date()

        if (now.getTime() > item.expiry) {
            localStorage.removeItem(key)

            return false
        }

        return true
    } else {
        localStorage.removeItem(key)

        return false
    }
}

const dismissed = ref(!(alert.title && alert.message) || isAlertDismissed())
</script>


<template>
    <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
        <div
            v-if="!dismissed"
            :class="{
                'bg-error/90': type.error,
                'bg-info/90': type.info,
                'bg-accent/90': type.question,
                'bg-success/90': type.success,
                'bg-warning/90': type.warning,
                'bg-primary/90': type.normal,
            }"
        >
            <div
                class="px-4 py-2 mx-auto bg-transparent border-0 rounded-none max-w-7xl sm:px-6 lg:px-8 alert"
                :class="{
                    'text-error-content': type.error,
                    'text-info-content': type.info,
                    'text-accent-content': type.question,
                    'text-success-content': type.success,
                    'text-warning-content': type.warning,
                    'text-primary-content': type.normal,
                }"
            >
                <svg v-if="type.error" class="w-5 h-5 fill-current shrink-0" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                    <path d="M256 48a208 208 0 1 1 0 416 208 208 0 1 1 0-416zm0 464A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM175 175c-9.4 9.4-9.4 24.6 0 33.9l47 47-47 47c-9.4 9.4-9.4 24.6 0 33.9s24.6 9.4 33.9 0l47-47 47 47c9.4 9.4 24.6 9.4 33.9 0s9.4-24.6 0-33.9l-47-47 47-47c9.4-9.4 9.4-24.6 0-33.9s-24.6-9.4-33.9 0l-47 47-47-47c-9.4-9.4-24.6-9.4-33.9 0z"/>
                </svg>

                <svg v-else-if="type.question" class="w-5 h-5 fill-current shrink-0" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 384 512">
                    <path d="M192 0c-41.8 0-77.4 26.7-90.5 64H64C28.7 64 0 92.7 0 128V448c0 35.3 28.7 64 64 64H320c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H282.5C269.4 26.7 233.8 0 192 0zm0 64a32 32 0 1 1 0 64 32 32 0 1 1 0-64zM105.8 229.3c7.9-22.3 29.1-37.3 52.8-37.3h58.3c34.9 0 63.1 28.3 63.1 63.1c0 22.6-12.1 43.5-31.7 54.8L216 328.4c-.2 13-10.9 23.6-24 23.6c-13.3 0-24-10.7-24-24V314.5c0-8.6 4.6-16.5 12.1-20.8l44.3-25.4c4.7-2.7 7.6-7.7 7.6-13.1c0-8.4-6.8-15.1-15.1-15.1H158.6c-3.4 0-6.4 2.1-7.5 5.3l-.4 1.2c-4.4 12.5-18.2 19-30.6 14.6s-19-18.2-14.6-30.6l.4-1.2zM160 416a32 32 0 1 1 64 0 32 32 0 1 1 -64 0z"/>
                </svg>

                <svg v-else-if="type.info" class="w-5 h-5 fill-current shrink-0" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                    <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zM216 336h24V272H216c-13.3 0-24-10.7-24-24s10.7-24 24-24h48c13.3 0 24 10.7 24 24v88h8c13.3 0 24 10.7 24 24s-10.7 24-24 24H216c-13.3 0-24-10.7-24-24s10.7-24 24-24zm40-208a32 32 0 1 1 0 64 32 32 0 1 1 0-64z"/>
                </svg>

                <svg v-else-if="type.success" class="w-5 h-5 fill-current shrink-0" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 448 512">
                    <path d="M342.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L160 178.7l-57.4-57.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l80 80c12.5 12.5 32.8 12.5 45.3 0l160-160zm96 128c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L160 402.7 54.6 297.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l128 128c12.5 12.5 32.8 12.5 45.3 0l256-256z"/>
                </svg>

                <svg v-else-if="type.warning" class="w-5 h-5 fill-current shrink-0" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                    <path d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24V296c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/>
                </svg>

                <svg v-else class="w-5 h-5 fill-current shrink-0" xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512">
                    <path d="M464 256A208 208 0 1 0 48 256a208 208 0 1 0 416 0zM0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256-96a96 96 0 1 1 0 192 96 96 0 1 1 0-192z"/>
                </svg>

                <div>
                    <h3 class="font-bold">
                        {{ alert.title }}
                    </h3>

                    <div class="text-xs">
                        {{ alert.message }}
                    </div>
                </div>

                <button v-if="alert.dismissable" @click="dismissAlertWithExpiry" class="btn btn-xs btn-secondary">
                    Dismiss
                </button>
            </div>
        </div>
    </Transition>
</template>
