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
    form.post(route("pictures.store"), {
        onSuccess: () => {
            form.pictures = [];
            fileInput.value.value = "";
        },
    });
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
    <div class="p-1 w-full flex flex-col items-center">
        <div class="w-full">
            <div class="text-center w-full font-bold text-xl">
                Name : {{ item.name }}
            </div>
            <div class="indent-5">Description : {{ item.description }}</div>
            <div class="text-right">{{ item.price }} MMK</div>
        </div>

        <div
            class="daisy-carousel daisy-carousel-center p-4 bg-neutral rounded-box w-10/12 h-60"
            v-if="item.pictures.length > 0"
            :class="{ 'space-x-4': item.pictures.length > 1 }"
        >
            <div
                class="daisy-carousel-item"
                v-for="picture in item.pictures"
                :key="picture.id"
                :class="{ 'w-full': item.pictures.length == 1 }"
            >
                <img
                    :src="picture.name"
                    class="rounded-box"
                    :class="{ 'w-full': item.pictures.length == 1 }"
                />
            </div>
        </div>

        <div v-for="(picture, key) in form.pictures" :key="key">
            <Img
                :src="theURL.createObjectURL(picture)"
                :alt="picture.name"
                @remove="removeFromFile(picture)"
            />
        </div>
        <form
            @submit.prevent="submit"
            class="p-10"
            v-if="$page.props.auth.user"
        >
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
    </div>
</template>
