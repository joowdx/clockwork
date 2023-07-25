import { onBeforeMount, onBeforeUnmount } from "vue"

export default function preventTabClose(preventOn, message = "Are you sure you want to leave? Changes you made may not be saved.") {
    const preventTabClose = (event) => {
        if (preventOn()) {
            event.preventDefault()
            event.returnValue = message
        }
    }

    onBeforeMount(() => window.addEventListener("beforeunload", preventTabClose))

    onBeforeUnmount(() => window.removeEventListener("beforeunload", preventTabClose))
}
