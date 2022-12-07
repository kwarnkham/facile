<script setup>
import { Head } from "@inertiajs/inertia-vue3";

const props = defineProps({
    purchases: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Purchases" />
    <div>
        <div v-for="purchase in purchases" :key="purchase">
            {{ purchase.id }}: {{ purchase.price }}
        </div>
    </div>
</template>
