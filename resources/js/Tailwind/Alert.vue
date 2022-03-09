<template>
    <div v-if="open" class="relative px-6 py-4 mb-4 text-white border-0 rounded" :class="bg">
        <div class="flex">
            <span class="inline-block mr-5 text-xl align-middle">
                <svg v-if="type == 'success'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-if="type == 'notice'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <svg v-if="type == 'info'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <svg v-if="type == 'error'" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>

            <span class="inline-block mr-8 align-middle">
                <b class="capitalize">{{ title }}!</b>
                <br>{{ message }}
            </span>
        </div>

        <button class="absolute top-0 right-0 mt-4 mr-6 text-2xl font-semibold leading-none bg-transparent outline-none focus:outline-none" v-on:click="closeAlert()">
            <span>×</span>
        </button>
    </div>
</template>

<script>
    export default {
        props: {
            type: {
                type: String,
                default: 'Success'
            },
            title: {
                type: String,
                default: 'Success',
            },
            message: {
                type: String,
                default: null,
            }
        },

        data() {
            return {
                open: true,
            }
        },

        computed: {
            bg() {
                return {
                    'bg-red-400': this.type == 'error',
                    'bg-orange-500': this.type == 'notice',
                    'bg-gray-800': this.type == 'info',
                    'bg-indigo-500': this.type == 'success',
                }
            }
        },

        methods: {
            closeAlert: function(){
                this.open = false;
            }
        }
    }
</script>
