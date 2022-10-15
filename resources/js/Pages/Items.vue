<script setup>
import { ref, watch } from "vue";
import pickBy from "lodash/pickBy";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
    items: {
        required: true,
        type: Object,
    },
    filters: {
        type: Object,
    },
});
const search = ref(props.filters.search);

const visitPage = (page) => {
    Inertia.visit(route("items.index"), {
        method: "get",
        data: pickBy({
            user_id: props.filters.user_id,
            search: search.value,
            page,
        }),
        preserveState: true,
    });
};

watch(
    search,
    debounce(() => {
        Inertia.visit(route("items.index"), {
            method: "get",
            data: pickBy({
                user_id: props.filters.user_id,
                search: search.value,
            }),
            preserveState: true,
        });
    }, 400)
);
</script>

<template>
    <div>Items: page {{ items.current_page }} of {{ items.last_page }}</div>
    <div class="text-center">
        <input type="text" placeholder="Search here" v-model="search" />
    </div>
    <div class="flex justify-between">
        <button @click="visitPage(items.current_page - 1)">prev</button>
        <button @click="visitPage(items.current_page + 1)">next</button>
    </div>
    <div v-for="item in items.data" :key="item.id">
        <button class="border-2 p-1 rounded-md mt-2">
            {{ item.name }} {{ item.price }} {{ item.description }}
        </button>
    </div>
</template>
