<script setup>
import ToppingRow from "./ToppingRow.vue";

defineProps({
    order: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <table class="daisy-table daisy-table-compact w-full daisy-table-zebra">
        <thead class="sticky top-0">
            <tr>
                <th></th>
                <th>Description</th>
                <th class="text-right">Price</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>

        <tbody>
            <tr
                v-for="(item, index) in order.items"
                :key="index"
                class="border-b-2 border-b-primary"
            >
                <th>{{ index + 1 }}</th>
                <td>{{ item.name }}</td>
                <td class="text-right">
                    {{ item.pivot.price.toLocaleString() }}
                </td>
                <td class="text-right">
                    {{ item.pivot.quantity }}
                </td>
                <td class="text-right">
                    {{
                        (
                            item.pivot.quantity * item.pivot.price
                        ).toLocaleString()
                    }}
                </td>
            </tr>
            <ToppingRow :order="order" />
            <tr class="font-bold">
                <th class="underline"></th>
                <td colspan="2">Total</td>
                <td class="text-right">
                    {{
                        order.items.reduce(
                            (carry, e) => e.pivot.quantity + carry,
                            0
                        ) +
                        order.toppings.reduce(
                            (carry, e) => e.pivot.quantity + carry,
                            0
                        )
                    }}
                </td>
                <td class="text-right">
                    {{
                        (
                            order.items.reduce(
                                (carry, e) =>
                                    e.pivot.quantity * e.pivot.price + carry,
                                0
                            ) +
                            order.toppings.reduce(
                                (carry, e) =>
                                    e.pivot.quantity * e.pivot.price + carry,
                                0
                            )
                        ).toLocaleString()
                    }}
                </td>
            </tr>
            <tr class="font-bold">
                <td colspan="4" class="text-right">Discount</td>
                <td class="text-right">
                    {{ order.discount.toLocaleString() }}
                </td>
            </tr>
            <tr class="font-bold" v-if="order.status != 3">
                <td colspan="4" class="text-right">Amount</td>
                <td class="text-right">
                    {{ (order.amount - order.discount).toLocaleString() }}
                </td>
            </tr>
            <tr class="border-b-2 border-b-primary font-bold">
                <td colspan="4" class="text-right">Paid</td>
                <td class="text-right">
                    {{
                        order.payments
                            .reduce((carry, e) => carry + e.pivot.amount, 0)
                            .toLocaleString()
                    }}
                </td>
            </tr>
            <tr class="font-bold">
                <td colspan="4" class="text-right">Remaining</td>
                <td class="text-right">
                    {{
                        (
                            order.amount -
                            order.discount -
                            order.payments.reduce(
                                (carry, e) => carry + e.pivot.amount,
                                0
                            )
                        ).toLocaleString()
                    }}
                </td>
            </tr>
        </tbody>
    </table>
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
