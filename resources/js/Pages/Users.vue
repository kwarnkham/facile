<script setup>
defineProps({
    users: {
        type: Object,
        required: true,
    },
});
</script>
<template>
    <div class="h-full flex flex-col">
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
    </div>
</template>
