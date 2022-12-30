<script setup>
import Dialog from "@/Components/Dialog.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import useConfirm from "@/Composables/confirm";
import { Inertia } from "@inertiajs/inertia";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import debounce from "lodash/debounce";
import pickBy from "lodash/pickBy";
import { ref, watch } from "vue";

const props = defineProps({
    search: {
        type: String,
        default: "",
    },
    toppingSearch: {
        type: String,
        default: "",
    },
    items: {
        type: Array,
        required: true,
    },
    toppings: {
        type: Array,
        required: true,
    },
});
const form = useForm({
    customer: "",
    phone: "",
    address: "",
    note: "",
    discount: "",
});

const showChooseTopping = ref(false);

const submit = () => {
    form.transform((data) =>
        pickBy({
            ...data,
            items: selectedItems.value.map((item) => ({
                item_id: item.item.id,
                price: item.price,
                quantity: item.quantity,
            })),
            toppings: selectedToppings.value.map((topping) => ({
                topping_id: topping.topping.id,
                price: topping.price,
                quantity: topping.quantity,
            })),
        })
    ).post(route("orders.preOrder"), {
        onSuccess() {},
    });
};
const search = ref(props.search ?? "");
const toppingSearch = ref("");
const getItems = () => {
    Inertia.visit(route("orders.create"), {
        method: "get",
        replace: true,
        data: pickBy({
            search: search.value,
            toppingSearch: toppingSearch.value,
        }),
        preserveState: true,
    });
};
watch(
    [search, toppingSearch],
    debounce(() => {
        getItems();
    }, 400)
);

const { confirm } = useConfirm();
const removeItem = (index) => {
    confirm(() => {
        selectedItems.value.splice(index, 1);
    }, "Do you want to remove the item?");
};

const removeTopping = (id) => {
    confirm(() => {
        selectedToppings.value.splice(
            selectedToppings.value.findIndex((e) => e.topping.id == id),
            1
        );
    }, "Do you want to remove the topping?");
};

const item = ref(null);
const topping = ref(null);
const price = ref("");
const quantity = ref("");
const toppingQuantity = ref(1);
const selectedItems = ref([]);
const selectedToppings = ref([]);
const showChooseItem = ref(false);
const chooseItem = () => {
    if (!item.value || !price.value || !quantity.value) return;
    const data = {
        item: item.value,
        price: price.value,
        quantity: quantity.value,
    };
    if (selectedItems.value.length == 0) selectedItems.value.push(data);
    else {
        const index = selectedItems.value.findIndex((e) => {
            return e.item.id == item.value.id && e.price == price.value;
        });

        if (index >= 0) selectedItems.value[index].quantity += quantity.value;
        else selectedItems.value.push(data);
    }
    item.value = null;
    price.value = "";
    quantity.value = "";
    showChooseItem.value = false;
};

const chooseTopping = () => {
    if (!topping.value || !toppingQuantity.value) return;
    const data = {
        topping: topping.value,
        quantity: toppingQuantity.value,
    };
    if (selectedToppings.value.length == 0) selectedToppings.value.push(data);
    else {
        const index = selectedToppings.value.findIndex((e) => {
            return e.topping.id == topping.value.id;
        });

        if (index >= 0)
            selectedToppings.value[index].quantity += toppingQuantity.value;
        else selectedToppings.value.push(data);
    }
    topping.value = null;
    toppingQuantity.value = "";
    showChooseTopping.value = false;
};

watch(item, () => {
    if (item.value) price.value = item.value.latest_feature?.price;
});
</script>
<template>
    <Head title="Pre Order" />
    <form @submit.prevent="submit" class="daisy-form-control p-4 space-y-2">
        <div class="text-center text-2xl text-primary">Pre Order</div>
        <div>
            <TextInput
                placeholder="Customer"
                id="customer"
                type="text"
                class="mt-1 block w-full"
                v-model="form.customer"
                required
                autofocus
                :class="{ 'daisy-input-error': form.errors.customer }"
            />
            <InputError :message="form.errors.customer" />
        </div>

        <div>
            <TextInput
                placeholder="Phone"
                id="phone"
                type="tel"
                class="mt-1 block w-full"
                v-model="form.phone"
                required
                :class="{ 'daisy-input-error': form.errors.phone }"
            />
            <InputError :message="form.errors.phone" />
        </div>

        <div>
            <TextInput
                placeholder="Address"
                id="address"
                type="text"
                class="mt-1 block w-full"
                v-model="form.address"
                required
                :class="{ 'daisy-input-error': form.errors.address }"
            />
            <InputError :message="form.errors.address" />
        </div>

        <div>
            <TextInput
                placeholder="Note"
                id="note"
                type="text"
                class="mt-1 block w-full"
                v-model="form.note"
                :class="{ 'daisy-input-error': form.errors.note }"
            />
            <InputError :message="form.errors.note" />
        </div>
        <div>
            <button
                type="button"
                class="daisy-btn daisy-btn-secondary capitalize daisy-btn-sm mr-2"
                @click="showChooseItem = true"
            >
                Choose Item
            </button>
            <button
                type="button"
                class="daisy-btn daisy-btn-secondary capitalize daisy-btn-sm"
                @click="showChooseTopping = true"
            >
                Choose Topping
            </button>
        </div>
        <template v-if="selectedItems.length">
            <table
                class="daisy-table daisy-table-compact w-full daisy-table-zebra"
            >
                <thead class="sticky top-0">
                    <tr>
                        <th></th>
                        <th class="capitalize">Name</th>
                        <th class="text-right capitalize">Price</th>
                        <th class="text-right capitalize">Qty</th>
                        <th class="text-right capitalize">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(value, index) in selectedItems" :key="index">
                        <th @click="removeItem(index)">{{ index + 1 }}</th>
                        <td class="whitespace-pre-wrap">
                            {{ value.item.name }}
                        </td>
                        <td class="text-right">
                            {{ value.price.toLocaleString() }}
                        </td>
                        <td class="text-right text-info">
                            {{ value.quantity }}
                        </td>

                        <td class="text-right">
                            {{
                                (value.quantity * value.price).toLocaleString()
                            }}
                        </td>
                    </tr>
                    <tr v-for="(value, index) in selectedToppings" :key="index">
                        <th @click="removeTopping(value.id)">
                            {{ index + 1 + selectedItems.length }}
                        </th>
                        <td class="whitespace-pre-wrap">
                            {{ value.topping.name }}
                        </td>
                        <td class="text-right">
                            {{ value.topping.price.toLocaleString() }}
                        </td>
                        <td class="text-right text-info">
                            {{ value.quantity }}
                        </td>

                        <td class="text-right">
                            {{
                                (
                                    value.quantity * value.topping.price
                                ).toLocaleString()
                            }}
                        </td>
                    </tr>
                    <tr class="font-bold">
                        <th class="underline"></th>
                        <td colspan="2">Total</td>
                        <td class="text-right">
                            {{
                                selectedItems.reduce(
                                    (carry, e) => e.quantity + carry,
                                    0
                                ) +
                                selectedToppings.reduce(
                                    (carry, e) => e.quantity + carry,
                                    0
                                )
                            }}
                        </td>

                        <td class="text-right">
                            {{
                                (
                                    selectedItems.reduce(
                                        (carry, e) =>
                                            e.quantity * e.price + carry,
                                        0
                                    ) +
                                    selectedToppings.reduce(
                                        (carry, e) =>
                                            e.quantity * e.topping.price +
                                            carry,
                                        0
                                    )
                                ).toLocaleString()
                            }}
                        </td>
                    </tr>

                    <tr class="font-bold" v-if="form.discount">
                        <th class="underline"></th>
                        <td colspan="2"></td>
                        <td class="text-right">Discount</td>

                        <td class="text-right">
                            {{ form.discount.toLocaleString() }}
                        </td>
                    </tr>
                    <tr class="font-bold">
                        <th class="underline"></th>
                        <td colspan="2"></td>
                        <td class="text-right">Amount</td>

                        <td class="text-right">
                            {{
                                (
                                    selectedItems.reduce(
                                        (carry, e) =>
                                            e.quantity * e.price + carry,
                                        0
                                    ) +
                                    selectedToppings.reduce(
                                        (carry, e) =>
                                            e.quantity * e.topping.price +
                                            carry,
                                        0
                                    ) -
                                    form.discount
                                ).toLocaleString()
                            }}
                        </td>
                    </tr>
                </tbody>
            </table>
            <div>
                <TextInput
                    placeholder="Discount"
                    id="discount"
                    type="tel"
                    class="mt-1 block w-full"
                    v-model.number="form.discount"
                    :class="{ 'daisy-input-error': form.errors.discount }"
                />
                <InputError :message="form.errors.discount" />
            </div>
        </template>

        <div class="flex items-center justify-end">
            <PrimaryButton
                type="submit"
                class="ml-4"
                :class="{ 'opacity-25': form.processing }"
                :disabled="
                    form.processing ||
                    form.discount >
                        selectedItems.reduce(
                            (carry, e) => e.quantity * e.price + carry,
                            0
                        )
                "
            >
                Submit
            </PrimaryButton>
        </div>
    </form>
    <Dialog
        :title="'Choose item'"
        :open="showChooseItem"
        @close="showChooseItem = false"
    >
        <form
            @submit.prevent="chooseItem"
            class="daisy-form-control p-4 space-y-2"
        >
            <TextInput
                type="text"
                class="w-full"
                v-model="search"
                placeholder="Search"
            />
            <div class="flex flex-row w-full justify-start flex-wrap">
                <div
                    class="daisy-badge daisy-badge-primary mr-1 mb-1"
                    :class="{
                        'daisy-badge-info text-white': item?.id == value.id,
                    }"
                    v-for="value in items"
                    :key="value.id"
                    @click="item = value"
                >
                    {{ value.name }}
                </div>
            </div>
            <div class="daisy-divider"></div>

            <div>
                <TextInput
                    id="price"
                    type="tel"
                    class="w-full"
                    v-model.number="price"
                    required
                    placeholder="Price"
                />
            </div>
            <div>
                <TextInput
                    id="quantity"
                    type="tel"
                    class="w-full"
                    v-model.number="quantity"
                    required
                    placeholder="Quantity"
                />
            </div>
            <div class="text-right pt-2">
                <button
                    class="daisy-btn daisy-btn-success daisy-btn-sm capitalize"
                    type="submit"
                >
                    Select
                </button>
            </div>
        </form>
    </Dialog>

    <Dialog
        :title="'Choose topping'"
        :open="showChooseTopping"
        @close="showChooseTopping = false"
    >
        <form
            @submit.prevent="chooseTopping"
            class="daisy-form-control p-4 space-y-2"
        >
            <TextInput
                type="text"
                class="w-full"
                v-model="toppingSearch"
                placeholder="Search"
            />
            <div class="flex flex-row w-full justify-start flex-wrap">
                <div
                    class="daisy-badge daisy-badge-primary mr-1 mb-1"
                    :class="{
                        'daisy-badge-info text-white': topping?.id == value.id,
                    }"
                    v-for="value in toppings"
                    :key="value.id"
                    @click="topping = value"
                >
                    {{ value.name }}
                </div>
            </div>
            <div class="daisy-divider"></div>

            <div>
                <InputLabel for="quantity" value="Quantity" />
                <TextInput
                    id="quantity"
                    type="tel"
                    class="w-full"
                    v-model.number="toppingQuantity"
                    required
                    placeholder="Quantity"
                />
            </div>
            <div class="text-right pt-2">
                <button
                    class="daisy-btn daisy-btn-success daisy-btn-sm capitalize"
                    type="submit"
                >
                    Select
                </button>
            </div>
        </form>
    </Dialog>
</template>
