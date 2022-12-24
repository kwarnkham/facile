<script setup>
import Pagination from "@/Components/Pagination.vue";
import { Head } from "@inertiajs/inertia-vue3";

const props = defineProps({
    features: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Stock Summary" />
    <div class="h-full flex flex-col flex-nowrap">
        <div class="flex-grow overflow-y-auto">
            <table
                class="daisy-table daisy-table-compact w-full daisy-table-zebra"
            >
                <thead class="sticky top-0">
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th class="text-right">Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(feature, index) in features.data"
                        :key="feature.id"
                    >
                        <th>{{ index + 1 }}</th>
                        <td>{{ feature.name }}</td>

                        <td
                            class="text-right underline text-info"
                            @click="
                                $inertia.visit(
                                    route('features.edit', {
                                        feature: feature.id,
                                    })
                                )
                            "
                        >
                            {{ feature.stock }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="text-center mt-1 mb-6">
            <Pagination :data="features" :url="route('routes.stock-summary')" />
        </div>
    </div>
</template>
<style scoped>
th {
    text-transform: capitalize;
}
td {
    white-space: normal;
}
.daisy-table th:first-child {
    position: static;
}
</style>
