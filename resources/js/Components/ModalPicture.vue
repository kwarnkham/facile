<script setup>
import { XMarkIcon } from "@heroicons/vue/24/solid";
defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    src: {
        type: String,
        required: true,
    },
});

defineEmits(["closed"]);
</script>

<template>
    <div class="daisy-modal" :class="{ 'daisy-modal-open': open }">
        <div
            class="daisy-modal-box w-screen max-w-screen h-screen max-h-screen rounded-none flex justify-center items-center p-0 overflow-y-auto"
        >
            <img :src="src" :alt="src" />
        </div>
    </div>
    <XMarkIcon
        style="z-index: 999"
        class="opacity-0 fixed w-6 h-6 bg-white rounded-md top-2 right-2 transition-opacity duration-75"
        @click="$emit('closed')"
        :class="{ 'opacity-100 delay-100': open }"
    />
</template>
