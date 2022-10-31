<script setup>
import Pagination from "@/Components/Pagination.vue";
import TextInput from "@/Components/TextInput.vue";
import { MagnifyingGlassCircleIcon } from "@heroicons/vue/24/solid";
import { Inertia } from "@inertiajs/inertia";
import { Head } from "@inertiajs/inertia-vue3";
import debounce from "lodash/debounce";
import pickBy from "lodash/pickBy";
import { ref, watch } from "vue";

const props = defineProps({
    item: {
        required: true,
        type: Object,
    },
    features: {
        required: true,
        type: Object,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});
const search = ref(props.filters.search ?? "");
const visitPage = (page) => {
    Inertia.visit(route("features.index"), {
        method: "get",
        replace: true,
        data: pickBy({
            item_id: props.item.id,
            search: search.value,
            page,
        }),
        preserveState: true,
    });
};

watch(
    search,
    debounce(() => {
        Inertia.visit(route("features.index"), {
            method: "get",
            replace: true,
            data: pickBy({
                item_id: props.item.id,
                search: search.value,
            }),
            preserveState: true,
        });
    }, 400)
);
</script>

<template>
    <div class="p-1 h-full flex flex-col">
        <Head title="Create Feature" />
        <div class="text-center text-lg font-bold">
            <div>Features of {{ item.name }}</div>
        </div>
        <div
            class="mb-2 flex flex-row flex-nowrap justify-between items-center"
        >
            <div class="px-1">
                <MagnifyingGlassCircleIcon class="w-8 h-8 text-primary" />
            </div>
            <TextInput placeholder="Search" class="flex-1" v-model="search" />
            <button
                class="daisy-btn daisy-btn-xs capitalize mx-2"
                @click="
                    $inertia.visit(
                        route('features.create', { item_id: item.id })
                    )
                "
            >
                Add New Feature
            </button>
        </div>
        <div class="flex-grow basis-0 shrink-0 overflow-y-auto">
            <div
                v-for="feature in features.data"
                :key="feature.id"
                class="py-10 bg-slate-300 mb-1 rounded-md"
            >
                {{ feature.name }} {{ feature.note }} {{ feature.stock }}
                {{ feature.price }}
            </div>
        </div>
        <div class="mb-5 mt-1 text-center">
            <Pagination :data="features" :navigate="visitPage" />
        </div>
    </div>
</template>
