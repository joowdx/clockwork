<template>
    <listbox v-model="selected" class="p-0 border-none">
        <div class="relative mt-1">
            <listbox-button class="relative w-full py-2 pl-3 pr-10 text-left bg-white border border-gray-300 rounded-md shadow-sm cursor-default dark:bg-gray-800 dark:focus:border-gray-800 dark:focus:ring-gray-700 dark:border-gray-700 focus:outline-none focus:border-indigo-300 focus:ring focus:ring-indigo-200 sm:text-sm" style="height:42px!important;">
                <span class="block capitalize truncate" v-html="selected.name ?? selected"></span>

                <span class="absolute inset-y-0 right-0 flex items-center pr-2 ml-3 pointer-events-none">
                    <selector-icon class="w-5 h-5 text-gray-400" aria-hidden="true" />
                </span>
            </listbox-button>

            <transition leave-active-class="transition duration-100 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <listbox-options class="absolute z-10 w-full py-1 mt-1 overflow-auto text-base bg-white rounded-md shadow-lg dark:bg-gray-800 max-h-56 ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                    <listbox-option as="template" v-for="item in items" :key="item.value ?? item" :value="item" v-slot="{ active, selected }" >
                        <li :hidden="item ? false : true" :class="[active ? 'bg-indigo-400 dark:bg-gray-700 dark:text-gray-200' : 'text-gray-900', ' select-none relative py-2 pl-3 pr-9']">
                            <span :class="[selected ? 'font-bold' : 'font-normal', 'block truncate capitalize dark:text-gray-200']" v-html="item.name ?? item"> </span>

                            <span v-if="selected" :class="[active ? 'text-white' : 'text-indigo-400 dark:text-gray-200', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                                <check-icon class="w-5 h-5" aria-hidden="true" />
                            </span>
                        </li>
                    </listbox-option>
                </listbox-options>
            </transition>
        </div>
    </listbox>
</template>

<script>
    import { defineComponent, ref } from 'vue'
    import { Listbox, ListboxButton, ListboxLabel, ListboxOption, ListboxOptions } from '@headlessui/vue'
    import { CheckIcon, SelectorIcon } from '@heroicons/vue/solid'

    export default defineComponent({
        components: {
            Listbox,
            ListboxButton,
            ListboxLabel,
            ListboxOption,
            ListboxOptions,
            CheckIcon,
            SelectorIcon,
        },

        props: ['options', 'modelValue'],

        data: function() {
            return {
                items: this.options ?? [''],

                selected: this.findNewSelectedFromOptions(),
            }
        },

        methods: {
            findNewSelectedFromOptions () {
                let items = this.items ?? this.options ?? ['']

                let selectedIndex = items.findIndex(e => (e.value ?? e) == this.modelValue)

                return selectedIndex != -1 ? ref(items[selectedIndex]) : '&nbsp;'
            },
        },

        watch: {
            selected: function() {
                let newValue = this.selected.value ?? this.selected

                this.$emit('update:modelValue', newValue == '&nbsp;' ? null : newValue)
            },

            modelValue: function() {
                if(this.modelValue == this.selected.value ?? this.selected) {
                    return
                }

                this.selected = this.findNewSelectedFromOptions()
            }
        },
    })
</script>
