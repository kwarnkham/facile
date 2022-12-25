<script setup>
import Img from "@/Components/Img.vue";
import { ref } from "vue";
import Collapse from "@/Components/Collapse.vue";
import Pagination from "@/Components/Pagination.vue";
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});
const isWholesalesExpanded = ref(false);
</script>
<template>
    <div class="p-1 flex flex-col pb-6 h-full flex-nowrap">
        <div>
            <div class="text-center w-full font-bold text-xl">
                Name : {{ item.name }}
            </div>
            <div class="indent-5">Description : {{ item.description }}</div>
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

        <div>
            <Collapse
                title="Wholesale prices"
                v-model:checked="isWholesalesExpanded"
                class="shadow-xl w-full rounded-md mt-1"
                v-if="item.wholesales.length"
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
        </div>
        <div class="text-lg font-bold text-center">Features</div>
        <div class="mt-1 w-full flex-grow overflow-y-auto">
            <div class="list-decimal list-inside space-y-2">
                <div
                    v-for="(feature, index) in item.features.data"
                    :key="feature.id"
                    @click="
                        $inertia.visit(
                            route('features.show', { feature: feature.id })
                        )
                    "
                >
                    <span>{{ index + item.features.from }}. </span
                    >{{ feature.name }}
                </div>
            </div>
        </div>
        <div class="text-center">
            <Pagination
                :url="
                    route('items.show', {
                        item: item.id,
                    })
                "
                :data="item.features"
            />
        </div>
    </div>
</template>
