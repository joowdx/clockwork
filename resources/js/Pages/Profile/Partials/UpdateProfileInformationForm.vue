<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            Profile Information
        </template>

        <template #description>
            Update your account's signatory information.
        </template>

        <template #form>
            <!-- Profile Photo -->
            <div class="col-span-6 sm:col-span-4" v-if="$page.props.jetstream.managesProfilePhotos">
                <!-- Profile Photo File Input -->
                <input type="file" class="hidden"
                            ref="photo"
                            @change="updatePhotoPreview">

                <label for="photo" class="block text-sm font-medium text-base-content"> Photo </label>

                <!-- Current Profile Photo -->
                <div class="mt-2" v-show="! photoPreview">
                    <img :src="user.profile_photo_url" :alt="user.name" class="object-cover w-20 h-20 rounded-full">
                </div>

                <!-- New Profile Photo Preview -->
                <div class="mt-2" v-show="photoPreview">
                    <span class="block w-20 h-20 bg-center bg-no-repeat bg-cover rounded-full"
                          :style="'background-image: url(\'' + photoPreview + '\');'">
                    </span>
                </div>

                <button class="mt-2 mr-2 btn btn-secondary btn-sm" type="button" @click.prevent="selectNewPhoto">
                    Select A New Photo
                </button>

                <button type="button btn btn-secondary btn-sm" class="mt-2" @click.prevent="deletePhoto" v-if="user.profile_photo_path">
                    Remove Photo
                </button>

                <InputError class="mt-0.5" :message="form.errors.photo" />
            </div>

            <!-- Username -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label for="username" class="block text-sm font-medium text-base-content"> Username </label>
                <input v-model="form.username" id="username" type="text" class="mt-1 input input-bordered" readonly />
                <InputError class="mt-0.5" :message="form.errors.username" />
            </div>

            <!-- Name -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label for="name" class="block text-sm font-medium text-base-content"> Name </label>
                <input v-model="form.name" id="name" type="text" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors.name" />
            </div>

            <!-- Title -->
            <div class="col-span-6 form-control sm:col-span-4">
                <label for="title" class="block text-sm font-medium text-base-content"> Title </label>
                <input v-model="form.title" id="title" type="text" class="mt-1 input input-bordered" />
                <InputError class="mt-0.5" :message="form.errors.title" />
            </div>
        </template>

        <template #actions>
            <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                <p v-if="form.recentlySuccessful" class="mr-3 text-sm opacity-50 text-base-content">Saved.</p>
            </Transition>

            <button class="btn btn-primary" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </button>
        </template>
    </FormSection>
</template>

<script>
import { defineComponent } from 'vue'
import FormSection from '@/Components/FormSection.vue'
import InputError from '@/Components/InputError.vue'

export default defineComponent({
    components: {
        FormSection,
        InputError,
    },

    props: ['user'],

    data() {
        return {
            form: this.$inertia.form({
                _method: 'PUT',
                username: this.user.username,
                name: this.user.name,
                title: this.user.title,
                type: this.user.type,
                photo: null,
            }),

            photoPreview: null,
        }
    },

    methods: {
        updateProfileInformation() {
            if (this.$refs.photo) {
                this.form.photo = this.$refs.photo.files[0]
            }

            this.form.post(route('user-profile-information.update'), {
                errorBag: 'updateProfileInformation',
                preserveScroll: true,
                onSuccess: () => (this.clearPhotoFileInput()),
            });
        },

        selectNewPhoto() {
            this.$refs.photo.click();
        },

        updatePhotoPreview() {
            const photo = this.$refs.photo.files[0];

            if (! photo) return;

            const reader = new FileReader();

            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };

            reader.readAsDataURL(photo);
        },

        deletePhoto() {
            this.$inertia.delete(route('current-user-photo.destroy'), {
                preserveScroll: true,
                onSuccess: () => {
                    this.photoPreview = null;
                    this.clearPhotoFileInput();
                },
            });
        },

        clearPhotoFileInput() {
            if (this.$refs.photo?.value) {
                this.$refs.photo.value = null;
            }
        },
    },
})
</script>
