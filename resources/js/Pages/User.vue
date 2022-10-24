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
});
const search = ref(props.filters.search);
const visitPage = (page) => {
    Inertia.visit(route("users.show", { user: props.user.id }), {
        method: "get",
        replace: true,
        data: pickBy({
            status: props.filters.status,
            search: search.value,
            page,
        }),
        preserveState: true,
    });
};

watch(
    search,
    debounce(() => {
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
                }),
                preserveState: true,
            }
        );
    }, 400)
);
</script>

<template>
    <div class="h-full flex flex-col p-1">
        <div class="h-40 w-full bg-black rounded-md"></div>
        <div class="text-center text-xl font-bold">{{ user.name }}</div>
        <div class="font-semibold">{{ user.merchant.description }}</div>
        <div class="text-sm">{{ user.merchant.address }}</div>

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
