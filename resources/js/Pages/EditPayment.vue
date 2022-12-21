<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PicturePicker from "@/Components/PicturePicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";

const props = defineProps({
    payment: {
        type: Object,
        required: true,
    },
});

const submit = () => {
    form.transform((data) => pickBy(data)).post(
        route("payments.update", { payment: props.payment.id }),
        {
            onSuccess() {
                form.reset("number", "account_name", "qr");
            },
            preserveState: false,
        }
    );
};
const form = useForm({
    account_name: props.payment.account_name,
    number: props.payment.number,
    qr: null,
    _method: "PUT",
});
</script>

<template>
    <Head title="Edit Payment" />
    <form @submit.prevent="submit" class="p-4 flex flex-col space-y-2">
        <div class="text-center">Edit Payments</div>
        <div>
            <InputLabel for="number" value="Number" />
            <TextInput
                id="number"
                type="tel"
                placeholder="Number"
                class="block w-full"
                v-model.number="form.number"
                :required="payment.payment_type_id != 1"
                :class="{ 'daisy-input-error': form.errors.number }"
            />
            <InputError :message="form.errors.number" />
        </div>

        <div class="mt-1">
            <InputLabel for="account_name" value="Account Name" />
            <TextInput
                id="account_name"
                type="text"
                placeholder="Account Name"
                class="block w-full"
                v-model.number="form.account_name"
                :required="form.payment_type_id != 1"
                :class="{ 'daisy-input-error': form.errors.account_name }"
            />
            <InputError :message="form.errors.account_name" />
        </div>
        <div class="mt-1">
            <PicturePicker :label="'Choose QR Code'" v-model="form.qr" />
            <InputError :message="form.errors.qr" />
            <img :src="payment.qr" alt="payment qr" class="w-full" />
        </div>
        <div class="text-right">
            <PrimaryButton type="submit" :disabled="form.processing">
                Update
            </PrimaryButton>
        </div>
    </form>
</template>
