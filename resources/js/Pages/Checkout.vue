<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { store } from "@/store";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";

const form = useForm({
    customer: "",
    phone: "",
    address: "",
    note: "",
    features: JSON.parse(localStorage.getItem("cartItems")),
    discount: JSON.parse(localStorage.getItem("cartDiscount")) ?? "",
    toppings: JSON.parse(localStorage.getItem("toppings")),
});
const submit = () => {
    form.transform((data) => {
        if (data.toppings.length == 0) data.toppings = undefined;
        return pickBy(data);
    }).post(route("orders.store"), {
        replace: true,
        onSuccess() {
            localStorage.removeItem("cartItems");
            localStorage.removeItem("cartDiscount");
            localStorage.removeItem("toppings");
            store.cart.clear();
        },
    });
};
</script>
<template>
    <div class="h-full overflow-y-auto flex flex-col p-1 pb-8">
        <Head title="Checkout" />
        <form @submit.prevent="submit" class="p-4 daisy-form-control space-y-2">
            <div class="text-center text-2xl text-primary">Checkout</div>
            <div>
                <InputLabel for="customer" value="Customer" />
                <TextInput
                    id="customer"
                    type="text"
                    class="w-full"
                    v-model="form.customer"
                    autofocus
                    :class="{ 'daisy-input-error': form.errors.customer }"
                />
                <InputError :message="form.errors.customer" />
            </div>

            <div>
                <InputLabel for="phone" value="Phone" />
                <TextInput
                    id="phone"
                    type="tel"
                    class="w-full"
                    v-model="form.phone"
                    :class="{ 'daisy-input-error': form.errors.phone }"
                />
                <InputError :message="form.errors.phone" />
            </div>

            <div>
                <InputLabel for="address" value="Address" />
                <TextInput
                    id="address"
                    type="text"
                    class="w-full"
                    v-model="form.address"
                    :class="{ 'daisy-input-error': form.errors.address }"
                />
                <InputError :message="form.errors.address" />
            </div>

            <div>
                <InputLabel for="note" value="Note" />
                <TextInput
                    id="note"
                    type="text"
                    class="w-full"
                    v-model="form.note"
                    :class="{ 'daisy-input-error': form.errors.note }"
                />
                <InputError :message="form.errors.note" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    type="submit"
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Checkout
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
