<script setup>
import { ref } from "vue";
import TextInput from "@/Components/TextInput.vue";
import { MagnifyingGlassCircleIcon } from "@heroicons/vue/24/solid";
import Pagination from "@/Components/Pagination.vue";
const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
    },
});

const query = ref({
    search: props.filters.search ?? "",
    role: props.filters.role,
});
</script>
<template>
    <div class="h-full flex flex-col">
        <div class="py-2 pr-2 flex flex-row items-center">
            <div class="px-1">
                <MagnifyingGlassCircleIcon class="w-8 h-8 text-primary" />
            </div>
            <TextInput
                placeholder="Search"
                class="flex-1"
                v-model="query.search"
            />
        </div>
        <div class="overflow-y-auto flex-grow flex-shrink-0 basis-0">
            <div v-for="user in users.data" :key="user.id" class="p-2">
                <div class="daisy-card bg-base-100 shadow-xl">
                    <figure>
                        <img
                            :src="'https://placeimg.com/400/225/arch'"
                            alt="User Avatar"
                        />
                    </figure>
                    <div class="daisy-card-body">
                        <h2 class="daisy-card-title">{{ user.name }}</h2>
                        <div class="daisy-card-actions justify-end">
                            <button
                                class="daisy-btn daisy-btn-primary daisy-btn-sm capitalize"
                                @click="
                                    $inertia.visit(
                                        route('users.show', { user: user.id })
                                    )
                                "
                            >
                                Visit Shop
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="py-2 text-center">
            <Pagination
                :data="users"
                :url="route('users.index')"
                :query="query"
            />
        </div>
    </div>
</template>
