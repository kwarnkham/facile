<script setup>
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
                v-for="(feature, index) in order.features"
                :key="feature.id"
                class="border-b-2 border-b-primary"
            >
                <th>{{ index + 1 }}</th>
                <td>{{ feature.name }}</td>
                <td class="text-right">
                    <strong v-if="feature.pivot.discount">
                        (-{{ feature.pivot.discount.toLocaleString() }})
                    </strong>
                    {{ feature.pivot.price.toLocaleString() }}
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
