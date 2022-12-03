<script setup>
import Button from "@/Components/Button.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { onMounted } from "vue";
import usePrinter from "@/Composables/printer";

onMounted(() => {});
const { sendPrinterData } = usePrinter(400, 40);

const print = () => {
    const el = document.getElementById("foo");
    sendPrinterData(el);
};
</script>

<template>
    <head title="Welcome" />
    <div class="flex flex-col h-full p-2 space-y-2">
        <button @click="$inertia.visit(route('items.create'))">Add item</button>
        <button @click="$inertia.visit(route('items.index'))">Item List</button>
        <button @click="$inertia.visit(route('orders.index'))">Orders</button>
        <button @click="$inertia.visit(route('merchant_payments.index'))">
            Payments
        </button>
        <button
            @click="$inertia.post(route('logout'))"
            v-if="$page.props.auth.user"
        >
            Logout
        </button>
        <button @click="print">print</button>
        <div
            id="foo"
            style="width: 375px; font-size: 18px"
            class="pr-4 text-right py-2"
        >
            စိုင်းကွမ်းခမ်း yhyhyh
        </div>
    </div>
</template>
