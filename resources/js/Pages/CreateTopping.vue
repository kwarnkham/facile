<script setup>
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/inertia-vue3";

const form = useForm({
    name: "",
    price: "",
    cost: "",
});
const props = defineProps({
    toppings: {
        type: Array,
        required: true,
    },
});
const submit = () => {
    form.post(route("toppings.store"), {
        onSuccess() {
            form.reset("name", "price");
        },
    });
};
</script>

<template>
    <div class="flex flex-col h-full flex-nowrap">
        <form @submit.prevent="submit" class="daisy-form-control p-4 space-y-2">
            <div class="text-center text-2xl text-primary">Create Topping</div>
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

            <div>
                <InputLabel for="cost" value="Cost" />
                <TextInput
                    id="cost"
                    type="number"
                    class="mt-1 w-full"
                    v-model.number="form.cost"
                    required
                    :class="{ 'daisy-input-error': form.errors.cost }"
                />
                <InputError :message="form.errors.cost" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Submit
                </PrimaryButton>
            </div>
        </form>
        <div class="daisy-divider text-lg font-bold">Topping List</div>
        <div
            class="flex-grow overflow-y-auto p-1 flex flex-row flex-wrap justify-around"
        >
            <Button
                v-for="topping in toppings"
                :key="topping.id"
                @click="
                    $inertia.visit(
                        route('toppings.edit', {
                            topping: topping.id,
                        })
                    )
                "
            >
                {{ topping.name }}
            </Button>
        </div>
    </div>
</template>
