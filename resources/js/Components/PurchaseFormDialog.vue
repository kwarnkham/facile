<script setup>
import { useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";
import Button from "./Button.vue";
import Dialog from "./Dialog.vue";
import InputLabel from "./InputLabel.vue";
import TextInput from "./TextInput.vue";

const props = defineProps({
    open: {
        type: Boolean,
        required: true,
    },
    feature: {
        type: Object,
        required: true,
    },
});
defineEmits(["close"]);
const purchaseForm = useForm({
    price: "",
    quantity: "",
    expired_on: "",
});
const submit = () => {
    purchaseForm
        .transform((data) => pickBy(data))
        .post(route("features.restock", { feature: props.feature.id }), {
            preserveState: false,
        });
};
</script>
<template>
    <Dialog :open="open" title="Purchase" @close="$emit('close')">
        <form @submit.prevent="submit">
            <div>
                <InputLabel for="purchasePrice" value="Price" />
                <TextInput
                    id="purchasePrice"
                    type="tel"
                    class="w-full"
                    v-model.number="purchaseForm.price"
                    required
                    placeholder="Amount"
                    :autofocus="open"
                />
            </div>
            <div>
                <InputLabel for="purchaseQuantity" value="Quantity" />
                <TextInput
                    id="purchaseQuantity"
                    type="tel"
                    class="w-full"
                    v-model.number="purchaseForm.quantity"
                    required
                    placeholder="Quantity"
                />
            </div>
            <div>
                <InputLabel for="expiredOn" value="Expire Date" />
                <TextInput
                    id="expiredOn"
                    type="date"
                    class="w-full"
                    v-model="purchaseForm.expired_on"
                    placeholder="Expire Date"
                />
            </div>
            <div class="text-right pt-2">
                <Button :disabled="purchaseForm.processing" type="submit">
                    Submit
                </Button>
            </div>
        </form>
    </Dialog>
</template>
