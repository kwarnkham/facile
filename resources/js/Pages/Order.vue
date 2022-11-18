<script setup>
import Collapse from "@/Components/Collapse.vue";
import Dialog from "@/Components/Dialog.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import {
    CalendarIcon,
    InformationCircleIcon,
    MapPinIcon,
    PhoneIcon,
    UserIcon,
} from "@heroicons/vue/24/solid";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";
import { computed, ref } from "vue";

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    merchant_payments: {
        type: Array,
        required: true,
    },
});

const remaining = computed(
    () =>
        props.order.amount -
        props.order.discount -
        props.order.merchant_payments.reduce(
            (carry, e) => carry + e.pivot.amount,
            0
        )
);
const canMakePayment = computed(() => paymentForm.amount <= remaining.value);
const paymentForm = useForm({
    amount: remaining.value,
    note: "",
    payment_id: props.merchant_payments[0].id,
});

const isOrderInfoExpanded = ref(true);
const completeOrderForm = useForm();
const completeOrder = () => {
    completeOrderForm.post(route("orders.complete", { order: props.order.id }));
};
const showPaymentForm = ref(false);
const submitPayment = () => {
    if (!canMakePayment.value) return;
    paymentForm
        .transform((data) => pickBy(data))
        .post(route("orders.pay", { order: props.order.id }), {
            onSuccess() {
                showPaymentForm.value = false;
                paymentForm.amount = remaining.value;
            },
        });
};
const isPaymentInfoExpanded = ref(false);
</script>
<template>
    <div class="h-full overflow-y-auto p-1 flex flex-col">
        <Head title="Order" />
        <Collapse
            v-model:checked="isOrderInfoExpanded"
            title="Order Information"
        >
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
                    <MapPinIcon class="h-4 w-4 mr-2" /> {{ order.address }}
                </div>
                <div v-if="order.note" class="flex items-center">
                    <InformationCircleIcon class="h-4 w-4 mr-2" />
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
        <Collapse
            v-model:checked="isPaymentInfoExpanded"
            title="Payment Information"
        >
            <div
                v-for="orderPayment in order.merchant_payments"
                :key="orderPayment.id"
                class="flex justify-evenly"
            >
                <p class="text-sm">
                    <strong>{{ orderPayment.pivot.amount }}</strong> is paid to
                    <span class="font-semibold">
                        {{ orderPayment.payment.name }} -
                        {{ orderPayment.pivot.number }}
                    </span>
                    on
                    {{
                        new Date(orderPayment.created_at)
                            .toLocaleString("en-GB", { hour12: true })
                            .toUpperCase()
                    }}
                </p>
                <div v-if="orderPayment.pivot.note">
                    Note : {{ orderPayment.pivot.note }}
                </div>
            </div>
        </Collapse>

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
                    <td colspan="4" class="text-right">Paid</td>
                    <td class="text-right">
                        {{
                            order.merchant_payments
                                .reduce((carry, e) => carry + e.pivot.amount, 0)
                                .toLocaleString()
                        }}
                    </td>
                </tr>
                <tr class="font-bold" v-if="order.status != 3">
                    <td colspan="4" class="text-right">Amount</td>
                    <td class="text-right">
                        {{ remaining.toLocaleString() }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div class="flex justify-evenly mt-2" v-if="order.status != 3">
            <form @submit.prevent="completeOrder">
                <PrimaryButton
                    type="submit"
                    @click="completeOrder"
                    :disabled="completeOrderForm.processing"
                >
                    Complete
                </PrimaryButton>
            </form>

            <PrimaryButton @click="showPaymentForm = true"> Pay </PrimaryButton>

            <button class="daisy-btn-sm daisy-btn-warning rounded-md">
                Cancel
            </button>
        </div>
        <Dialog
            :title="'Payment method'"
            :open="showPaymentForm"
            @close="showPaymentForm = false"
        >
            <div
                class="daisy-form-control"
                v-for="merchantPayment in merchant_payments"
                :key="merchantPayment.id"
            >
                <label class="daisy-label cursor-pointer">
                    <span class="daisy-label-text">
                        {{ merchantPayment.payment.name }} -
                        {{ merchantPayment.number }}
                    </span>
                    <input
                        type="radio"
                        class="daisy-radio checked:bg-primary"
                        v-model="paymentForm.payment_id"
                        :value="merchantPayment.id"
                    />
                </label>
            </div>
            <div class="daisy-divider"></div>
            <div>
                <InputLabel for="paymentAmount" value="Amount" />
                <TextInput
                    id="paymentAmount"
                    type="number"
                    class="w-full"
                    v-model="paymentForm.amount"
                    required
                    :class="{
                        'daisy-input-error': !canMakePayment,
                    }"
                    placeholder="Amount"
                />
            </div>
            <div>
                <InputLabel for="note" value="Note" />
                <TextInput
                    id="note"
                    type="text"
                    class="w-full"
                    v-model="paymentForm.note"
                    placeholder="Note"
                />
            </div>
            <div class="text-right pt-2">
                <button
                    class="daisy-btn daisy-btn-success daisy-btn-sm capitalize"
                    :disabled="!canMakePayment"
                    @click="submitPayment"
                >
                    Make Payment
                </button>
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
