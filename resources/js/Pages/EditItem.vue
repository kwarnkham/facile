<script setup>
import Collapse from "@/Components/Collapse.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { ref } from "vue";

const props = defineProps({
    item: {
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

const submit = () => {
    form.put(route("items.update", { item: props.item.id }));
};
const isInfoExpanded = ref(props.edit == "info");
const isPicturesExpanded = ref(props.edit == "picture");
</script>

<template>
    <Head title="Edit Item" />
    <Collapse :title="'Item Info'" v-model:checked="isInfoExpanded">
        <form @submit.prevent="submit" class="p-4 daisy-form-control space-y-2">
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
                    type="primary"
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Update
                </PrimaryButton>
            </div>
        </form>
    </Collapse>
    <Collapse :title="'Pictures'" v-model:checked="isPicturesExpanded">
        <div
            class="flex flex-row flex-nowrap h-52 items-center space-x-2 w-full overflow-x-auto scroll-smooth justify-evenly"
        >
            <div>
                <button class="daisy-btn daisy-btn-sm capitalize">Add</button>
            </div>

            <figure
                v-for="picture in item.pictures"
                :key="picture.id"
                class="relative"
            >
                <button
                    class="absolute top-1 right-1 daisy-btn daisy-btn-sm daisy-btn-error capitalize"
                    @click="
                        $inertia.delete(
                            route('pictures.destroy', { picture: picture.id })
                        )
                    "
                >
                    Delete
                </button>
                <img :src="picture.name" :alt="picture.name" class="h-52" />
            </figure>
        </div>
    </Collapse>
</template>
