<script setup>
import ActionSection from '@/Components/ActionSection.vue'
import Modal from '@/Components/Modal.vue'
import Specimen from './Specimen.vue'
import InputError from '@/Components/InputError.vue'
import preventTabClose from '@/Composables/preventTabClose'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'

const props = defineProps(['signature'])

const enabled = computed(() => Boolean(props.signature?.enabled))

const confirm = ref(false)

const modal = ref(false)

const fileInput = ref(null)

const uploadForm = useForm({
    samples: []
})

const deleteForm = useForm({})

const update = (data) => {
    useForm(data).put(route('signature.update', { signature: props.signature.id }), {
        preserveScroll: true,
        preserveState: true,
        only: ['errors', 'signature'],
    })
}

const enable = () => {
    if (props.signature) {
        update({ enabled: true })

        return
    }

    useForm({ enabled: true }).post(route('users.signature.store', usePage().props.user.id), {
        preserveScroll: true,
        preserveState: true,
        only: ['errors', 'signature'],
    })
}

const disable = () => {
    update({ enabled: false })
}

const toggle = (id, enabled) => {
    useForm({ enabled }).put(route('specimens.update', id), {
        preserveScroll: true,
        preserveState: true,
        only: ['errors', 'signature'],
    })
}

const upload = () => {
    uploadForm.post(route('signature.specimens.store', props.signature.id), {
        preserveScroll: true,
        preserveState: true,
        only: ['errors', 'signature'],
        onStart: () => uploadForm.clearErrors(),
        onSuccess: () => {
            clear()
            modal.value = false
        },
    })
}

const remove = (id) => {
    deleteForm.delete(route('specimens.destroy', id), {
        preserveScroll: true,
        preserveState: true,
        only: ['errors', 'signature'],
        onSuccess: () => confirm.value = false
    })
}

const clear = () => {
    uploadForm.samples = []

    fileInput.value.value = null

    uploadForm.clearErrors()
}

watch([confirm, modal], ([confirm, modal]) => {
    if (!confirm) {
        deleteForm.clearErrors()
    }

    if (!modal) {
        uploadForm.clearErrors()
    }
})

onMounted(() => router.reload({ only: ['signature'] }))

preventTabClose(() => uploadForm.processing)
</script>

<template>
    <ActionSection>
        <template #title>
            Signature Specimen
        </template>

        <template #description>
            Add or delete your signature specimen samples.
        </template>

        <template #content>
            <div class="flex flex-col max-w-xl gap-2 text-sm">
                <p>
                    Sign documents automatically with your electronic signature. Your signature specimen are <b class="text-warning">encrypted</b> for security where only you have access to these.
                </p>
                <p>
                    You may have to enable this feature first, if you want to use its functionality.
                </p>
            </div>

            <div v-if="enabled" class="flex flex-row flex-wrap gap-3 my-5 break-all">
                <div
                    v-for="specimen in signature?.specimens"
                    :key="specimen.id"
                    class="max-w-[15rem] gap-3 p-3 rounded-[--rounded-box] bg-base-300/50 justify-center flex flex-col text-center items-center"
                >
                    <div class="flex justify-between w-full gap-3">
                        <label class="p-0 space-x-2 cursor-pointer label">
                            <span class="label-text">Enabled</span>
                            <input
                                v-model="specimen.enabled"
                                type="checkbox"
                                class="toggle toggle-xs"
                                @change="toggle(specimen.id, specimen.enabled)"
                            >
                        </label>

                        <button @click="confirm = specimen.id" class="btn btn-xs btn-error">
                            Delete
                        </button>
                    </div>

                    <div class="rounded-[--rounded-box] overflow-hidden w-fit">
                        <Specimen :mime="specimen.mime" :sample="specimen.sample" />
                    </div>
                </div>
            </div>

            <div class="flex items-stretch gap-2 mt-5">
                <template v-if="enabled">
                    <button @click="modal = true" class="btn btn-primary btn-sm">
                        Upload
                    </button>

                    <button @click="disable" class="btn btn-error btn-sm">
                        Disable
                    </button>
                </template>

                <button v-else @click="enable" class="btn btn-primary btn-sm">
                    Enable
                </button>

                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <InputError class="flex items-center" :message="$page.props.errors?.enabled" />
                </Transition>
            </div>

            <Modal v-model="confirm">
                <template #header>
                    Confirm Deletion
                </template>

                Are you sure you want to delete this specimen sample?

                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                    <InputError class="flex items-center mt-1" :message="deleteForm.errors.delete" />
                </Transition>

                <template #action>
                    <button @click="confirm = false" class="btn btn-sm">
                        Cancel
                    </button>

                    <button @click="remove(confirm)" class="btn btn-sm btn-error">
                        Delete
                    </button>
                </template>
            </Modal>

            <Modal v-model="modal">
                <template #header>
                    Upload
                </template>

                <div class="grid gap-2">
                    <div class="grid gap-1">
                        <p>Guide:</p>

                        <ul class="px-4 text-sm tracking-tight list-disc">
                            <li>Minimum of three active samples required</li>
                            <li>Make sure the samples are clear and have transparent background</li>
                            <li>Maximum dimension is 2048px</li>
                            <li>Minimum dimension is 64px</li>
                        </ul>
                    </div>

                    <div class="form-control">
                        <label for="specimen-samples-upload" class="sr-only">
                            Specimens
                        </label>

                        <input
                            ref="fileInput"
                            id="specimen-samples-upload"
                            class="w-full file-input file-input-bordered file-input-sm"
                            type="file"
                            accept="image/webp, image/png"
                            multiple
                            @input="uploadForm.samples = $event.target.files"
                            :disabled="uploadForm.processing"
                        />

                        <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                            <div class="block" v-if="!!Object.keys(uploadForm.errors).length">
                                <label v-for="error in uploadForm.errors" class="block mt-1 text-sm text-error">
                                    {{ error }}
                                </label>
                            </div>
                        </Transition>
                    </div>
                </div>

                <template #action>
                    <button @click="clear" class="btn btn-sm" :disabled="uploadForm.processing">
                        Clear
                    </button>

                    <button @click="upload" class="btn btn-sm btn-primary" :disabled="uploadForm.processing">
                        Save
                    </button>
                </template>
            </Modal>
        </template>
    </ActionSection>
</template>
