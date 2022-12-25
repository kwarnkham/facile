<script setup>
import DateRangeSearch from "@/Components/DateRangeSearch.vue";
import { Head } from "@inertiajs/inertia-vue3";

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Financial Summary" />
    <div class="p-1">
        <DateRangeSearch
            :url="route('routes.financial-summary')"
            :from="filters.from"
            :to="filters.to"
        />
        <div class="text-center text-lg font-bold">Financial Summary</div>
        <div class="flex flex-row justify-between">
            <span>Completed Orders :</span>
            <span
                >{{
                    summary.completed_orders
                        .reduce(
                            (carry, e) => carry + (e.amount - e.discount),
                            0
                        )
                        .toLocaleString()
                }}
                MMK</span
            >
        </div>
        <div class="flex flex-row justify-between">
            <span> Purchases :</span>
            <span
                >{{
                    summary.purchases
                        .reduce((carry, e) => carry + e.price * e.quantity, 0)
                        .toLocaleString()
                }}
                MMK</span
            >
        </div>
        <div class="daisy-divider"></div>
        <div class="flex flex-row justify-between">
            <span> Net Total :</span>
            <span
                >{{
                    (
                        summary.completed_orders.reduce(
                            (carry, e) => carry + (e.amount - e.discount),
                            0
                        ) -
                        summary.purchases.reduce(
                            (carry, e) => carry + e.price * e.quantity,
                            0
                        )
                    ).toLocaleString()
                }}
                MMK</span
            >
        </div>
    </div>
</template>
