<script setup>
import Button from "@/Components/Button.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { store } from "@/store";
import { XCircleIcon } from "@heroicons/vue/24/outline";
import { Inertia } from "@inertiajs/inertia";
import { Head } from "@inertiajs/inertia-vue3";
import { computed, ref } from "vue";

const open = ref(false);

const cartFeatureInEdit = ref({ quantity: 0 });
const editCartFeature = (feature) => {
    open.value = true;
    cartFeatureInEdit.value = JSON.parse(JSON.stringify(feature));
};
const checkout = () => {
    localStorage.setItem("cartDiscount", JSON.stringify(discount.value));
    Inertia.visit(route("routes.checkout"));
};
const cartTotal = computed(() =>
    store.cart.items.reduce((carry, e) => e.quantity * e.price + carry, 0)
);

const discount = ref(JSON.parse(localStorage.getItem("cartDiscount")) ?? "");

const removeFromCart = () => {
    store.cart.remove(
        cartFeatureInEdit.value,
        store.cart.items.find((e) => e.id == cartFeatureInEdit.value.id)
            .quantity
    );
    open.value = false;
};

const updateCartFeature = () => {
    store.cart.update(cartFeatureInEdit.value);
    open.value = false;
};

const clearCart = () => {
    store.cart.clear();
    Inertia.visit(route("items.index"), {
        replace: true,
    });
};
</script>
<template>
    <div class="h-full overflow-y-auto flex flex-col p-1 pb-8">
        <Head title="Cart" />
        <template v-if="store.cart.items.length">
            <div class="text-right mb-2">
                <Button @click="clearCart">Clear Cart</Button>
            </div>
            <table
                class="daisy-table daisy-table-compact w-full daisy-table-zebra"
            >
                <thead class="sticky top-0">
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(feature, index) in store.cart.items"
                        :key="feature.id"
                    >
                        <th>{{ index + 1 }}</th>
                        <td>{{ feature.name }}</td>
                        <td class="text-right">
                            {{ feature.price?.toLocaleString() }}
                        </td>
                        <td
                            class="text-right underline text-info"
                            @click="editCartFeature(feature)"
                        >
                            {{ feature.quantity }}
                        </td>

                        <td class="text-right">
                            {{
                                (
                                    feature.quantity * feature.price
                                ).toLocaleString()
                            }}
                        </td>
                    </tr>
                    <tr class="font-bold">
                        <th class="underline"></th>
                        <td colspan="2">Total</td>
                        <td class="text-right">
                            {{
                                store.cart.items.reduce(
                                    (carry, e) => e.quantity + carry,
                                    0
                                )
                            }}
                        </td>

                        <td class="text-right">
                            {{ cartTotal.toLocaleString() }}
                        </td>
                    </tr>
                    <tr class="font-bold">
                        <th class="underline"></th>
                        <td colspan="2"></td>
                        <td class="text-right">Discount</td>

                        <td class="text-right">
                            <input
                                type="number"
                                v-model.number="discount"
                                class="w-16 text-right"
                            />
                        </td>
                    </tr>
                    <tr
                        class="font-bold"
                        :class="{
                            'text-error': cartTotal - discount < 0,
                        }"
                    >
                        <th class="underline"></th>
                        <td colspan="2"></td>
                        <td class="text-right">Amount</td>

                        <td class="text-right">
                            {{ (cartTotal - discount).toLocaleString() }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div class="flex-1 flex items-end justify-end">
                <Button @click="checkout" :disabled="cartTotal - discount < 0"
                    >Checkout</Button
                >
            </div>
        </template>
        <div
            v-else
            class="w-full h-full flex justify-center items-center flex-wrap content-center"
        >
            <div class="w-full text-center">No item in the cart yet.</div>
            <div>
                <Button
                    @click="$inertia.visit(route('index'), { replace: true })"
                    >Home</Button
                >
            </div>
        </div>
        <Teleport to="body">
            <div
                class="daisy-modal daisy-modal-bottom sm:daisy-modal-middle"
                :class="{ 'daisy-modal-open': open }"
            >
                <div class="daisy-modal-box">
                    <h3 class="font-bold text-lg">
                        Edit <span>{{ cartFeatureInEdit?.name }}</span>
                    </h3>
                    <div class="py-4">
                        <InputLabel for="quantity" value="Quantity" />
                        <TextInput
                            id="quantity"
                            type="number"
                            class="w-full"
                            v-model.number="cartFeatureInEdit.quantity"
                            required
                        />
                    </div>
                    <div class="flex flex-row justify-evenly">
                        <Button
                            @click="cartFeatureInEdit.quantity++"
                            :disabled="
                                cartFeatureInEdit.quantity >=
                                cartFeatureInEdit.stock
                            "
                        >
                            Increase
                        </Button>
                        <Button
                            @click="cartFeatureInEdit.quantity--"
                            :disabled="cartFeatureInEdit.quantity <= 0"
                        >
                            Decrease
                        </Button>
                        <Button @click="removeFromCart">Remove</Button>
                    </div>
                    <div class="daisy-modal-action items-center space-x-2">
                        <XCircleIcon class="w-8 h-8" @click="open = false" />
                        <Button
                            @click="updateCartFeature"
                            :disabled="
                                cartFeatureInEdit.quantity % 1 != 0 ||
                                cartFeatureInEdit.quantity >
                                    cartFeatureInEdit.stock ||
                                cartFeatureInEdit.quantity < 0 ||
                                cartFeatureInEdit.quantity === ''
                            "
                            >Ok</Button
                        >
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
th {
    text-transform: capitalize;
}
td {
    white-space: normal;
}
.daisy-table th:first-child {
    position: static;
}
</style>
