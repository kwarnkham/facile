<script setup>
import { Head } from "@inertiajs/inertia-vue3";
import { PhoneIcon, UserIcon, HashtagIcon } from "@heroicons/vue/24/solid";
import Pagination from "@/Components/Pagination.vue";
import { ref } from "vue";
const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    order_statuses: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
    },
});
const query = ref({
    status: props.filters.status ?? 1,
});
</script>
<template>
    <Head title="Orders" />
    <div class="h-full flex flex-col flex-nowrap p-1">
        <select
            class="daisy-select daisy-select-sm m-1"
            v-model.number="query.status"
        >
            <option
                v-for="(status, key) in order_statuses"
                :key="status"
                :value="key"
            >
                {{ status }}
            </option>
        </select>
        <div class="flex-1 overflow-y-auto">
            <div
                class="daisy-card bg-base-100 shadow-xl mb-1"
                v-if="orders.data.length"
                v-for="order in orders.data"
            >
                <div class="daisy-card-body">
                    <h2 class="daisy-card-title flex justify-between">
                        <span
                            >Amount : {{ order.amount - order.discount }}</span
                        >
                        <span>
                            <HashtagIcon
                                class="w-4 h-4 inline-block text-primary"
                            />
                            {{ order.id }}
                        </span>
                    </h2>
                    <div v-if="order.customer">
                        <UserIcon class="w-4 h-4 inline-block text-primary" />{{
                            order.customer
                        }}
                    </div>
                    <div v-if="order.phone">
                        <PhoneIcon
                            class="w-4 h-4 inline-block text-primary"
                        />{{ order.phone }}
                    </div>

                    <p>
                        Order is
                        <strong>{{ order_statuses[order.status] }}</strong> at
                        {{
                            new Date(order.updated_at).toLocaleString("en-GB", {
                                hour12: true,
                            })
                        }}
                    </p>
                    <div class="daisy-card-actions justify-end">
                        <button
                            @click="
                                $inertia.visit(
                                    route('orders.show', { order: order.id })
                                )
                            "
                            class="daisy-btn daisy-btn-primary daisy-btn-sm capitalize"
                        >
                            See More
                        </button>
                    </div>
                </div>
            </div>
            <div v-else class="text-center text-lg font-bold">No order yet</div>
        </div>

        <div class="text-center">
            <Pagination
                :data="orders"
                :url="route('orders.index')"
                :query="query"
                class="mb-6"
            />
        </div>
    </div>
</template>
