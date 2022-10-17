<script setup>
import { useForm } from "@inertiajs/inertia-vue3";
import Img from "@/Components/Img.vue";
import { ref } from "vue";
const props = defineProps({
    item: {
        type: Object,
        required: true,
    },
});
const fileInput = ref(null);
const form = useForm({
    pictures: [],
    type: "item",
    type_id: props.item.id,
});
const submit = () => {
    if (form.pictures.length <= 0) return;
    form.post(route("pictures.store"));
};
const removeFromFile = (picture) => {
    form.pictures.splice(
        form.pictures.findIndex((e) => e.name == picture.name),
        1
    );
    if (form.pictures.length == 0) fileInput.value.value = "";
};
const theURL = URL;
</script>
<template>
    <div>Item</div>
    <div>{{ item.name }}</div>
    <div>{{ item.price }}</div>
    <div>{{ item.description }}</div>
    <div v-for="(picture, key) in form.pictures" :key="key">
        <Img
            :src="theURL.createObjectURL(picture)"
            :alt="picture.name"
            @remove="removeFromFile(picture)"
        />
    </div>
    <form @submit.prevent="submit" class="p-10">
        <input
            type="file"
            multiple
            accept="image/*"
            @input="form.pictures = Array.from($event.target.files)"
            class="hidden"
            ref="fileInput"
        />
        <button @click="fileInput.click" v-if="form.pictures.length <= 0">
            choose file
        </button>

        <button type="submit">submit</button>
    </form>
</template>
