<template>
    <app-layout title="Biometrics">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                Scanners <small class="uppercase font-extralight"> ({{ $page.props.user.username }}) </small>
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 bg-gray">
                <div class="flex flex-col">
                    <div class="flex items-center justify-end px-6 mb-5 space-x-3 sm:px-0">
                        <jet-input type="text" class="block w-full disabled:opacity-60" v-model="search" style="padding: .25rem .5em!important" autocomplete="name" placeholder="Search" />
                        <Link class="flex-none" :href="route('scanners.create')">
                            <jet-secondary-button>
                                Add New Scanner
                            </jet-secondary-button>
                        </Link>
                    </div>
                </div>
                <div v-for="scanner in scanners" :key="scanner.id">
                    {{ scanner.username }}
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
    import { defineComponent } from 'vue'
    import { Link } from '@inertiajs/inertia-vue3';
    import AppLayout from '@/Layouts/AppLayout.vue'
    import JetSecondaryButton from '@/Jetstream/SecondaryButton.vue'
    import JetInput from '@/Jetstream/Input.vue'

    export default defineComponent({
        components: {
            Link,
            AppLayout,
            JetInput,
            JetSecondaryButton,
        },

        props: {
            'scanners': Array,
        },

        data: function() {
            return {
                search: this.$page.props.search,
                active: [],
            };
        },

        watch: {
            search: _.debounce(function(search) {
                this.$inertia.get(route('scanner.index'), search ? { search: search } : {}, {
                    preserveState: true,
                    preserveScroll: true,
                })
            }, 500)
        },
    })
</script>
