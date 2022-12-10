<script setup>
import { Head } from "@inertiajs/inertia-vue3";

defineProps({
    summary: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Financial Summery" />
</template>
