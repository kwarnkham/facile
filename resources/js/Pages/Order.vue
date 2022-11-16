<script setup>
import { Head } from "@inertiajs/inertia-vue3";

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});
</script>
<template>
    <div class="h-full overflow-y-auto p-1 flex flex-col">
        <Head title="Order" />
        <div>
            <div>Name : {{ order.customer }}</div>
            <div>Phone : {{ order.phone }}</div>
            <div v-if="order.address">Address : {{ order.address }}</div>
            <div>
                {{
                    new Date(order.created_at)
                        .toLocaleString("en-GB", {
                            hour12: true,
                        })
                        .toUpperCase()
                }}
            </div>
            <div>
                Status: {{ order.status }}
                {{
                    new Date(order.updated_at)
                        .toLocaleString("en-GB", {
                            hour12: true,
                        })
                        .toUpperCase()
                }}
            </div>
            <div v-if="order.note">Note: {{ order.note }}</div>
        </div>
        <table class="daisy-table daisy-table-compact w-full daisy-table-zebra">
            <thead class="sticky top-0">
                <tr>
                    <th></th>
                    <th>Name</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr
                    v-for="(feature, index) in order.features"
                    :key="feature.id"
                >
                    <th>{{ index + 1 }}</th>
                    <td>{{ feature.name }}</td>
                    <td class="text-right">
                        {{ feature.pivot.price.toLocaleString() }}
                        <strong v-if="feature.pivot.discount">
                            ({{ feature.pivot.discount }})
                        </strong>
                    </td>
                    <td class="text-right">
                        {{ feature.pivot.quantity }}
                    </td>
                    <td class="text-right">
                        {{
                            (
                                feature.pivot.quantity *
                                Math.floor(
                                    feature.pivot.price - feature.pivot.discount
                                )
                            ).toLocaleString()
                        }}
                    </td>
                </tr>
                <tr class="font-bold">
                    <th class="underline"></th>
                    <td colspan="2">Total</td>
                    <td class="text-right">
                        {{
                            order.features.reduce(
                                (carry, e) => e.pivot.quantity + carry,
                                0
                            )
                        }}
                    </td>
                    <td class="text-right">
                        {{
                            order.features
                                .reduce(
                                    (carry, e) =>
                                        e.pivot.quantity *
                                            Math.floor(
                                                e.pivot.price - e.pivot.discount
                                            ) +
                                        carry,
                                    0
                                )
                                .toLocaleString()
                        }}
                    </td>
                </tr>
                <tr class="font-bold">
                    <td colspan="4" class="text-right">Discount</td>
                    <td class="text-right">
                        {{ order.discount.toLocaleString() }}
                    </td>
                </tr>
                <tr class="font-bold">
                    <td colspan="4" class="text-right">Deposit</td>
                    <td class="text-right">
                        {{ order.deposit.toLocaleString() }}
                    </td>
                </tr>
                <tr class="font-bold">
                    <td colspan="4" class="text-right">Amount</td>
                    <td class="text-right">
                        {{
                            (
                                order.amount -
                                order.deposit -
                                order.discount
                            ).toLocaleString()
                        }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
th {
    text-transform: capitalize;
}
td {
    white-space: normal;
}
.daisy-table th:first-child {
    position: static;
}
</style>
