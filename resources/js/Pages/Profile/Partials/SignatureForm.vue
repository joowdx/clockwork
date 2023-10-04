<script setup>
import ActionSection from '@/Components/ActionSection.vue'
import Modal from '@/Components/Modal.vue'
import { router, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref } from 'vue'
import Specimen from './Specimen.vue';

const props = defineProps(['signature'])

const enabled = computed(() => Boolean(props.signature?.enabled))

const confirm = ref(false)

const modal = ref(false)

const fileInput = ref(null)

const uploadForm = useForm({
    samples: []
})

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
    })
}

const remove = (id) => {
    useForm({}).delete(route('specimens.destroy', id), {
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

onMounted(() => router.reload({ only: ['signature'] }))
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
                    Please ensure that the sample file you upload has a transparent background and is in PNG or WebP format. For security, these specimen samples will be encrypted on our servers.
                </p>
                <p>
                    You may have to enable this feature first, if you want to use its functionality.
                </p>
            </div>

            <div v-if="enabled" class="flex flex-row flex-wrap gap-3 my-5 break-all">
                <div
                    v-for="specimen in signature?.specimens"
                    class="max-w-[15rem] gap-3 p-3 rounded-[--rounded-box] bg-base-100/50 justify-center flex flex-col text-center items-center"
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

                    <p class="font-mono text-xs">
                        Checksum: {{ specimen.checksum }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2 mt-5">
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
            </div>

            <Modal v-model="confirm">
                <template #header>
                    Confirm Deletion
                </template>

                Are you sure you want to delete this specimen sample?

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

                <div class="form-control">
                    <label for="specimen-samples-upload" class="px-0 label">
                        <span class="label-text">
                            Specimens
                        </span>
                    </label>

                    <input
                        ref="fileInput"
                        id="specimen-samples-upload"
                        class="w-full file-input file-input-bordered file-input-sm"
                        type="file"
                        accept="image/webp, image/png"
                        multiple
                        @input="uploadForm.samples = $event.target.files"
                    />

                    <label v-for="error in uploadForm.errors" class="mt-1 text-sm text-error">
                        {{ error }}
                    </label>
                </div>

                <template #action>
                    <button @click="clear" class="btn btn-sm" >
                        Clear
                    </button>

                    <button @click="upload" class="btn btn-sm btn-primary">
                        Save
                    </button>
                </template>
            </Modal>
        </template>
    </ActionSection>
</template>
