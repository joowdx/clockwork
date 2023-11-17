import { ref } from "vue"

export const toasts = ref([])

export default function sendToast(type, title, message) {
    toasts.value.push({ type, title, message })
}
