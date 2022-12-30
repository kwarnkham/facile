<script setup>
const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <tr
        v-for="(topping, index) in order.toppings"
        :key="topping.id"
        class="border-b-2 border-b-primary"
    >
        <th>{{ (order.features.length ?? order.items.length) + index + 1 }}</th>
        <td>{{ topping.name }}</td>
        <td class="text-right">
            <strong v-if="topping.pivot.discount">
                (-{{ topping.pivot.discount.toLocaleString() }})
            </strong>
            {{ topping.pivot.price.toLocaleString() }}
        </td>
        <td class="text-right">
            {{ topping.pivot.quantity }}
        </td>
        <td class="text-right">
            {{
                (
                    topping.pivot.quantity *
                    Math.floor(topping.pivot.price - topping.pivot.discount)
                ).toLocaleString()
            }}
        </td>
    </tr>
</template>
