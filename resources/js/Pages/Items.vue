<script setup>
import { ref, watch } from "vue";
import pickBy from "lodash/pickBy";
import debounce from "lodash/debounce";
import { Inertia } from "@inertiajs/inertia";
import Button from "@/Components/Button.vue";

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
    <div class="flex flex-col h-full">
        <div class="text-center">
            <input type="text" placeholder="Search here" v-model="search" />
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
                <div class="daisy-card-body bg-white/20">
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

        <div>Items: page {{ items.current_page }} of {{ items.last_page }}</div>
        <div class="flex justify-between">
            <button @click="visitPage(items.current_page - 1)">prev</button>
            <button @click="visitPage(items.current_page + 1)">next</button>
        </div>
    </div>
</template>
