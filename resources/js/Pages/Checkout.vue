<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";

const form = useForm({
    name: "",
    phone: "",
    address: "",
});
const submit = () => {};
</script>
<template>
    <div class="h-full overflow-y-auto flex flex-col p-1 pb-8">
        <Head title="Checkout" />
        <form @submit.prevent="submit" class="p-4 daisy-form-control space-y-2">
            <div class="text-center text-2xl text-primary">Checkout</div>
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
                <InputLabel for="phone" value="Phone" />
                <TextInput
                    id="phone"
                    type="phone"
                    class="w-full"
                    v-model="form.phone"
                    required
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
