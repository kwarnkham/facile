<script setup>
defineProps({
    type: {
        type: String,
        default: "button",
    },
});
</script>

<template>
    <button
        :type="type"
        class="daisy-btn daisy-btn-primary daisy-btn-sm capitalize"
    >
        <slot />
    </button>
</template>
