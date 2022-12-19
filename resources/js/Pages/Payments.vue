<script setup>
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { EyeIcon, EyeSlashIcon } from "@heroicons/vue/24/solid";
import { Inertia } from "@inertiajs/inertia";
import { Head, useForm } from "@inertiajs/inertia-vue3";
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
    form.post(route("payments.store"), {
        onSuccess() {
            form.reset("number");
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
    payment_id: props.payments[0]?.id,
});
</script>

<template>
    <div class="h-full p-1">
        <Head title="Payments" />
        <div
            v-for="payment in payments"
            :key="payment.id"
            class="flex items-center"
        >
            <span class="inline-block mr-1">
                {{ payment.payment_type_id }} : {{ payment.number }}
            </span>
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
        <form @submit.prevent="submit" class="p-4 flex flex-col space-y-2">
            <div class="text-center">Add Payments</div>
            <select
                class="daisy-select daisy-select-primary daisy-select-sm"
                required
                v-model="form.payment_id"
            >
                <option disabled selected>Select Payment</option>
                <option
                    v-for="payment in payment_types"
                    :key="payment.id"
                    :value="payment.id"
                >
                    {{ payment.name }}
                </option>
            </select>
            <div>
                <TextInput
                    id="number"
                    type="tel"
                    placeholder="Number"
                    class="mt-1 block w-full"
                    v-model.number="form.number"
                    :required="form.payment_id != 1"
                    :class="{ 'daisy-input-error': form.errors.number }"
                />
                <InputError :message="form.errors.number" />
            </div>
            <div class="text-right">
                <PrimaryButton type="submit" :disabled="form.processing">
                    Add
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
