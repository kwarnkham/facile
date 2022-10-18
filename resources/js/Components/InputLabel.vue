<script setup>
defineProps(["value"]);
</script>

<template>
    <label class="daisy-label daisy-label-text">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
