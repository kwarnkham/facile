<script setup>
import { useForm } from "@inertiajs/inertia-vue3";
import InputError from "./InputError.vue";
import InputLabel from "./InputLabel.vue";
import PrimaryButton from "./PrimaryButton.vue";
import TextInput from "./TextInput.vue";

const props = defineProps({
    url: {
        type: String,
        required: true,
    },
    from: {
        type: String,
    },
    to: {
        type: String,
    },
    title: {
        type: String,
    },
});
const submit = () => {
    form.get(props.url, {
        replace: true,
    });
};
const from = props.from ? new Date(props.from) : new Date();
const to = props.to ? new Date(props.to) : new Date();
const today = new Date();
const form = useForm({
    from: from.toLocaleDateString("en-GB", {}).split("/").reverse().join("-"),
    to: to.toLocaleDateString("en-GB", {}).split("/").reverse().join("-"),
});
</script>
<template>
    <form @submit.prevent="submit" class="daisy-form-control space-y-2">
        <div class="text-center font-bold" v-if="title">
            {{ title }}
        </div>
        <div>
            <InputLabel for="from" value="From" />
            <TextInput
                id="from"
                type="date"
                class="mt-1 block w-full"
                v-model="form.from"
                required
                :class="{ 'daisy-input-error': form.errors.from }"
                min="1899-01-01"
                :max="
                    today
                        .toLocaleDateString('en-GB', {})
                        .split('/')
                        .reverse()
                        .join('-')
                "
            />
            <InputError :message="form.errors.from" />
        </div>

        <div>
            <InputLabel for="to" value="To" />
            <TextInput
                id="to"
                type="date"
                class="mt-1 block w-full"
                v-model="form.to"
                required
                :class="{ 'daisy-input-error': form.errors.to }"
                :min="form.from"
                :max="
                    today
                        .toLocaleDateString('en-GB', {})
                        .split('/')
                        .reverse()
                        .join('-')
                "
            />
            <InputError :message="form.errors.to" />
        </div>

        <div class="flex items-center justify-end">
            <PrimaryButton
                type="submit"
                class="ml-4"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Submit
            </PrimaryButton>
        </div>
    </form>
</template>
