<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
const form = useForm({
    name: "",
    price: "",
    description: "",
});

const submit = () => {
    form.post(route("items.store"), {
        onFinish: () => form.reset("name", "price", "description"),
    });
};
</script>

<template>
    <Head title="Create Item" />
    <AuthenticatedLayout>
        <form @submit.prevent="submit" class="p-10">
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="price" value="Price" />
                <TextInput
                    id="price"
                    type="number"
                    class="mt-1 block w-full"
                    v-model="form.price"
                    required
                />
                <InputError class="mt-2" :message="form.errors.price" />
            </div>

            <div class="mt-4">
                <InputLabel for="description" value="Description" />
                <TextInput
                    id="description"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.description"
                    required
                />
                <InputError class="mt-2" :message="form.errors.price" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Create Item
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
