<script setup>
import { ref, watch } from "vue";

const message = ref("");
const props = defineProps(["flash"]);
watch(
    () => props.flash,
    () => {
        message.value = props.flash.error || props.flash.message;
        setTimeout(() => {
            message.value = "";
        }, 2000);
    },
    { deep: true }
);
</script>

<template>
    <div class="h-screen w-screen flex flex-col relative" data-theme="garden">
        <slot />
        <div
            class="absolute bottom-4 right-4 rounded-md bg-secondary-focus text-secondary-content px-2 py-1 text-sm font-semibold shadow-md"
            v-if="message"
        >
            {{ message }}
        </div>
    </div>
</template>
