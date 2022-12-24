<script setup>
import Button from "@/Components/Button.vue";
import Pagination from "@/Components/Pagination.vue";
import useConfirm from "@/Composables/confirm";
import { Inertia } from "@inertiajs/inertia";
import { Head } from "@inertiajs/inertia-vue3";

const props = defineProps({
    purchases: {
        type: Object,
        required: true,
    },
});
const { confirm } = useConfirm();

const cancelPurchase = (purchase) => {
    Inertia.post(route("purchases.cancel", { purchase: purchase.id }));
};
</script>

<template>
    <Head title="Purchases" />
    <div class="h-full flex flex-col flex-nowrap p-2">
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
            <Pagination :data="purchases" :url="route('purchases.index')" />
        </div>
    </div>
</template>
