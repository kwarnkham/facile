<script setup>
import { ref } from "vue";
import Button from "@/Components/Button.vue";
import Pagination from "@/Components/Pagination.vue";
import TextInput from "@/Components/TextInput.vue";
import { MagnifyingGlassCircleIcon } from "@heroicons/vue/24/solid";

const props = defineProps({
    items: {
        required: true,
        type: Object,
    },
    filters: {
        type: Object,
    },
});

const query = ref({
    search: props.filters.search ?? "",
    merchant_id: props.filters.merchant_id ?? "",
});
</script>

<template>
    <div class="flex flex-col h-full p-2 pb-6">
        <div
            class="mb-2 flex flex-row flex-nowrap justify-between items-center space-x-2"
        >
            <div>
                <MagnifyingGlassCircleIcon class="w-8 h-8 text-primary" />
            </div>
            <TextInput
                placeholder="Search"
                class="flex-1"
                v-model="query.search"
            />
        </div>
        <div class="flex-grow flex-shrink-0 basis-0 overflow-y-auto space-y-2">
            <div
                class="daisy-card bg-base-100 shadow-xl w-11/12 mx-auto h-52 bg-contain bg-no-repeat bg-center"
                :style="{
                    backgroundImage: item.pictures?.length
                        ? 'url(' + item.pictures[0].name + ')'
                        : 'none',
                }"
                v-for="item in items.data"
                :key="item.id"
            >
                <div class="daisy-card-body bg-white/50">
                    <h2 class="daisy-card-title">{{ item.name }}</h2>
                    <p>{{ item.description }}</p>
                    <p class="text-right">{{ item.price }} MMK</p>
                    <div class="daisy-card-actions justify-end space-x-2">
                        <Button
                            @click="
                                $inertia.visit(
                                    route('items.edit', { item: item.id })
                                )
                            "
                        >
                            Edit
                        </Button>
                        <Button
                            @click="
                                $inertia.visit(
                                    route('items.show', { item: item.id })
                                )
                            "
                        >
                            See More
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center">
            <Pagination
                :data="items"
                :url="route('items.index')"
                :query="query"
            />
        </div>
    </div>
</template>
