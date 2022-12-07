<script setup>
import Button from "@/Components/Button.vue";
import Dialog from "@/Components/Dialog.vue";
import { Inertia } from "@inertiajs/inertia";
import { Head } from "@inertiajs/inertia-vue3";
import { ref } from "vue";

const props = defineProps({
    purchases: {
        type: Array,
        required: true,
    },
});
const confirm = ref(false);
const purchaseBeingCanceled = ref(null);
const confirmCancelPurchase = (purchase) => {
    confirm.value = true;
    purchaseBeingCanceled.value = purchase;
};
const cancelPurchase = () => {
    confirm.value = false;
    Inertia.post(
        route("purchases.cancel", { purchase: purchaseBeingCanceled.value.id })
    );
};
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
                    @click="confirmCancelPurchase(purchase)"
                    :disabled="purchase.status == 2"
                >
                    Cancel
                </Button>
            </div>
        </div>
        <Dialog title="Confirm cancel" :open="confirm">
            <div class="flex flex-row justify-end space-x-2">
                <Button @click="cancelPurchase"> Yes </Button>
                <Button @click="confirm = false"> Cancel </Button>
            </div>
        </Dialog>
    </div>
</template>
