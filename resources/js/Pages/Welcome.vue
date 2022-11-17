<script setup>
import Button from "@/Components/Button.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
</script>

<template>
    <Head title="Welcome" />
    <div class="flex flex-col h-full p-2 space-y-2">
        <Button @click="$inertia.visit(route('items.create'))">Add item</Button>
        <Button @click="$inertia.visit(route('items.index'))">Item List</Button>
        <Button @click="$inertia.visit(route('orders.index'))">Orders</Button>
        <Button @click="$inertia.visit(route('merchant_payments.index'))"
            >Payments</Button
        >
        <Button
            @click="$inertia.post(route('logout'))"
            v-if="$page.props.auth.user"
            >Logout</Button
        >
    </div>
</template>
