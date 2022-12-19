<script setup>
import Button from "@/Components/Button.vue";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="h-full p-1">
        <div>Name : {{ user.name }}</div>
        <div>Status : {{ user.status }}</div>
        <div>Joined on : {{ user.created_at }}</div>
        <Button
            @click="$inertia.visit(route('items.index', { user: user.id }))"
        >
            Items
        </Button>
    </div>
</template>
