<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";

const form = useForm({
    password: "",
    new_password: "",
    new_password_confirmation: "",
});

const submit = () => {
    form.post(route("changePassword"), {
        onSuccess() {
            form.reset("password", "new_password", "new_password_confirmation");
        },
    });
};
</script>

<template>
    <Head title="Change Password" />
    <form @submit.prevent="submit" class="daisy-form-control p-4 space-y-2">
        <div class="text-center text-2xl text-primary">Change Password</div>
        <input
            type="text"
            class="hidden"
            name="email"
            autocomplete="username"
        />
        <div>
            <InputLabel for="password" value="Password" />
            <TextInput
                id="password"
                type="password"
                class="mt-1 block w-full"
                v-model="form.password"
                required
                autocomplete="current-password"
            />
            <InputError :message="form.errors.password" />
        </div>

        <div>
            <InputLabel for="new_password" value="New Password" />
            <TextInput
                id="new_password"
                type="password"
                class="mt-1 block w-full"
                v-model="form.new_password"
                autocomplete="new-password"
                required
            />
            <InputError :message="form.errors.new_password" />
        </div>

        <div>
            <InputLabel
                for="new_password_confirmation"
                value="New Password Confirmation"
            />
            <TextInput
                id="new_password_confirmation"
                type="password"
                class="mt-1 block w-full"
                autocomplete="current-password"
                v-model="form.new_password_confirmation"
                required
            />
            <InputError :message="form.errors.new_password_confirmation" />
        </div>

        <div class="flex items-center justify-end">
            <PrimaryButton
                type="submit"
                class="ml-4"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Update
            </PrimaryButton>
        </div>
    </form>
</template>
