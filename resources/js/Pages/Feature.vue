<script setup>
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import ModalPicture from "@/Components/ModalPicture.vue";
import TextInput from "@/Components/TextInput.vue";
import { store } from "@/store";
import { computed, inject, onMounted, ref } from "vue";

const props = defineProps({
    feature: {
        type: Object,
        required: true,
    },
});
const modalImage = ref("");
const showPicture = (picture) => {
    modalImage.value = picture.name;
};

const quantity = ref(
    (store.cart.items.find((e) => e.id == props.feature.id)?.quantity ?? 0) <
        props.feature.stock
        ? 1
        : ""
);
const { updateMessage } = inject("message");
const addToCart = (feature) => {
    store.cart.add(feature, quantity.value);
    updateMessage("Added");
    quantity.value = "";
};

const cartQty = computed(
    () => store.cart.items.find((e) => e.id == props.feature.id)?.quantity ?? 0
);

onMounted(() => {
    setTimeout(() => {
        document.querySelector(".daisy-carousel-item")?.scrollIntoView();
    }, 500);
});
</script>

<template>
    <div class="h-full p-1 flex flex-col space-y-1 pb-6">
        <div
            class="daisy-card daisy-card-compact bg-primary text-primary-content"
        >
            <div class="daisy-card-body">
                <div>Item: {{ feature.item.name }} / {{ feature.name }}</div>
                <div>Price: {{ feature.price }}</div>
                <div>Description: {{ feature.item.description }}</div>
                <div>Stock: {{ feature.stock }}</div>
                <div v-if="feature.note">Note: {{ feature.note }}</div>
            </div>
        </div>
        <div
            class="daisy-carousel p-4 bg-neutral rounded-box w-full h-60"
            v-if="feature.pictures.length > 0"
            :class="{
                'space-x-4': feature.pictures.length > 1,
                'justify-center': feature.pictures.length == 1,
            }"
        >
            <div
                class="daisy-carousel-item"
                v-for="picture in feature.pictures"
                :key="picture.id"
                @click="showPicture(picture)"
            >
                <img
                    :src="picture.name"
                    class="rounded-box"
                    :class="{ 'w-full': feature.pictures.length == 1 }"
                />
            </div>
        </div>
        <div class="text-center text-xl">Cart quantity: {{ cartQty }}</div>
        <div class="flex-1 flex items-end justify-end">
            <div class="mr-2">
                <InputLabel for="quantity" value="Quantity" />
                <TextInput
                    id="quantity"
                    type="number"
                    class="w-full"
                    v-model.number="quantity"
                    :class="{
                        'daisy-input-error': quantity + cartQty > feature.stock,
                    }"
                    required
                />
                <InputError
                    :message="'Quantity is greater than stock'"
                    :class="{ invisible: quantity + cartQty <= feature.stock }"
                />
            </div>
            <Button
                @click="addToCart(feature)"
                class="mb-5"
                :disabled="quantity + cartQty > feature.stock || !quantity"
            >
                Add to cart
            </Button>
        </div>
        <Teleport to="body">
            <ModalPicture
                :open="!!modalImage"
                :src="modalImage"
                @closed="modalImage = ''"
            />
        </Teleport>
    </div>
</template>
