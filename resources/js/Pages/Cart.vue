<script setup>
import Button from "@/Components/Button.vue";
import Collapse from "@/Components/Collapse.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { store } from "@/store";
import { XCircleIcon } from "@heroicons/vue/24/outline";
import { Inertia } from "@inertiajs/inertia";
import { Head } from "@inertiajs/inertia-vue3";
import debounce from "lodash/debounce";
import { computed, ref, watch } from "vue";

const props = defineProps({
    toppings: {
        required: true,
        type: Array,
    },
    toppingSearch: {
        default: "",
        type: String,
    },
});
const openQuantityEdit = ref(false);
const openToppingQuantityEdit = ref(false);
const openPriceEdit = ref(false);
const openToppingPriceEdit = ref(false);
const cartToppingInEdit = ref({ quantity: 0, discount: "" });

const cartFeatureInEdit = ref({ quantity: 0, discount: "" });
const editCartFeatureQuantity = (feature) => {
    openQuantityEdit.value = true;
    cartFeatureInEdit.value = JSON.parse(JSON.stringify(feature));
};
const editCartToppingQuantity = (topping) => {
    openToppingQuantityEdit.value = true;
    cartToppingInEdit.value = JSON.parse(JSON.stringify(topping));
};
const editCartFeaturePrice = (feature) => {
    openPriceEdit.value = true;
    cartFeatureInEdit.value = JSON.parse(JSON.stringify(feature));
};
const editCartToppingPrice = (topping) => {
    openToppingPriceEdit.value = true;
    cartToppingInEdit.value = JSON.parse(JSON.stringify(topping));
};

const removeToppingFromCart = () => {
    store.cart.removeTopping(
        cartToppingInEdit.value,
        store.cart.toppings.find((e) => e.id == cartToppingInEdit.value.id)
            .quantity
    );
    openToppingQuantityEdit.value = false;
};
const checkout = () => {
    localStorage.setItem("cartDiscount", JSON.stringify(discount.value));
    Inertia.visit(route("routes.checkout"));
};
const cartTotal = computed(
    () =>
        store.cart.items.reduce(
            (carry, e) => e.quantity * (e.price - (e.discount ?? 0)) + carry,
            0
        ) +
        store.cart.toppings.reduce(
            (carry, e) => e.quantity * (e.price - (e.discount ?? 0)) + carry,
            0
        )
);

const discount = ref(JSON.parse(localStorage.getItem("cartDiscount")) ?? "");

const removeFromCart = () => {
    store.cart.remove(
        cartFeatureInEdit.value,
        store.cart.items.find((e) => e.id == cartFeatureInEdit.value.id)
            .quantity
    );
    openQuantityEdit.value = false;
};

const updateCartFeature = () => {
    store.cart.update(cartFeatureInEdit.value);
    openPriceEdit.value = false;
    openQuantityEdit.value = false;
};

const clearCart = () => {
    store.cart.clear();
    Inertia.visit(route("items.index"), {
        replace: true,
    });
};

const updateCartTopping = () => {
    store.cart.updateTopping(cartToppingInEdit.value);
    openToppingPriceEdit.value = false;
    openToppingQuantityEdit.value = false;
};

const toppingSearch = ref(props.toppingSearch);

watch(
    toppingSearch,
    debounce(() => {
        searchToppings();
    }, 400)
);

const searchToppings = () => {
    Inertia.visit(
        route("routes.cart", {
            toppingSearch: toppingSearch.value,
        }),
        {
            preserveState: true,
        }
    );
};
const addTopping = (topping) => {
    store.cart.addTopping(topping);
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
                        <th>#</th>
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
                        <td
                            class="text-right"
                            :class="{
                                'text-indigo-500': feature.discount ?? 0 > 0,
                            }"
                            @click="editCartFeaturePrice(feature)"
                        >
                            {{
                                (
                                    feature.price - (feature.discount ?? 0)
                                )?.toLocaleString()
                            }}
                        </td>
                        <td
                            class="text-right underline text-info"
                            @click="editCartFeatureQuantity(feature)"
                        >
                            {{ feature.quantity }}
                        </td>

                        <td class="text-right">
                            {{
                                (
                                    feature.quantity *
                                    (feature.price - (feature.discount ?? 0))
                                ).toLocaleString()
                            }}
                        </td>
                    </tr>

                    <tr
                        v-for="(topping, index) in store.cart.toppings"
                        :key="topping.id"
                    >
                        <th>{{ index + store.cart.items.length + 1 }}</th>
                        <td>{{ topping.name }}</td>
                        <td
                            class="text-right"
                            :class="{
                                'text-indigo-500': topping.discount ?? 0 > 0,
                            }"
                            @click="editCartToppingPrice(topping)"
                        >
                            {{
                                (
                                    topping.price - (topping.discount ?? 0)
                                )?.toLocaleString()
                            }}
                        </td>
                        <td
                            class="text-right underline text-info"
                            @click="editCartToppingQuantity(topping)"
                        >
                            {{ topping.quantity }}
                        </td>

                        <td class="text-right">
                            {{
                                (
                                    topping.quantity *
                                    (topping.price - (topping.discount ?? 0))
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
                                ) +
                                store.cart.toppings.reduce(
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
                                type="tel"
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
            <Collapse title="Topping">
                <TextInput
                    placeholder="Search..."
                    id="topping"
                    type="text"
                    class="mt-1 w-full"
                    v-model="toppingSearch"
                />
                <div class="flex flex-row justify-evenly flex-wrap">
                    <Button
                        v-for="topping in toppings"
                        :key="topping.id"
                        class="mr-1 mt-1"
                        @click="addTopping(topping)"
                    >
                        {{ topping.name }}
                    </Button>
                </div>
            </Collapse>

            <div class="flex-1 flex items-end justify-end">
                <Button
                    @click="checkout"
                    :disabled="
                        cartTotal - discount < 0 || isNaN(Number(discount))
                    "
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
                >
                    Home
                </Button>
            </div>
        </div>

        <Teleport to="body">
            <div
                class="daisy-modal daisy-modal-bottom sm:daisy-modal-middle"
                :class="{ 'daisy-modal-open': openQuantityEdit }"
            >
                <div class="daisy-modal-box">
                    <h3 class="font-bold text-lg">
                        Edit <span>{{ cartFeatureInEdit?.name }}</span>
                    </h3>
                    <div class="py-4">
                        <InputLabel for="quantity" value="Quantity" />
                        <TextInput
                            :autofocus="openQuantityEdit"
                            id="quantity"
                            type="tel"
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
                        <XCircleIcon
                            class="w-8 h-8"
                            @click="openQuantityEdit = false"
                        />
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

        <Teleport to="body">
            <div
                class="daisy-modal daisy-modal-bottom sm:daisy-modal-middle"
                :class="{ 'daisy-modal-open': openToppingQuantityEdit }"
            >
                <div class="daisy-modal-box">
                    <h3 class="font-bold text-lg">
                        Edit <span>{{ cartToppingInEdit?.name }}</span>
                    </h3>
                    <div class="py-4">
                        <InputLabel for="quantity" value="Quantity" />
                        <TextInput
                            :autofocus="openToppingQuantityEdit"
                            id="quantity"
                            type="tel"
                            class="w-full"
                            v-model.number="cartToppingInEdit.quantity"
                            required
                        />
                    </div>
                    <div class="flex flex-row justify-evenly">
                        <Button @click="cartToppingInEdit.quantity++">
                            Increase
                        </Button>
                        <Button
                            @click="cartToppingInEdit.quantity--"
                            :disabled="cartToppingInEdit.quantity <= 0"
                        >
                            Decrease
                        </Button>
                        <Button @click="removeToppingFromCart">Remove</Button>
                    </div>
                    <div class="daisy-modal-action items-center space-x-2">
                        <XCircleIcon
                            class="w-8 h-8"
                            @click="openToppingQuantityEdit = false"
                        />
                        <Button
                            @click="updateCartTopping"
                            :disabled="
                                cartToppingInEdit.quantity % 1 != 0 ||
                                cartToppingInEdit.quantity < 0 ||
                                cartToppingInEdit.quantity === ''
                            "
                            >Ok</Button
                        >
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                class="daisy-modal daisy-modal-bottom sm:daisy-modal-middle"
                :class="{ 'daisy-modal-open': openPriceEdit }"
            >
                <div class="daisy-modal-box">
                    <h3 class="font-bold text-lg">
                        Apply discount for
                        <span>{{ cartFeatureInEdit?.name }}</span>
                    </h3>
                    <div class="py-4">
                        <InputLabel for="discount" value="Discount" />
                        <TextInput
                            :autofocus="openPriceEdit"
                            id="discount"
                            type="tel"
                            class="w-full"
                            v-model.number="cartFeatureInEdit.discount"
                            required
                        />
                    </div>
                    <div class="flex flex-row justify-evenly">
                        <Button
                            @click="cartFeatureInEdit.discount = ''"
                            :disabled="!cartFeatureInEdit.discount"
                        >
                            Remove discount
                        </Button>
                    </div>
                    <div class="daisy-modal-action items-center space-x-2">
                        <XCircleIcon
                            class="w-8 h-8"
                            @click="openPriceEdit = false"
                        />
                        <Button
                            @click="updateCartFeature"
                            :disabled="
                                cartFeatureInEdit.discount % 1 != 0 ||
                                cartFeatureInEdit.discount >
                                    cartFeatureInEdit.price ||
                                cartFeatureInEdit.discount < 0
                            "
                            >Ok</Button
                        >
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div
                class="daisy-modal daisy-modal-bottom sm:daisy-modal-middle"
                :class="{ 'daisy-modal-open': openToppingPriceEdit }"
            >
                <div class="daisy-modal-box">
                    <h3 class="font-bold text-lg">
                        Apply discount for
                        <span>{{ cartToppingInEdit?.name }}</span>
                    </h3>
                    <div class="py-4">
                        <InputLabel for="discount" value="Discount" />
                        <TextInput
                            :autofocus="openToppingPriceEdit"
                            id="discount"
                            type="tel"
                            class="w-full"
                            v-model.number="cartToppingInEdit.discount"
                            required
                        />
                    </div>
                    <div class="flex flex-row justify-evenly">
                        <Button
                            @click="cartToppingInEdit.discount = ''"
                            :disabled="!cartToppingInEdit.discount"
                        >
                            Remove discount
                        </Button>
                    </div>
                    <div class="daisy-modal-action items-center space-x-2">
                        <XCircleIcon
                            class="w-8 h-8"
                            @click="openToppingPriceEdit = false"
                        />
                        <Button
                            @click="updateCartTopping"
                            :disabled="
                                cartToppingInEdit.discount % 1 != 0 ||
                                cartToppingInEdit.discount >
                                    cartToppingInEdit.price ||
                                cartToppingInEdit.discount < 0
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
th,
td {
    padding: 14px 0;
}
.daisy-table th:first-child {
    position: static;
}
</style>
