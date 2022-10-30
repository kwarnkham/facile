<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";

const props = defineProps({
    item: {
        required: true,
        type: Object,
    },
});
const form = useForm({
    name: props.item.name,
    price: props.item.price,
    description: props.item.description,
});

const submit = () => {
    form.put(route("items.update", { item: props.item.id }));
};
</script>

<template>
    <Head title="Create Item" />

    <form @submit.prevent="submit" class="p-4 daisy-form-control space-y-2">
        <div class="text-center text-2xl text-primary">Edit Item</div>
        <div>
            <InputLabel for="name" value="Name" />
            <TextInput
                id="name"
                type="text"
                class="w-full"
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
                class="w-full"
                v-model="form.price"
                required
                :class="{ 'daisy-input-error': form.errors.price }"
            />
            <InputError :message="form.errors.price" />
        </div>

        <div>
            <InputLabel for="description" value="Description" />
            <textarea
                id="description"
                class="w-full daisy-textarea daisy-textarea-primary"
                placeholder="Description"
                v-model="form.description"
                required
                :class="{ 'daisy-input-error': form.errors.description }"
                rows="3"
            ></textarea>
            <InputError :message="form.errors.description" />
        </div>

        <div class="flex items-center justify-end">
            <PrimaryButton
                type="primary"
                class="ml-4"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Update
            </PrimaryButton>
        </div>
    </form>
</template>
