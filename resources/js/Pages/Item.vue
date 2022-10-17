<script setup>
import { useForm } from "@inertiajs/inertia-vue3";
defineProps({
    item: {
        type: Object,
        required: true,
    },
});
const form = useForm({
    pictures: [],
});
const submit = () => {
    form.post(route("pictures.store"));
};
</script>
<template>
    <div>Item</div>
    <div>{{ item.name }}</div>
    <div>{{ item.price }}</div>
    <div>{{ item.description }}</div>
    <form @submit.prevent="submit" class="p-10">
        <input
            type="file"
            multiple
            accept="image/*"
            @input="form.pictures = $event.target.files"
        />
        <button type="submit">submit</button>
    </form>
</template>
