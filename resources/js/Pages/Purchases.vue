<script setup>
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Pagination from "@/Components/Pagination.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import useConfirm from "@/Composables/confirm";
import { Inertia } from "@inertiajs/inertia";
import { Head, useForm } from "@inertiajs/inertia-vue3";

const props = defineProps({
    purchases: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
    total: {
        type: Number,
    },
});

const { confirm } = useConfirm();

const cancelPurchase = (purchase) => {
    Inertia.post(route("purchases.cancel", { purchase: purchase.id }));
};

const submit = () => {
    form.get(route("purchases.index"));
};
const from = new Date(props.filters.from);
const to = new Date(props.filters.to);
const today = new Date();
const form = useForm({
    from: from.toLocaleDateString("en-GB", {}).split("/").reverse().join("-"),
    to: to.toLocaleDateString("en-GB", {}).split("/").reverse().join("-"),
});
</script>

<template>
    <Head title="Purchases" />
    <div class="h-full flex flex-col flex-nowrap p-2">
        <form @submit.prevent="submit" class="daisy-form-control space-y-2">
            <div class="text-center font-bold">
                Total {{ total.toLocaleString() }} MMK
            </div>
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
        <div class="flex-1 overflow-y-auto">
            <div
                v-if="purchases.data.length"
                v-for="purchase in purchases.data"
                :key="purchase.id"
                class="border-b border-b-primary"
            >
                <div>Name {{ purchase.purchasable.name }}</div>
                <div>Price {{ purchase.price }}</div>
                <div v-if="purchase.quantity > 1">
                    Quantity {{ purchase.quantity }}
                </div>

                <div v-if="purchase.purchasable_type == 'App\\Models\\Feature'">
                    Type : Item
                </div>
                <div v-if="purchase.purchasable_type == 'App\\Models\\Expense'">
                    Type : Expense
                </div>
                <div v-if="purchase.note">
                    {{ purchase.note }}
                </div>
                <div>
                    {{ new Date(purchase.created_at).toLocaleString("en-GB") }}
                </div>
                <div class="text-right mb-1">
                    <Button
                        @click="
                            confirm(() => {
                                cancelPurchase(purchase);
                            })
                        "
                        :disabled="purchase.status == 2"
                    >
                        Cancel
                    </Button>
                </div>
            </div>
            <div v-else class="text-center text-lg font-bold">
                No purchase yet
            </div>
        </div>
        <div class="text-center mb-6">
            <Pagination
                :data="purchases"
                :url="route('purchases.index')"
                :query="filters"
            />
        </div>
    </div>
</template>
