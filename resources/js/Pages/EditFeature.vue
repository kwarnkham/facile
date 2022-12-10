<script setup>
import Button from "@/Components/Button.vue";
import Collapse from "@/Components/Collapse.vue";
import Dialog from "@/Components/Dialog.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PicturePicker from "@/Components/PicturePicker.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { InformationCircleIcon, PlusIcon } from "@heroicons/vue/24/solid";
import { Inertia } from "@inertiajs/inertia";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import pickBy from "lodash/pickBy";
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
    form.transform((data) => pickBy(data)).put(
        route("features.update", { feature: props.feature.id })
    );
};
const form = useForm({
    name: props.feature.name,
    price: props.feature.price,
    stock: props.feature.stock,
    note: props.feature.note,
    item_id: props.feature.item_id,
});

const purchaseForm = useForm({
    price: "",
    quantity: "",
    expired_on: "",
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
const showPurchaseDialog = ref(false);
const showPurchaseForm = () => {
    showPurchaseDialog.value = true;
};

const purchase = () => {
    purchaseForm
        .transform((data) => pickBy(data))
        .post(route("features.restock", { feature: props.feature.id }), {
            preserveState: false,
        });
};
const showBatchesDialog = ref(false);

const showBatches = () => {
    showBatchesDialog.value = true;
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
                        type="tel"
                        class="w-full"
                        v-model.number="form.price"
                        required
                        :class="{ 'daisy-input-error': form.errors.price }"
                    />
                    <InputError :message="form.errors.price" />
                </div>

                <div>
                    <div class="flex flex-row items-center">
                        <InputLabel for="stock" value="Stock" />
                        <PlusIcon class="w-6 h-6" @click="showPurchaseForm" />
                        <InformationCircleIcon
                            class="w-6 h-6"
                            @click="showBatches"
                        />
                    </div>
                    <TextInput
                        id="stock"
                        type="tel"
                        class="w-full"
                        v-model.number="form.stock"
                        required
                        :class="{ 'daisy-input-error': form.errors.stock }"
                        disabled
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

        <Dialog
            :open="showPurchaseDialog"
            title="Purchase"
            @close="showPurchaseDialog = false"
        >
            <form @submit.prevent="purchase">
                <div>
                    <InputLabel for="purchasePrice" value="Price" />
                    <TextInput
                        id="purchasePrice"
                        type="tel"
                        class="w-full"
                        v-model.number="purchaseForm.price"
                        required
                        placeholder="Amount"
                        :autofocus="showPurchaseDialog"
                    />
                </div>
                <div>
                    <InputLabel for="purchaseQuantity" value="Quantity" />
                    <TextInput
                        id="purchaseQuantity"
                        type="tel"
                        class="w-full"
                        v-model.number="purchaseForm.quantity"
                        required
                        placeholder="Quantity"
                    />
                </div>
                <div>
                    <InputLabel for="expiredOn" value="Expire Date" />
                    <TextInput
                        id="expiredOn"
                        type="date"
                        class="w-full"
                        v-model="purchaseForm.expired_on"
                        placeholder="Expire Date"
                    />
                </div>
                <div class="text-right pt-2">
                    <Button :disabled="purchaseForm.processing" type="submit">
                        Submit
                    </Button>
                </div>
            </form>
        </Dialog>

        <Dialog
            :open="showBatchesDialog"
            title="Batches"
            @close="showBatchesDialog = false"
        >
            <div v-for="batch in feature.batches" :key="batch.id">
                <div class="flex flex-row justify-between">
                    <div>{{ batch.expired_on ?? "No expired date" }}</div>
                    <div>{{ batch.stock }}</div>
                </div>
            </div>

            <div class="text-right border-t border-t-primary border-dashed">
                {{
                    feature.batches.reduce(
                        (carry, batch) => carry + batch.stock,
                        0
                    )
                }}
            </div>
        </Dialog>
    </div>
</template>
