<script setup>
import Button from "@/Components/Button.vue";
import QrcodeScanner from "@/Components/QrcodeScanner.vue";
import useConfirm from "@/Composables/confirm";
import { Head } from "@inertiajs/inertia-vue3";

const { confirm } = useConfirm();
const onScan = (decodedText, decodedResult) => {
    console.log(decodedText);
    console.log(decodedResult);
};
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
            Financial Summary
        </Button>
        <Button @click="$inertia.visit(route('routes.stock-summary'))">
            Stock Summary
        </Button>
        <Button @click="$inertia.visit(route('editPassword'))">
            Change Password
        </Button>
        <Button
            @click="
                () => {
                    confirm(() => {
                        $inertia.post(route('logout'));
                    }, 'Do you want to logout?');
                }
            "
            v-if="$page.props.auth.user"
        >
            Logout
        </Button>
        <QrcodeScanner @result="onScan" />
    </div>
</template>
