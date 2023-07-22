<script setup>
import { Link, useForm, usePage } from '@inertiajs/inertia-vue3'
import { computed, watch } from 'vue'

const emits = defineEmits(['updated'])

const props = defineProps({
    items: Object,
    checkbox: Boolean,
    queryStrings: Object,
    links: String,
    compact: Boolean,
    wrapperClass: {
        type: String,
        default: 'h-[29.5em] mt-3 overflow-auto'
    },
    tableClass: {
        type: String,
        default: 'table table-zebra w-full'
    },
})

const update = (page) => {
    form.transform((data) => ({
        ...data,
        ...(page ? {page} : {}),
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

const form = useForm({
    search: usePage().props.value.search,
    paginate: usePage().props.value.paginate,
    page: ''
})

watch(() => props.queryStrings, () => update(), { deep: true })

defineExpose({processing: computed(() => form.processing)})
</script>

<template>
    <div class="grid grid-cols-12 gap-3 mb-8">
        <div class="col-span-12 md:col-span-6 form-control">
            <div class="w-full form-control">
                <label for="period" class="p-0 label">
                    <span class="label-text">Search</span>
                </label>
                <div class="w-full input-group" :class="{'input-group-sm' : compact}">
                    <input
                        type="text"
                        placeholder="Search…"
                        class="w-full input input-bordered"
                        :class="{'input-sm' : compact}"
                        v-model="form.search"
                        @keypress.enter="update"
                        :readonly="form.processing"
                    />
                    <button title="Search" class="btn btn-square" :class="{'btn-sm' : compact}" @click="update()" :disabled="form.processing">
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
        </div>
        <div class="order-first col-span-12 md:col-span-6 md:order-last">
            <slot name="actions"></slot>
        </div>
    </div>

    <div :class="wrapperClass">
        <table class="table-fixed table-pin-rows table-pin-cols" :class="`${tableClass}${compact ? ' table-compact text-sm' : ''}`">
            <thead>
                <slot name="head" :data="Object.keys(items?.data[0] ?? {})">
                    <tr>
                        <th class="sticky top-0" :class="{'py-2 px-4': compact, 'mx-4' : ! compact}">
                            <slot name="preColHead">
                                #
                            </slot>
                        </th>
                        <template v-for="(v, k) in items?.data[0]">
                            <th class="sticky top-0" v-if="k !== 'id'" :class="{'py-1' : compact}">
                                {{ k }}
                            </th>
                        </template>
                        <slot name="postColHead" />
                    </tr>
                </slot>
            </thead>

            <tbody>
                <template v-for="item in items.data">
                    <slot :row="item">
                        <tr class="hover" v-for="(item, index) in items?.data">
                            <th :class="{ 'p-0': links }" :key="item.key" style="z-index: 0 !important">
                                <slot name="preColCell" :id="item.id" :index="(items.current_page - 1) * items.per_page + index + 1">
                                    <template v-if="links">
                                        <Link :title="item.id" class="block w-full px-4 py-2" :href="links.replace('{id}', item.id)">
                                            {{ (items.current_page - 1) * items.per_page + index + 1 }}
                                        </Link>
                                    </template>
                                    <template v-else>
                                        {{ (items.current_page - 1) * items.per_page + index + 1 }}
                                    </template>
                                </slot>
                            </th>

                            <template v-for="(data, key) in item">
                                <td v-if="key != 'id'" :key="`${item.key}-${key}`" :class="{ 'p-0': links}">
                                    <template v-if="links">
                                        <Link :title="item.id" class="block w-full" :href="links.replace('{id}', item.id)" :class="{'px-4 py-2': ! compact, 'px-2': compact}">
                                            {{
                                                `${
                                                    (
                                                        string = (
                                                            typeof data == 'string' || data instanceof String
                                                                ? data
                                                                : JSON.stringify(data)?.substring(0, 60)
                                                        )
                                                    ) ? string : '&nbsp;'
                                                }${
                                                    (typeof data == 'string' || data instanceof String
                                                        ? data
                                                        : JSON.stringify(data)
                                                    )?.length > 60
                                                        ? '...'
                                                        : ''
                                                }`
                                            }}
                                        </Link>
                                    </template>

                                    <template v-else>
                                        {{
                                            `${
                                                typeof data == 'string' || data instanceof String
                                                    ? data
                                                    : JSON.stringify(data)?.substring(0, 60)
                                            }${
                                                (typeof data == 'string' || data instanceof String
                                                    ? data
                                                    : JSON.stringify(data)
                                                )?.length > 60
                                                    ? '...'
                                                    : ''
                                            }`
                                        }}
                                    </template>
                                </td>
                            </template>
                            <slot name="postColumnCell" :id="item.id" :index="(items.current_page - 1) * items.per_page + index + 1" />
                        </tr>
                        <tr v-if="items?.data.length === 0">
                            <td colspan="1" class="px-6">We've come up empty!</td>
                        </tr>
                    </slot>
                </template>
            </tbody>
        </table>
    </div>

    <div class="my-3 text-center">
        <div class="grid grid-cols-3 gap-3">
            <div class="inline-block w-full col-span-3 form-control sm:col-span-1">
                <div class="justify-center hidden sm:flex input-group">
                    <select aria-label="Paginate value" class="w-24 select select-bordered" :class="{'select-sm': compact}" v-model="form.paginate">
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
                        <option>200</option>
                        <option>500</option>
                        <option>1000</option>
                    </select>
                    <button class="w-24 btn" :class="{'btn-sm': compact}" @click="update()" :disabled="form.processing">Paginate</button>
                </div>

                <div class="justify-center sm:hidden input-group">
                    <button class="w-24 btn" :class="{'btn-sm': compact}" @click="update()" :disabled="form.processing">Paginate</button>
                    <select aria-label="Paginate value" class="w-24 select select-bordered" :class="{'select-sm': compact}" v-model="form.paginate">
                        <option>25</option>
                        <option>50</option>
                        <option>100</option>
                        <option>200</option>
                        <option>500</option>
                        <option>1000</option>
                    </select>
                </div>
            </div>

            <slot name="links">
                <div class="justify-center w-full col-span-3 btn-group sm:col-span-1">
                    <button
                        @click="update(1)" class="btn"
                        :class="{'btn-sm': compact}"
                        :disabled="form.processing || items?.current_page <= 1"
                    >
                        ⇚
                    </button>
                    <button
                        @click="update(isNaN(items?.current_page) ? 0 : items?.current_page - 1)"
                        :class="{'btn-sm': compact}" class="btn"
                        :disabled="form.processing || isNaN(items?.current_page) || items?.current_page <= 1"
                    >
                        ⇐
                    </button>
                    <button
                        class="btn bg-primary-content text-primary btn-square"
                        disabled :class="{'btn-sm': compact}"
                    >
                        {{ items?.current_page }}
                    </button>
                    <button
                        @click="update(isNaN(items?.current_page) ? 0 : items?.current_page + 1)"
                        :class="{'btn-sm': compact}" class="btn"
                        :disabled="form.processing || isNaN(items?.current_page) || items?.current_page >= items?.last_page"
                    >
                        ⇒
                    </button>
                    <button
                        @click="update(isNaN(items?.current_page) ? 0 : items?.last_page)"
                        :class="{'btn-sm': compact}" class="btn"
                        :disabled="form.processing || isNaN(items?.current_page) || items?.current_page >= items?.last_page"
                    >
                        ⇛
                    </button>
                </div>
            </slot>

            <div class="inline-block w-full col-span-3 form-control sm:col-span-1">
                <div class="justify-center input-group" :class="{'input-group-sm': compact}">
                    <button class="w-24 btn" :class="{'btn-sm': compact}" @click="update()" :disabled="form.processing">Jump&nbsp;to</button>
                    <input type="number" class="w-24 input input-bordered" :class="{'input-sm': compact}" v-model="form.page" @keypress.enter="update()" :readonly="form.processing"/>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-1 text-center md:mt-3">
        Showing {{ items?.from }} to {{ items?.to }} of {{ items?.total }} results | Page {{ items?.current_page }} of
        {{ items?.last_page }}
    </div>
</template>

<style scoped>
:where(.table *:first-child) :where(*:first-child) :where(th, td):first-child {
    border-top-left-radius: 0.2rem;
}
:where(.table *:first-child) :where(*:first-child) :where(th, td):last-child {
    border-top-right-radius: 0.2rem;
}
:where(.table *:last-child) :where(*:last-child) :where(th, td):first-child {
    border-bottom-left-radius: 0.2rem;
}
:where(.table *:last-child) :where(*:last-child) :where(th, td):last-child {
    border-bottom-right-radius: 0.1rem;
}
</style>
