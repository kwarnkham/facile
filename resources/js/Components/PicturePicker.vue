<script setup>
import { useForm } from "@inertiajs/inertia-vue3";
import { inject, ref } from "vue";
import imageCompression from "browser-image-compression";

const props = defineProps({
    label: {
        type: String,
        default: "Add",
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    accept: {
        type: String,
        default: "image/*",
    },
    type: {
        type: String,
        required: true,
    },
    pictureable: {
        type: Object,
        required: true,
    },
});

const fileInput = ref(null);
const form = useForm({
    pictures: [],
    type: props.type,
    type_id: props.pictureable.id,
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

const { updateMessage } = inject("message");

const setPictures = (event) => {
    const pictures = Array.from(event.target.files);

    if (pictures.length > 5) {
        updateMessage("Maximun 5 files at a time");
        return;
    }
    clearPictures();
    isChoosing.value = true;
    Promise.all(
        pictures.map(async (picture) => {
            const options = {
                maxSizeMB: 0.7,
            };
            try {
                const compressedFile = await imageCompression(picture, options);
                // console.log(
                //     "compressedFile instanceof Blob",
                //     compressedFile instanceof Blob
                // ); // true
                // console.log(
                //     `compressedFile size ${
                //         compressedFile.size / 1024 / 1024
                //     } MB`
                // ); // smaller than maxSizeMB
                return compressedFile;
            } catch (error) {
                console.error(error.message);
            }
        })
    )
        .then((pictures) => {
            // console.log(pictures);
            form.pictures = pictures;
        })
        .finally(() => {
            isChoosing.value = false;
        });
};

const chooseFile = () => {
    fileInput.value.click();
};

const clearPictures = () => {
    fileInput.value.value = "";
    form.pictures = [];
};
const isChoosing = ref(false);
</script>

<template>
    <form @submit.prevent="submit" class="space-x-2">
        <input
            type="file"
            :multiple="multiple"
            :accept="accept"
            @input="setPictures"
            class="hidden"
            ref="fileInput"
        />
        <button
            class="daisy-btn daisy-btn-sm capitalize"
            type="button"
            @click="chooseFile"
            :disabled="isChoosing"
        >
            {{ label }}
        </button>

        <button
            class="daisy-btn daisy-btn-sm capitalize"
            type="submit"
            v-if="form.pictures.length"
        >
            Upload
        </button>
        <div v-if="form.pictures.length">
            <div class="text-center font-light text-xs p-1 text-stone-500">
                <span class="font-bold">{{ form.pictures.length }}</span> files
                chosen
            </div>
            <div class="text-center">
                <button
                    class="daisy-btn daisy-btn-xs capitalize"
                    type="button"
                    @click="clearPictures"
                >
                    X
                </button>
            </div>
        </div>
        <progress
            v-if="form.progress"
            :value="form.progress.percentage"
            max="100"
        >
            {{ form.progress.percentage }}%
        </progress>
    </form>
</template>
