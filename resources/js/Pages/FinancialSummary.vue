<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";

const props = defineProps({
    summary: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});
const from = new Date(props.filters.from);
const to = new Date(props.filters.to);
const today = new Date();
const form = useForm({
    from: from.toLocaleDateString("en-GB", {}).split("/").reverse().join("-"),
    to: to.toLocaleDateString("en-GB", {}).split("/").reverse().join("-"),
});
const submit = () => {
    form.get(route("routes.financial-summary"));
};
</script>

<template>
    <Head title="Financial Summary" />
    <div class="p-1">
        <form @submit.prevent="submit" class="daisy-form-control p-4 space-y-2">
            <div>
                <InputLabel for="from" value="From" />
                <TextInput
                    id="from"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.from"
                    required
                    :class="{ 'daisy-input-error': form.errors.from }"
                    min="1899-01-01"
                    :max="
                        today
                            .toLocaleDateString('en-GB', {})
                            .split('/')
                            .reverse()
                            .join('-')
                    "
                />
                <InputError :message="form.errors.from" />
            </div>

            <div>
                <InputLabel for="to" value="To" />
                <TextInput
                    id="to"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.to"
                    required
                    :class="{ 'daisy-input-error': form.errors.to }"
                    :min="form.from"
                    :max="
                        today
                            .toLocaleDateString('en-GB', {})
                            .split('/')
                            .reverse()
                            .join('-')
                    "
                />
                <InputError :message="form.errors.to" />
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
        <div class="text-center text-lg font-bold">Financial Summary</div>
        <div class="flex flex-row justify-between">
            <span>Completed Orders :</span>
            <span
                >{{
                    summary.completed_orders
                        .reduce(
                            (carry, e) => carry + (e.amount - e.discount),
                            0
                        )
                        .toLocaleString()
                }}
                MMK</span
            >
        </div>
        <div class="flex flex-row justify-between">
            <span> Purchases :</span>
            <span
                >{{
                    summary.purchases
                        .reduce((carry, e) => carry + e.price * e.quantity, 0)
                        .toLocaleString()
                }}
                MMK</span
            >
        </div>
        <div class="daisy-divider"></div>
        <div class="flex flex-row justify-between">
            <span> Net Total :</span>
            <span
                >{{
                    (
                        summary.completed_orders.reduce(
                            (carry, e) => carry + (e.amount - e.discount),
                            0
                        ) -
                        summary.purchases.reduce(
                            (carry, e) => carry + e.price * e.quantity,
                            0
                        )
                    ).toLocaleString()
                }}
                MMK</span
            >
        </div>
    </div>
</template>
