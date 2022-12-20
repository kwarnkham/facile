<script setup>
import InputError from "@/Components/InputError.vue";
import PicturePicker from "@/Components/PicturePicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { EyeIcon, EyeSlashIcon } from "@heroicons/vue/24/solid";
import { Inertia } from "@inertiajs/inertia";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";
import { ref } from "vue";

const props = defineProps({
    payments: {
        type: Object,
        required: true,
    },
    payment_types: {
        type: Object,
        required: true,
    },
});

const submit = () => {
    form.transform((data) => pickBy(data)).post(route("payments.store"), {
        onSuccess() {
            form.reset("number", "account_name");
        },
    });
};

const togglePayment = (payment) => {
    toggling.value = true;
    Inertia.visit(
        route("payments.toggle", {
            payment: payment.id,
        }),
        {
            method: "post",
            preserveState: true,
            onFinish: () => {
                toggling.value = false;
            },
        }
    );
};
const toggling = ref(false);
const form = useForm({
    number: "",
    payment_type_id: props.payment_types[0]?.id,
    account_name: "",
    qr: null,
});
</script>

<template>
    <div class="h-full p-1 flex flex-col">
        <Head title="Payments" />

        <form @submit.prevent="submit" class="p-4 flex flex-col space-y-2">
            <div class="text-center">Add Payments</div>
            <select
                class="daisy-select daisy-select-primary daisy-select-sm"
                required
                v-model="form.payment_type_id"
            >
                <option disabled selected>Select Payment</option>
                <option
                    v-for="payment_type in payment_types"
                    :key="payment_type.id"
                    :value="payment_type.id"
                >
                    {{ payment_type.name }}
                </option>
            </select>
            <InputError :message="form.errors.payment_type_id" />
            <div>
                <TextInput
                    id="number"
                    type="tel"
                    placeholder="Number"
                    class="mt-1 block w-full"
                    v-model.number="form.number"
                    :required="form.payment_type_id != 1"
                    :class="{ 'daisy-input-error': form.errors.number }"
                />
                <InputError :message="form.errors.number" />
            </div>

            <div>
                <TextInput
                    id="account_name"
                    type="text"
                    placeholder="Account Name"
                    class="mt-1 block w-full"
                    v-model.number="form.account_name"
                    :required="form.payment_type_id != 1"
                    :class="{ 'daisy-input-error': form.errors.account_name }"
                />
                <InputError :message="form.errors.account_name" />
            </div>
            <div>
                <PicturePicker
                    :label="'QR Code'"
                    class="mt-2"
                    v-model="form.qr"
                />
                <InputError :message="form.errors.qr" />
            </div>
            <div class="text-right">
                <PrimaryButton type="submit" :disabled="form.processing">
                    Add
                </PrimaryButton>
            </div>
        </form>
        <div class="flex-grow flex-shrink-0 basis-0 overflow-y-auto">
            <div
                v-for="payment in payments"
                :key="payment.id"
                class="daisy-card bg-base-100 shadow-xl daisy-card-compact w-full mb-1"
            >
                <div class="daisy-card-body">
                    <h4 class="daisy-card-title">
                        {{
                            payment_types.find(
                                (e) => e.id == payment.payment_type_id
                            ).name
                        }}
                    </h4>
                    <p v-if="payment.number">Number: {{ payment.number }}</p>
                    <p v-if="payment.account_name">
                        Account name: {{ payment.account_name }}
                    </p>
                    <div class="daisy-card-actions justify-end">
                        <EyeIcon
                            v-if="payment.status == 1 && !toggling"
                            class="w-5 h-5 inline-block text-success"
                            @click="togglePayment(payment)"
                        />
                        <EyeSlashIcon
                            v-else-if="payment.status == 2 && !toggling"
                            class="w-5 h-5 inline-block text-error"
                            @click="togglePayment(payment)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
