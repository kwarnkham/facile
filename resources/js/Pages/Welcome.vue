<script setup>
import Button from "@/Components/Button.vue";
import { Head } from "@inertiajs/inertia-vue3";
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
        <button @click="$inertia.visit(route('expenses.create'))">
            Expenses
        </button>
        <button @click="$inertia.visit(route('purchases.index'))">
            Purchases
        </button>
        <button
            @click="$inertia.post(route('logout'))"
            v-if="$page.props.auth.user"
        >
            Logout
        </button>
    </div>
</template>
