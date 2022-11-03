<script setup>
import Collapse from "@/Components/Collapse.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PicturePicker from "@/Components/PicturePicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Inertia } from "@inertiajs/inertia";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { ref } from "vue";

const props = defineProps({
    feature: {
        type: Object,
        required: true,
    },
    edit: {
        required: false,
        default: "info",
    },
});

const isItemExpanded = ref(props.edit == "item");
const isFeatureExpanded = ref(props.edit == "info");
const isPicturesExpanded = ref(props.edit == "pictures");
const submit = () => {
    form.put(route("features.update", { feature: props.feature.id }));
};
const form = useForm({
    name: props.feature.name,
    price: props.feature.price,
    stock: props.feature.stock,
    note: props.feature.note,
    item_id: props.feature.item_id,
});

const deletingPicture = ref(false);
const deletePicture = (id) => {
    deletingPicture.value = true;
    Inertia.delete(route("pictures.destroy", { picture: id }), {
        onFinish() {
            deletingPicture.value = false;
        },
    });
};
</script>

<template>
    <div class="h-full p-1">
        <Head title="Edit Feature" />
        <div class="text-lg font-bold text-center">Edit Feature</div>
        <Collapse
            title="Item"
            v-model:checked="isItemExpanded"
            class="shadow-xl"
        >
            <div>Name: {{ feature.item.name }}</div>
            <div>Price: {{ feature.item.price }}</div>
            <div>Description: {{ feature.item.description }}</div>
        </Collapse>

        <Collapse
            title="Feature"
            v-model:checked="isFeatureExpanded"
            class="shadow-xl"
        >
            <form
                @submit.prevent="submit"
                class="p-4 daisy-form-control space-y-2"
            >
                <div class="text-center text-2xl text-primary">Info</div>
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        type="text"
                        class="w-full"
                        v-model="form.name"
                        required
                        autofocus
                        :class="{ 'daisy-input-error': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="price" value="Price" />
                    <TextInput
                        id="price"
                        type="number"
                        class="w-full"
                        v-model="form.price"
                        required
                        :class="{ 'daisy-input-error': form.errors.price }"
                    />
                    <InputError :message="form.errors.price" />
                </div>

                <div>
                    <InputLabel for="stock" value="Stock" />
                    <TextInput
                        id="stock"
                        type="number"
                        class="w-full"
                        v-model="form.stock"
                        required
                        :class="{ 'daisy-input-error': form.errors.stock }"
                    />
                    <InputError :message="form.errors.stock" />
                </div>

                <div>
                    <InputLabel for="note" value="Note" />
                    <textarea
                        id="note"
                        class="w-full daisy-textarea daisy-textarea-primary"
                        placeholder="Note"
                        v-model="form.note"
                        required
                        :class="{
                            'daisy-input-error': form.errors.note,
                        }"
                        rows="3"
                    ></textarea>
                    <InputError :message="form.errors.note" />
                </div>

                <div class="flex items-center justify-end">
                    <PrimaryButton
                        type="submit"
                        class="ml-4"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing || !form.isDirty"
                    >
                        Update Item
                    </PrimaryButton>
                </div>
            </form>
        </Collapse>

        <Collapse
            :title="'Pictures'"
            v-model:checked="isPicturesExpanded"
            class="shadow-xl"
        >
            <div
                class="flex flex-row flex-nowrap h-52 items-center space-x-2 w-full overflow-x-auto scroll-smooth"
                :class="{ 'justify-center': feature.pictures.length == 0 }"
            >
                <div class="shrink-0">
                    <PicturePicker
                        multiple
                        type="feature"
                        :pictureable="feature"
                    />
                </div>

                <figure
                    v-for="picture in feature.pictures"
                    :key="picture.id"
                    class="relative shrink-0"
                >
                    <button
                        class="absolute top-1 right-1 daisy-btn daisy-btn-sm daisy-btn-error capitalize"
                        :disabled="deletingPicture"
                        @click="deletePicture(picture.id)"
                    >
                        Delete
                    </button>
                    <img :src="picture.name" :alt="picture.name" class="h-52" />
                </figure>
            </div>
        </Collapse>
    </div>
</template>
