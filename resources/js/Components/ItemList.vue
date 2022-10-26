<script setup>
defineProps({
    items: {
        required: true,
        type: Array,
    },
});
</script>

<template>
    <div class="flex flex-row flex-wrap content-baseline">
        <div
            v-for="item in items"
            :key="item.id"
            class="w-1/2 p-1"
            @click="$inertia.visit(route('items.show', { item: item.id }))"
        >
            <div class="bg-secondary rounded-md text-sm p-1">
                <div class="h-32 bg-black"></div>
                <div class="flex flex-row justify-between">
                    <div
                        class="font-semibold whitespace-nowrap overflow-ellipsis overflow-hidden"
                    >
                        {{ item.name }}
                    </div>
                    <div class="font-extrabold">{{ item.price }}</div>
                </div>
                <div
                    class="whitespace-nowrap overflow-ellipsis overflow-hidden"
                >
                    {{ item.description }}
                </div>
            </div>
        </div>
    </div>
</template>
