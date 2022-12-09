<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";

const props = defineProps({
    wholesale: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    quantity: props.wholesale.quantity,
    price: props.wholesale.price,
    item_id: props.wholesale.item_id,
});

const submit = () => {
    form.put(route("wholesales.update", { wholesale: props.wholesale.id }));
};
</script>

<template>
    <div class="h-full overflow-y-auto">
        <Head title="Edit Wholesale" />
        <form
            @submit.prevent="submit"
            class="p-4 daisy-form-control space-y-2 shadow-md mb-2"
        >
            <div class="text-center text-2xl text-primary">
                Edit wholesale price
            </div>
            <div>
                <div>Name: {{ wholesale.item.name }}</div>
                <div>Price: {{ wholesale.item.price }}</div>
                <div>Description: {{ wholesale.item.description }}</div>
            </div>
            <div>
                <InputLabel for="quantity" value="Quantity" />
                <TextInput
                    id="quantity"
                    type="tel"
                    class="w-full"
                    v-model.number="form.quantity"
                    required
                    autofocus
                    :class="{
                        'daisy-input-error': form.errors.quantity,
                    }"
                />
                <InputError :message="form.errors.quantity" />
            </div>

            <div>
                <InputLabel for="price" value="Price" />
                <TextInput
                    id="price"
                    type="tel"
                    class="w-full"
                    v-model.number="form.price"
                    required
                    autofocus
                    :class="{
                        'daisy-input-error': form.errors.price,
                    }"
                />
                <InputError :message="form.errors.price" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    type="submit"
                    class="ml-4"
                    :disabled="form.processing"
                >
                    Update
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
