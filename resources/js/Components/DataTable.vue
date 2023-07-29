<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3'
import { computed, onMounted, ref, watch } from 'vue'

const table = ref(null)

defineExpose({ processing: computed(() => form.processing) })

const emits = defineEmits(['updated'])

const props = defineProps({
    items: Object,
    queryStrings: Object,
    links: String,
    wrapperClass: String,
    autofocus: {
        type: Boolean,
        default: false
    }
})

const form = useForm({
    search: usePage().props.search,
    paginate: usePage().props.paginate,
    page: ''
})

const update = () => {
    if (props.items == undefined) {
        return
    }

    form.transform((data) => ({
        ...data,
        ...(props.queryStrings ?? {})
    })).get(props.items.path, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            form.page = ''
            emits('updated')
        }
    })
}

const jump = (page = 1) => {
    form.page = page
    update()
}

const formatData = (data) => {
    if (typeof data == 'string' || data instanceof String) {
        return data ? data : '&nbsp;'
    }

    data = JSON.stringify(data)?.substring(0, 60)

    return data + (data.length > 60 ? '...' : '')
}

const isCompact = () => table.value?.classList.contains('table-sm') || table.value?.classList.contains('table-xs')

const input = ref(null)

watch(
    () => props.queryStrings,
    () => update(),
    { deep: true }
)

onMounted(() => {
    if (props.autofocus) {
        setTimeout(() => input.value.focus(), 200)
    }
})
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-col-reverse justify-between gap-3 sm:flex-row" :class="[isCompact() ? 'mb-4' : 'mb-8']">
            <div class="w-full max-w-md form-control">
                <label for="period" class="p-0 label">
                    <span class="label-text">Search</span>
                </label>
                <div class="join" :class="{ 'input-group-sm': isCompact() }">
                    <input
                        ref="input"
                        type="search"
                        placeholder="Search…"
                        class="w-full input input-bordered join-item"
                        :class="{ 'input-sm': isCompact() }"
                        v-model="form.search"
                        @keypress.enter="update"
                        :readonly="form.processing"
                    />
                    <button
                        :class="{ 'btn-sm': isCompact() }"
                        class="btn btn-square join-item btn-primary"
                        @click="update"
                        :disabled="form.processing"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-6 h-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </button>
                </div>
            </div>
            <slot name="actions"></slot>
        </div>

        <div class="p-4 mt-4 bg-base-300/50 rounded-[--rounded-box]">
            <slot name="pre"></slot>

            <div class="overflow-y-scroll" :class="[wrapperClass, isCompact() ? 'h-[34.5em]' : 'h-[28.75em]']">
                <table ref="table" class="table w-full table-zebra table-pin-rows" v-bind="$attrs">
                    <thead>
                        <slot name="head">
                            <tr>
                                <th>#</th>
                                <template v-for="(v, k) in items?.data[0]">
                                    <th class="capitalize" v-if="k !== 'id'">
                                        {{ k }}
                                    </th>
                                </template>
                            </tr>
                        </slot>
                    </thead>

                    <tbody>
                        <slot :row="item" :index="index + items?.from" v-for="(item, index) in items?.data">
                            <tr class="hover" >
                                <th :class="{ 'p-0': links }">
                                    <template v-if="links">
                                        <Link
                                            class="block w-full"
                                            :class="[isCompact() ? 'py-2 px-4' : 'p-4']"
                                            :href="links.replace('{id}', item.id)"
                                        >
                                            {{ (items.current_page - 1) * items.per_page + index + 1 }}
                                        </Link>
                                    </template>
                                    <template v-else>
                                        {{ (items.current_page - 1) * items.per_page + index + 1 }}
                                    </template>
                                </th>
                                <template v-for="(data, key) in item">
                                    <td v-if="key != 'id'" :class="{ 'p-0': links }">
                                        <template v-if="links">
                                            <Link
                                                class="block w-full h-full"
                                                :class="[isCompact() ? 'py-2 px-4' : 'p-4']"
                                                :href="links.replace('{id}', item.id)"
                                                v-html="formatData(data)"
                                            />
                                        </template>

                                        <template v-else>
                                            {{ formatData(data) }}
                                        </template>
                                    </td>
                                </template>
                            </tr>
                            <tr v-if="items?.data.length === 0">
                                <td colspan="4">We've come up empty!</td>
                            </tr>
                        </slot>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="text-center" :class="[isCompact() ? 'my-3' : 'my-8']">
            <div class="justify-center gap-3 space-y-3 md:space-y-0 md:flex">
                <div class="inline-block w-full md:w-fit">
                    <div class="justify-center join">
                        <button
                            class="w-24 btn join-item"
                            :class="{ 'btn-sm': isCompact() }"
                            @click="update"
                            :disabled="form.processing"
                        >
                            Paginate
                        </button>
                        <select
                            class="w-24 select select-bordered join-item"
                            :class="{ 'select-sm': isCompact() }"
                            v-model="form.paginate"
                            :disabled="form.processing"
                        >
                            <option>25</option>
                            <option>50</option>
                            <option>100</option>
                            <option>200</option>
                        </select>
                    </div>
                </div>

                <slot name="links">
                    <div class="justify-center w-full join md:w-fit">
                        <button
                            class="btn join-item"
                            :class="{ 'btn-sm': isCompact() }"
                            @click="jump()"
                            :disabled="form.processing || (items?.current_page == 1 ? true : null)"
                            preserve-scroll
                            preserve-state
                        >
                            ⇚
                        </button>
                        <button
                            class="btn join-item"
                            :class="{ 'btn-sm': isCompact() }"
                            @click="jump(items?.current_page - 1)"
                            :disabled="form.processing || (items?.current_page == 1 ? true : null)"
                            preserve-scroll
                            preserve-state
                        >
                            ⇐
                        </button>
                        <button
                            class="btn btn-square disabled:text-base-content join-item"
                            disabled
                            :class="{ 'btn-sm': isCompact() }"
                        >
                            {{ items?.current_page }}
                        </button>
                        <button
                            class="btn join-item"
                            :class="{ 'btn-sm': isCompact() }"
                            @click="jump(items?.current_page + 1)"
                            :disabled="form.processing || (items?.current_page == items?.last_page ? true : null)"
                            preserve-scroll
                            preserve-state
                        >
                            ⇒
                        </button>
                        <button
                            class="btn join-item"
                            :class="{ 'btn-sm': isCompact() }"
                            @click="jump(items?.last_page)"
                            :disabled="form.processing || (items?.current_page == items?.last_page ? true : null)"
                            preserve-scroll
                            preserve-state
                        >
                            ⇛
                        </button>
                    </div>
                </slot>

                <div class="inline-block w-full form-control md:w-fit">
                    <div class="justify-center join">
                        <button
                            class="w-24 btn join-item"
                            :class="{ 'btn-sm': isCompact() }"
                            @click="update"
                            :disabled="form.processing"
                        >
                            Jump&nbsp;to
                        </button>
                        <input
                            type="number"
                            class="w-24 input input-bordered join-item"
                            :class="{ 'input-sm': isCompact() }"
                            v-model="form.page"
                            @keypress.enter="update"
                            :readonly="form.processing"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-1 text-center md:mt-3" :class="{ 'text-sm': isCompact() }">
            Showing {{ items?.from ?? 0 }} to {{ items?.to }} of {{ items?.total }} results | Page
            {{ items?.current_page }} of {{ items?.last_page }}
        </div>
    </div>
</template>

<style scoped>
.table :where(thead, tbody) :where(tr:not(:last-child)), .table :where(thead, tbody) :where(tr:first-child:last-child) {
    border-bottom: none;
}
</style>

<style>
.table tr.active, .table tr.active:nth-child(even), .table-zebra tbody tr:nth-child(even) {
    --tw-bg-opacity: 0.5 !important;
    background-color: hsl(var(--b2) / var(--tw-bg-opacity)) !important;
}
</style>
