<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
const props = defineProps({
    batch: {
        required: true,
        type: Object,
    },
});

const form = useForm({
    type: 1,
    stock: "",
});
const submit = () => {
    form.post(route("batches.correct", { batch: props.batch.id }), {
        onSuccess() {
            form.reset("stock");
        },
    });
};
</script>

<template>
    <Head title="Batch" />
    <div class="p-1">
        <div class="text-center text-lg font-bold">
            {{ batch.feature.name }}
        </div>
        <div>Stock : {{ batch.stock }}</div>
        <div>Expired on : {{ batch.expired_on }}</div>
        <form @submit.prevent="submit" class="daisy-form-control p-4 space-y-2">
            <div class="text-center text-2xl text-primary">Correction</div>
            <div>
                <InputLabel for="stock" value="Stock correction" />
                <TextInput
                    id="email"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model.number="form.stock"
                    required
                    :class="{ 'daisy-input-error': form.errors.stock }"
                />
                <InputError :message="form.errors.stock" />
            </div>

            <div class="daisy-form-control">
                <label class="daisy-label cursor-pointer">
                    <span class="daisy-label-text">Minus</span>
                    <input
                        type="radio"
                        name="radio-10"
                        class="daisy-radio checked:bg-red-500"
                        v-model="form.type"
                        :value="1"
                    />
                </label>
            </div>
            <div class="daisy-form-control">
                <label class="daisy-label cursor-pointer">
                    <span class="daisy-label-text">Plus</span>
                    <input
                        type="radio"
                        name="radio-10"
                        class="daisy-radio checked:bg-blue-500"
                        v-model="form.type"
                        :value="2"
                    />
                </label>
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    type="submit"
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Submit
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
