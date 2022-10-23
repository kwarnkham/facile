<script setup>
import { Inertia } from "@inertiajs/inertia";
import pickBy from "lodash/pickBy";
import { ref, watch } from "vue";
import TextInput from "@/Components/TextInput.vue";
import { MagnifyingGlassCircleIcon } from "@heroicons/vue/24/solid";
import debounce from "lodash/debounce";
const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
    },
});
const search = ref(props.filters.search);
const visitPage = (page) => {
    Inertia.visit(route("users.index"), {
        method: "get",
        data: pickBy({
            role: props.filters.role,
            search: search.value,
            page,
        }),
        preserveState: true,
    });
};

watch(
    search,
    debounce(() => {
        Inertia.visit(route("users.index"), {
            method: "get",
            data: pickBy({
                role: props.filters.role,
                search: search.value,
            }),
            preserveState: true,
        });
    }, 400)
);
</script>
<template>
    <div class="h-full flex flex-col">
        <div class="py-2 pr-2 flex flex-row items-center">
            <div class="px-1">
                <MagnifyingGlassCircleIcon class="w-8 h-8 text-primary" />
            </div>
            <TextInput placeholder="Search" class="flex-1" v-model="search" />
        </div>
        <div class="overflow-y-auto flex-grow flex-shrink-0 basis-0">
            <div v-for="user in users.data" :key="user.id" class="p-2">
                <div class="daisy-card bg-base-100 shadow-xl">
                    <figure>
                        <img
                            :src="'https://placeimg.com/400/225/arch'"
                            alt="Merchant Avatar"
                        />
                    </figure>
                    <div class="daisy-card-body">
                        <h2 class="daisy-card-title">{{ user.name }}</h2>
                        <p>{{ user.merchant?.description }}</p>
                        <p>{{ user.merchant?.address }}</p>
                        <div class="daisy-card-actions justify-end">
                            <button
                                class="daisy-btn daisy-btn-primary daisy-btn-sm capitalize"
                            >
                                Visit Shop
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="py-2 text-center">
            <div class="daisy-btn-group">
                <button
                    class="daisy-btn"
                    :class="{
                        'daisy-btn-disabled text-gray-500':
                            users.current_page == 1,
                        'text-info': users.current_page != 1,
                    }"
                    @click="visitPage(1)"
                >
                    «
                </button>
                <button
                    class="daisy-btn capitalize"
                    :class="{
                        'daisy-btn-disabled text-gray-500':
                            !users.prev_page_url,
                        'text-info': users.prev_page_url,
                    }"
                    @click="visitPage(users.current_page - 1)"
                >
                    Prev
                </button>
                <button
                    class="daisy-btn daisy-btn-primary pointer-events-none capitalize"
                >
                    Page {{ users.current_page }} of {{ users.last_page }}
                </button>
                <button
                    class="daisy-btn capitalize"
                    :class="{
                        'daisy-btn-disabled text-gray-500':
                            !users.next_page_url,
                        'text-info': users.next_page_url,
                    }"
                    @click="visitPage(users.current_page + 1)"
                >
                    Next
                </button>
                <button
                    class="daisy-btn"
                    :class="{
                        'daisy-btn-disabled text-gray-500':
                            users.current_page == users.last_page,
                        'text-info': users.current_page != users.last_page,
                    }"
                    @click="visitPage(users.last_page)"
                >
                    »
                </button>
            </div>
        </div>
    </div>
</template>
