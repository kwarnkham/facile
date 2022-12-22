<script setup>
import Button from "@/Components/Button.vue";
import { Head } from "@inertiajs/inertia-vue3";
</script>

<template>
    <head title="Welcome" />
    <div class="flex flex-col h-full p-2 space-y-2">
        <div>1</div>
        <Button @click="$inertia.visit(route('items.create'))">Add item</Button>
        <Button @click="$inertia.visit(route('items.index'))">Item List</Button>
        <Button @click="$inertia.visit(route('orders.index'))">Orders</Button>
        <Button @click="$inertia.visit(route('payments.index'))">
            Payments
        </Button>
        <Button @click="$inertia.visit(route('expenses.create'))">
            Expenses
        </Button>
        <Button @click="$inertia.visit(route('purchases.index'))">
            Purchases
        </Button>
        <Button
            v-if="$page.props.auth.user"
            @click="$inertia.visit(route('features.all'))"
        >
            All Features
        </Button>
        <Button
            v-if="$page.props.auth.user"
            @click="$inertia.visit(route('orders.create'))"
        >
            Pre Order
        </Button>
        <Button @click="$inertia.visit(route('routes.financial-summary'))">
            Summary
        </Button>
        <Button
            @click="$inertia.post(route('logout'))"
            v-if="$page.props.auth.user"
        >
            Logout
        </Button>
    </div>
</template>
