<script setup>
import Collapse from "@/Components/Collapse.vue";
import Dialog from "@/Components/Dialog.vue";
import InputLabel from "@/Components/InputLabel.vue";
import ModalPicture from "@/Components/ModalPicture.vue";
import FeatureOrder from "@/Components/FeatureOrder.vue";
import PicturePicker from "@/Components/PicturePicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import {
    CalendarIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    MapPinIcon,
    PhoneIcon,
    PhotoIcon,
    UserIcon,
    HashtagIcon,
} from "@heroicons/vue/24/solid";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";
import { computed, ref } from "vue";
import ItemOrder from "@/Components/ItemOrder.vue";
import useConfirm from "@/Composables/confirm";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    payments: {
        type: Array,
        required: true,
    },
    payment_types: {
        type: Array,
        required: true,
    },
});

const remaining = computed(
    () =>
        props.order.amount -
        props.order.discount -
        props.order.payments.reduce((carry, e) => carry + e.pivot.amount, 0)
);
const modalPicture = ref("");
const showPicture = (picture) => {
    modalPicture.value = picture;
};
const canMakePayment = computed(() => paymentForm.amount <= remaining.value);
const paymentForm = useForm({
    amount: remaining.value,
    note: "",
    payment_id: props.payments[0]?.id,
    picture: null,
});

const isOrderInfoExpanded = ref(true);
const passedHours = computed(() => {
    return (
        Math.abs(Date.now() - new Date(props.order.updated_at).getTime()) /
        3600000
    );
});
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
const { confirm } = useConfirm();
const cancelOrder = (order) => {
    confirm(() => {
        Inertia.post(route("orders.cancel", { order: order.id }));
    }, "Do you want to cancel the order?");
};
</script>
<template>
    <div class="h-full overflow-y-auto p-1 flex flex-col">
        <Head title="Order" />
        <Collapse
            v-model:checked="isOrderInfoExpanded"
            title="Order Information"
        >
            <div class="text-xs">
                <div class="flex items-center justify-center">
                    <HashtagIcon class="h-4 w-4 mr-2" /> {{ order.id }}
                </div>
                <div class="flex flex-row justify-between">
                    <div class="flex items-center" v-if="order.customer">
                        <UserIcon class="h-4 w-4 mr-2" />
                        {{ order.customer }}
                    </div>
                    <div class="flex items-center" v-if="order.phone">
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
            v-if="order.payments.length"
            v-model:checked="isPaymentInfoExpanded"
            title="Payment Information"
        >
            <div
                v-for="orderPayment in order.payments"
                :key="orderPayment.id"
                class="text-sm w-ful"
            >
                <div class="flex justify-between">
                    <div>
                        <strong>{{ orderPayment.pivot.amount }}</strong> MMK is
                        paid to
                        <span class="font-semibold">
                            {{ orderPayment.pivot.account_name }}
                        </span>
                        <span
                            class="font-semibold"
                            v-if="orderPayment.pivot.number"
                        >
                            -
                            {{ orderPayment.pivot.number }}
                        </span>
                    </div>
                    <PhotoIcon
                        class="w-4 h-4"
                        @click="showPicture(orderPayment.pivot.picture)"
                        v-if="orderPayment.pivot.picture"
                    />
                </div>
                <div>
                    {{
                        new Date(orderPayment.created_at)
                            .toLocaleString("en-GB", { hour12: true })
                            .toUpperCase()
                    }}
                </div>

                <div v-if="orderPayment.pivot.note">
                    Note : {{ orderPayment.pivot.note }}
                </div>
            </div>
        </Collapse>
        <FeatureOrder :order="order" v-if="order.features.length" />
        <ItemOrder :order="order" v-else />

        <div class="text-xs flex items-center" v-if="!payments.length">
            <ExclamationTriangleIcon class="w-4 h-4 text-info" />
            You have no payment method or payment is not enabled.
        </div>
        <div
            class="flex justify-evenly mt-2"
            v-if="order.status != 4 && order.status != 5"
        >
            <PrimaryButton
                @click="showPaymentForm = true"
                v-if="payments.length && order.status != 3"
            >
                Pay
            </PrimaryButton>

            <PrimaryButton
                @click="$inertia.visit(route('payments.index'))"
                v-else-if="!payments.length && order.status != 3"
            >
                Manage Payments
            </PrimaryButton>

            <button
                class="daisy-btn-sm daisy-btn-warning daisy-btn capitalize"
                :disabled="order.status == 3 && passedHours >= 24"
                @click="cancelOrder(order)"
            >
                Cancel
            </button>
            <button
                class="daisy-btn-sm daisy-btn-success daisy-btn capitalize"
                :disabled="order.status != 3"
                @click="
                    $inertia.post(route('orders.complete', { order: order.id }))
                "
            >
                Complete
            </button>
        </div>
        <div v-if="order.status == 4" class="text-sm text-center text-warning">
            This order has been canceled on
            {{
                new Date(order.updated_at)
                    .toLocaleString("en-GB", {
                        hour12: true,
                    })
                    .toUpperCase()
            }}
        </div>
        <Dialog
            :title="'Payment method'"
            :open="showPaymentForm"
            @close="showPaymentForm = false"
        >
            <div
                class="daisy-form-control"
                v-for="payment in payments"
                :key="payment.id"
            >
                <label class="daisy-label cursor-pointer">
                    <div class="daisy-label-text">
                        <span>
                            {{
                                payment_types.find(
                                    (e) => e.id == payment.payment_type_id
                                ).name
                            }}
                        </span>
                        <strong v-if="payment.number">
                            -
                            {{ payment.number }}
                        </strong>
                        <strong v-if="payment.account_name">
                            -
                            {{ payment.account_name }}
                        </strong>
                    </div>
                    <input
                        type="radio"
                        class="daisy-radio checked:bg-primary"
                        v-model="paymentForm.payment_id"
                        :value="payment.id"
                    />
                </label>
            </div>
            <div class="daisy-divider"></div>
            <img
                v-if="payments.find((e) => e.id == paymentForm.payment_id)?.qr"
                :src="payments.find((e) => e.id == paymentForm.payment_id).qr"
                alt="payment qr"
                class="w-full"
            />
            <div class="daisy-divider"></div>

            <div>
                <InputLabel for="paymentAmount" value="Amount" />
                <TextInput
                    id="paymentAmount"
                    type="tel"
                    class="w-full"
                    v-model.number="paymentForm.amount"
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
            <div>
                <PicturePicker
                    :label="'screenshot'"
                    class="mt-2"
                    v-model="paymentForm.picture"
                />
            </div>
            <div class="text-right pt-2">
                <button
                    class="daisy-btn daisy-btn-success daisy-btn-sm capitalize"
                    :disabled="!canMakePayment || paymentForm.processing"
                    @click="submitPayment"
                >
                    Make Payment
                </button>
            </div>
        </Dialog>
        <Teleport to="body">
            <ModalPicture
                :open="!!modalPicture"
                :src="modalPicture"
                @closed="modalPicture = ''"
            />
        </Teleport>
    </div>
</template>
