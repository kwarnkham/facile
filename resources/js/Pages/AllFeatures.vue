<script setup>
import Button from "@/Components/Button.vue";
import Checkbox from "@/Components/Checkbox.vue";
import Pagination from "@/Components/Pagination.vue";
import TextInput from "@/Components/TextInput.vue";
import { MagnifyingGlassCircleIcon } from "@heroicons/vue/24/solid";
import { Head } from "@inertiajs/inertia-vue3";
import { ref } from "vue";

const props = defineProps({
    features: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
    },
});

const query = ref({
    search: props.filters.search ?? "",
    stocked: Boolean(props.filters.stocked) ?? false,
    merchant_id: props.filters.merchant_id,
});
</script>

<template>
    <div class="px-1 py-2 h-full flex flex-col">
        <Head title="All Features" />
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
            <label class="flex items-center cursor-pointer">
                <Checkbox name="only stocked" v-model:checked="query.stocked" />
                <span class="ml-1 daisy-label-text">Only Stocked</span>
            </label>
        </div>
        <div class="flex-grow basis-0 shrink-0 overflow-y-auto">
            <div
                v-for="feature in features.data"
                :key="feature.id"
                class="p-2 bg-slate-300 mb-1 rounded-md flex flex-row flex-nowrap"
            >
                <div
                    class="min-h-full w-32 bg-stone-200 bg-no-repeat bg-contain bg-center"
                    :style="{
                        backgroundImage: feature.pictures.length
                            ? 'url(' + feature.pictures[0]?.name + ')'
                            : 'none',
                    }"
                >
                    <!-- <img
                        :src="feature.pictures[0].name"
                        :alt="feature.name"
                        v-if="feature.pictures.length"
                    /> -->
                </div>
                <div class="flex-1 ml-1">
                    <div>Name: {{ feature.name }}</div>
                    <div>Price: {{ feature.price }} MMK</div>
                    <div>Stock: {{ feature.stock }}</div>
                    <div
                        v-if="feature.note"
                        class="text-ellipsis whitespace-nowrap overflow-hidden w-40"
                    >
                        Note: {{ feature.note }}
                    </div>
                    <div class="flex flex-row justify-between">
                        <Button
                            @click="
                                $inertia.visit(
                                    route('features.show', {
                                        feature: feature.id,
                                    })
                                )
                            "
                            >View</Button
                        >
                        <Button
                            @click="
                                $inertia.visit(
                                    route('features.edit', {
                                        feature: feature.id,
                                    })
                                )
                            "
                            >Edit</Button
                        >
                    </div>
                </div>
            </div>
        </div>
        <div class="mb-5 mt-1 text-center">
            <Pagination
                :data="features"
                :url="route('features.all')"
                :query="query"
            />
        </div>
    </div>
</template>
