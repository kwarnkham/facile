<script setup>
defineProps({
    item: {
        type: Object,
        required: true,
    },
});
</script>
<template>
    <div>Item</div>
    <div>{{ item.name }}</div>
    <div>{{ item.price }}</div>
    <div>{{ item.description }}</div>
</template>
