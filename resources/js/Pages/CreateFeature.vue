<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";

const props = defineProps({
    item: {
        required: true,
        type: Object,
    },
});
const form = useForm({
    name: "",
    price: "",
    purchase_price: "",
    stock: "1",
    note: "",
    item_id: props.item.id,
    expired_on: null,
});
const submit = () => {
    form.transform((data) => pickBy(data)).post(route("features.store"));
};
</script>

<template>
    <div class="p-1">
        <Head title="Create Feature" />
        <div class="text-xs font-bold">
            <div>Name: {{ item.name }}</div>
            <div>Description: {{ item.description }}</div>
        </div>
        <form @submit.prevent="submit" class="p-4 daisy-form-control space-y-2">
            <div class="text-center text-2xl text-primary">
                Create Item Feature
            </div>
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
                <InputLabel for="purchase_price" value="Purchase Price" />
                <TextInput
                    id="purchase_price"
                    type="number"
                    class="w-full"
                    v-model="form.purchase_price"
                    required
                    :class="{ 'daisy-input-error': form.errors.purchase_price }"
                />
                <InputError :message="form.errors.purchase_price" />
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
                <InputLabel for="stock" value="Stock" />
                <TextInput
                    id="stock"
                    type="number"
                    class="w-full"
                    v-model="form.stock"
                    required
                    :class="{ 'daisy-input-error': form.errors.stock }"
                />
                <InputError :message="form.errors.stock" />
            </div>

            <div>
                <InputLabel for="expireDate" value="Expire Date" />
                <TextInput
                    id="expireDate"
                    type="date"
                    class="w-full"
                    v-model="form.expired_on"
                    required
                    :class="{ 'daisy-input-error': form.errors.expired_on }"
                />
                <InputError :message="form.errors.expired_on" />
            </div>

            <div>
                <InputLabel for="note" value="Note" />
                <textarea
                    id="note"
                    class="w-full daisy-textarea daisy-textarea-primary"
                    placeholder="Note"
                    v-model="form.note"
                    :class="{
                        'daisy-input-error': form.errors.note,
                    }"
                    rows="3"
                ></textarea>
                <InputError :message="form.errors.note" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    type="submit"
                    class="ml-4"
                    :disabled="form.processing"
                >
                    Create
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
