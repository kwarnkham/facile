<script setup>
import Collapse from "@/Components/Collapse.vue";
import Dialog from "@/Components/Dialog.vue";
import {
    CalendarIcon,
    InformationCircleIcon,
    MapPinIcon,
    PhoneIcon,
    UserIcon,
} from "@heroicons/vue/24/solid";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { ref } from "vue";

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    payments: {
        type: Array,
        required: true,
    },
});

const isExpanded = ref(true);
const completeOrderForm = useForm();
const payOrderForm = useForm();
const completeOrder = () => {
    completeOrderForm.post(route("orders.complete", { order: props.order.id }));
};
const open = ref(false);
const payOrder = () => {
    open.value = true;
};
</script>
<template>
    <div class="h-full overflow-y-auto p-1 flex flex-col">
        <Head title="Order" />
        <Collapse v-model:checked="isExpanded" title="Order Information">
            <div class="text-xs">
                <div class="flex flex-row justify-between">
                    <div class="flex items-center">
                        <UserIcon class="h-4 w-4 mr-2" />
                        {{ order.customer }}
                    </div>
                    <div class="flex items-center">
                        <PhoneIcon class="h-4 w-4 mr-2" />
                        {{ order.phone }}
                    </div>
                </div>

                <div v-if="order.address" class="flex items-center">
                    <MapPinIcon class="h-4 w-4 mr-2" /> : {{ order.address }}
                </div>
                <div v-if="order.note" class="flex items-center">
                    <InformationCircleIcon class="h-4 w-4 mr-2" />:
                    {{ order.note }}
                </div>

                <div class="flex justify-between">
                    <div class="flex items-center">
                        <CalendarIcon class="h-4 w-4 mr-2" />
                        {{
                            new Date(order.created_at)
                                .toLocaleString("en-GB", {
                                    hour12: true,
                                })
                                .toUpperCase()
                        }}
                    </div>

                    <div
                        class="daisy-tooltip daisy-tooltip-left daisy-tooltip-info"
                        :data-tip="
                            new Date(order.updated_at)
                                .toLocaleString('en-GB', {
                                    hour12: true,
                                })
                                .toUpperCase()
                        "
                    >
                        <div>Status: {{ order.status }}</div>
                    </div>
                </div>
            </div>
        </Collapse>

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
        <div class="flex justify-evenly mt-2">
            <form @submit.prevent="completeOrder">
                <button
                    type="submit"
                    class="daisy-btn-sm daisy-btn-primary rounded-md"
                    @click="completeOrder"
                    :disabled="completeOrderForm.processing"
                >
                    Complete
                </button>
            </form>

            <button
                type="submit"
                class="daisy-btn-sm daisy-btn-primary rounded-md"
                @click="payOrder"
                :disabled="payOrderForm.processing"
            >
                Pay
            </button>

            <button class="daisy-btn-sm daisy-btn-warning rounded-md">
                Cancel
            </button>
        </div>
        <Dialog :title="'Payment method'" :open="open">
            <div class="daisy-form-control">
                <label class="daisy-label cursor-pointer">
                    <span class="daisy-label-text">Red pill</span>
                    <input
                        type="radio"
                        name="radio-10"
                        class="daisy-radio checked:bg-red-500"
                        checked
                    />
                </label>
            </div>
        </Dialog>
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
