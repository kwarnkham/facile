<script setup>
import Button from "@/Components/Button.vue";
import { Head } from "@inertiajs/inertia-vue3";

const props = defineProps({
    purchases: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Purchases" />
    <div class="p-2">
        <div
            v-for="purchase in purchases"
            :key="purchase.id"
            class="border-b border-b-primary"
        >
            <div>Name {{ purchase.purchasable.name }}</div>
            <div>Price {{ purchase.price }}</div>
            <div v-if="purchase.quantity > 1">
                Quantity {{ purchase.quantity }}
            </div>
            <div>
                {{ new Date(purchase.created_at).toLocaleString("en-GB") }}
            </div>
            <div class="text-right mb-1">
                <Button
                    @click="
                        $inertia.post(
                            route('purchases.cancel', { purchase: purchase.id })
                        )
                    "
                    :disabled="purchase.status == 2"
                >
                    Cancel
                </Button>
            </div>
        </div>
    </div>
</template>
