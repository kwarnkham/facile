<script setup>
import Img from "@/Components/Img.vue";
import { ref } from "vue";
import Collapse from "@/Components/Collapse.vue";
import Button from "@/Components/Button.vue";
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});
const isWholesalesExpanded = ref(false);
</script>
<template>
    <div class="p-1 flex flex-col pb-6">
        <div>
            <div class="text-center w-full font-bold text-xl">
                Name : {{ item.name }}
            </div>
            <div class="indent-5">Description : {{ item.description }}</div>
            <div class="text-right">{{ item.price }} MMK</div>
        </div>

        <div
            class="daisy-carousel daisy-carousel-center p-4 bg-neutral rounded-box w-10/12 h-60 self-center"
            v-if="item.pictures.length > 0"
            :class="{
                'space-x-4': item.pictures.length > 1,
                'justify-center': item.pictures.length == 1,
            }"
        >
            <div
                class="daisy-carousel-item"
                v-for="picture in item.pictures"
                :key="picture.id"
            >
                <img
                    :src="picture.name"
                    class="rounded-box"
                    :class="{ 'w-full': item.pictures.length == 1 }"
                />
            </div>
        </div>

        <Collapse
            title="Wholesale prices"
            v-model:checked="isWholesalesExpanded"
            class="shadow-xl w-full rounded-md mt-1"
        >
            <div
                class="flex flex-row justify-between font-bold border-b border-primary"
            >
                <div>Quantity</div>
                <div>Price</div>
            </div>
            <div
                v-for="wholesale in item.wholesales"
                :key="wholesale.id"
                class="flex flex-row justify-between"
            >
                <div>{{ wholesale.quantity }}</div>
                <div>{{ wholesale.price }}</div>
            </div>
        </Collapse>
        <div class="text-lg font-bold text-center">Features</div>
        <div class="flex flex-row flex-wrap justify-evenly mt-1">
            <div
                v-for="feature in item.features.data"
                class="h-40 w-2/5 bg-base-300 rounded-md flex justify-center mb-1"
            >
                <img
                    :src="feature.pictures[0]?.name"
                    :alt="feature.name"
                    class="h-full w-auto"
                />
            </div>
        </div>
        <div
            class="text-right p-1"
            v-if="item.features.total > item.features.to"
        >
            <Button
                @click="
                    $inertia.visit(
                        route('features.index', { item_id: item.id })
                    )
                "
                >More</Button
            >
        </div>
    </div>
</template>
