<script setup>
defineProps({
    feature: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="h-full p-1 flex flex-col">
        <div
            class="daisy-carousel daisy-carousel-center p-4 bg-neutral rounded-box w-full h-60 self-center"
            v-if="feature.pictures.length > 0"
            :class="{
                'space-x-4': feature.pictures.length > 1,
                'justify-center': feature.pictures.length == 1,
            }"
        >
            <div
                class="daisy-carousel-item"
                v-for="picture in feature.pictures"
                :key="picture.id"
            >
                <img
                    :src="picture.name"
                    class="rounded-box"
                    :class="{ 'w-full': feature.pictures.length == 1 }"
                />
            </div>
        </div>
        <div
            class="daisy-card daisy-card-compact bg-primary text-primary-content mt-1"
        >
            <div class="daisy-card-body">
                <div>Item: {{ feature.item.name }} / {{ feature.name }}</div>
                <div>Price: {{ feature.price }}</div>
                <div>Description: {{ feature.item.description }}</div>
                <div>Stock: {{ feature.stock }}</div>
                <div v-if="feature.note">Note: {{ feature.note }}</div>
            </div>
        </div>
    </div>
</template>
