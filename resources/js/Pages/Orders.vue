<script setup>
import { Head } from "@inertiajs/inertia-vue3";

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
});
</script>
<template>
    <div class="h-full overflow-y-auto p-1">
        <Head title="Orders" />
        <div
            v-for="order in orders.data"
            @click="$inertia.visit(route('orders.show', { order: order.id }))"
        >
            {{ order.id }} : {{ order.amount - order.discount }}
        </div>
    </div>
</template>
