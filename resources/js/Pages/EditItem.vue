<script setup>
import Collapse from "@/Components/Collapse.vue";
import PicturePicker from "@/Components/PicturePicker.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
    item: {
        required: true,
        type: Object,
    },
    tags: {
        required: true,
        type: Object,
    },
    edit: {
        required: false,
        default: "info",
    },
});
const form = useForm({
    name: props.item.name,
    price: props.item.price,
    description: props.item.description,
});

const tagForm = useForm({
    name: "",
    item_id: props.item.id,
});

const submitTag = () => {
    tagForm.post(route("tags.store"), {
        onSuccess() {
            tagForm.reset("name");
        },
    });
};

const submit = () => {
    if (!form.isDirty) return;
    form.put(route("items.update", { item: props.item.id }));
};
const isInfoExpanded = ref(props.edit == "info");
const isPicturesExpanded = ref(props.edit == "pictures");
const isTagsExpanded = ref(props.edit == "tags");
const deletePicture = (id) => {
    deletingPicture.value = true;
    Inertia.delete(route("pictures.destroy", { picture: id }), {
        onFinish() {
            deletingPicture.value = false;
        },
    });
};
const deletingPicture = ref(false);
</script>

<template>
    <div class="flex flex-col min-h-full">
        <Head title="Edit Item" />
        <Collapse
            :title="'Item Info'"
            v-model:checked="isInfoExpanded"
            class="shadow-xl"
        >
            <form
                @submit.prevent="submit"
                class="p-4 daisy-form-control space-y-2"
            >
                <div class="text-center text-2xl text-primary">Edit Item</div>
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
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        class="w-full daisy-textarea daisy-textarea-primary"
                        placeholder="Description"
                        v-model="form.description"
                        required
                        :class="{
                            'daisy-input-error': form.errors.description,
                        }"
                        rows="3"
                    ></textarea>
                    <InputError :message="form.errors.description" />
                </div>

                <div class="flex items-center justify-end">
                    <PrimaryButton
                        type="submit"
                        class="ml-4"
                        :disabled="form.processing || !form.isDirty"
                    >
                        Update
                    </PrimaryButton>
                </div>
            </form>
        </Collapse>
        <Collapse
            :title="'Tags'"
            v-model:checked="isTagsExpanded"
            class="shadow-xl"
        >
            <form
                @submit.prevent="submitTag"
                class="p-4 daisy-form-control space-y-2 shadow-md mb-2"
            >
                <div class="text-center text-2xl text-primary">
                    Attach new Tag
                </div>
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        type="text"
                        class="w-full"
                        v-model="tagForm.name"
                        required
                        autofocus
                        :class="{ 'daisy-input-error': tagForm.errors.name }"
                    />
                    <InputError :message="tagForm.errors.name" />
                </div>

                <div class="flex items-center justify-end">
                    <PrimaryButton
                        type="submit"
                        class="ml-4"
                        :disabled="tagForm.processing"
                    >
                        Attach
                    </PrimaryButton>
                </div>
            </form>
            <div
                class="flex flex-row justify-evenly flex-wrap items-center space-x-1"
            >
                <button
                    class="daisy-btn daisy-btn-xs lowercase mb-1"
                    v-for="tag in tags"
                    :key="tag.id"
                    :class="{
                        'daisy-btn-success': item.tags.some(
                            (e) => e.id == tag.id
                        ),
                    }"
                    @click="
                        $inertia.post(route('tags.toggle', { tag: tag.id }), {
                            item_id: item.id,
                        })
                    "
                >
                    {{ tag.name }}
                </button>
            </div>
        </Collapse>
        <Collapse
            :title="'Pictures'"
            v-model:checked="isPicturesExpanded"
            class="shadow-xl"
            v-if="false"
        >
            <div
                class="flex flex-row flex-nowrap h-52 items-center space-x-2 w-full overflow-x-auto scroll-smooth"
                :class="{ 'justify-center': item.pictures.length == 0 }"
            >
                <div class="shrink-0">
                    <PicturePicker multiple type="item" :pictureable="item" />
                </div>

                <figure
                    v-for="picture in item.pictures"
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

        <div class="flex-1 flex flex-col-reverse p-1 items-end">
            <div>
                <button
                    class="daisy-btn daisy-btn-sm capitalize"
                    @click="
                        $inertia.visit(
                            route('features.index', { item_id: item.id })
                        )
                    "
                >
                    Features
                </button>
            </div>
        </div>
    </div>
</template>
