<script setup>
import ItemList from "@/Components/ItemList.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link } from "@inertiajs/inertia-vue3";
import { ref, watch } from "vue";
import { MagnifyingGlassCircleIcon } from "@heroicons/vue/24/solid";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import pickBy from "lodash/pickBy";
import Pagination from "@/Components/Pagination.vue";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    filters: {
        required: true,
        type: Object,
    },
    tags: {
        required: true,
        type: Object,
    },
});
const search = ref(props.filters.search);
const selected_tags = ref(
    props.filters.selected_tags ?? props.tags.map((tag) => tag.id).join(",")
);
const isCollapsed = ref(false);
const visitPage = (page) => {
    Inertia.visit(route("users.show", { user: props.user.id }), {
        method: "get",
        replace: true,
        data: pickBy({
            status: props.filters.status,
            search: search.value,
            selected_tags: selected_tags.value,
            page,
        }),
        preserveState: true,
    });
};
const fetchWithFilters = () => {
    Inertia.visit(
        route("users.show", {
            user: props.user.id,
        }),
        {
            method: "get",
            replace: true,
            data: pickBy({
                status: props.filters.status,
                search: search.value,
                selected_tags: selected_tags.value,
            }),
            preserveState: true,
        }
    );
};
const toggleSelectedTags = (tag_id) => {
    const tag_ids = selected_tags.value.split(",");
    const index = tag_ids.findIndex((e) => e == tag_id);
    if (index >= 0) {
        if (tag_ids.length > 1)
            selected_tags.value = tag_ids.filter((e) => e != tag_id).join(",");
    } else {
        selected_tags.value = [...tag_ids, tag_id].join(",");
    }
};

watch(search, debounce(fetchWithFilters, 400));
watch(selected_tags, fetchWithFilters);
</script>

<template>
    <div class="h-full flex flex-col p-1">
        <div class="daisy-collapse daisy-collapse-arrow">
            <input type="checkbox" v-model="isCollapsed" class="min-h-8" />
            <div class="daisy-collapse-title text-xl font-bold min-h-8 py-2">
                {{ user.name }}
            </div>
            <div class="daisy-collapse-content">
                <div class="h-40 w-full bg-black rounded-md"></div>
                <div class="font-semibold">{{ user.merchant.description }}</div>
                <div class="text-sm">{{ user.merchant.address }}</div>
            </div>
        </div>

        <div class="w-full flex flex-row justify-center shadow-md mb-1">
            <div class="daisy-tabs">
                <Link
                    class="daisy-tab daisy-tab-bordered"
                    :class="{
                        'daisy-tab-active pointer-events-none':
                            filters.status == 2,
                    }"
                    :href="route('users.show', { status: 2, user: user.id })"
                >
                    Featured
                </Link>
                <Link
                    class="daisy-tab daisy-tab-bordered"
                    :class="{
                        'daisy-tab-active pointer-events-none':
                            filters.status == 1 || !filters.status,
                    }"
                    :href="route('users.show', { status: 1, user: user.id })"
                >
                    All
                </Link>
            </div>
        </div>
        <div class="w-full flex flex-row p-1 mb-1" v-if="filters.status == 1">
            <MagnifyingGlassCircleIcon class="w-8 h-8 text-primary pr-1" />
            <TextInput v-model="search" placeholder="Search" class="flex-1" />
        </div>
        <div
            class="flex flex-row flex-wrap space-x-1 py-1 px-2"
            v-if="filters.status == 1"
        >
            <div
                v-for="tag in tags"
                :key="tag.id"
                class="daisy-badge"
                :class="{
                    'daisy-badge-primary': selected_tags
                        .split(',')
                        .map((e) => Number(e))
                        .includes(tag.id),
                }"
                @click="toggleSelectedTags(tag.id)"
            >
                {{ tag.name }}
            </div>
        </div>
        <ItemList
            :items="user.items.data"
            class="flex-grow basis-0 shrink-0 overflow-y-auto"
        />
        <div
            class="text-center pt-1"
            v-if="filters.status == 1 || !filters.status"
        >
            <Pagination :data="user.items" :navigate="visitPage" />
        </div>
    </div>
</template>
