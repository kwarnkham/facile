<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/inertia-vue3";

const props = defineProps({
    topping: {
        required: true,
        type: Object,
    },
});
const form = useForm({
    name: props.topping.name,
    price: props.topping.price,
});

const submit = () => {
    form.put(
        route("toppings.update", {
            topping: props.topping.id,
        })
    );
};
</script>

<template>
    <form @submit.prevent="submit" class="daisy-form-control p-4 space-y-2">
        <div class="text-center text-2xl text-primary">Update Topping</div>
        <div>
            <InputLabel for="name" value="Name" />
            <TextInput
                id="name"
                type="text"
                class="mt-1 w-full"
                v-model="form.name"
                required
                autofocus
                :class="{ 'daisy-input-error': form.errors.name }"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="price" value="Price" />
            <TextInput
                id="price"
                type="number"
                class="mt-1 w-full"
                v-model.number="form.price"
                required
                :class="{ 'daisy-input-error': form.errors.price }"
            />
            <InputError :message="form.errors.price" />
        </div>

        <div class="flex items-center justify-end">
            <PrimaryButton
                type="submit"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Update
            </PrimaryButton>
        </div>
    </form>
</template>
