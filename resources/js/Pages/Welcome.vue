<script setup>
import { Head, Link } from "@inertiajs/inertia-vue3";
</script>

<template>
    <Head title="Welcome" />
    <div class="flex flex-col h-full p-2">
        <Link :href="route('items.create')">Add item</Link>
        <Link :href="route('items.index')">Items List</Link>
    </div>
</template>
