<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/inertia-vue3";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role_id: 2,
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Register" />

    <form @submit.prevent="submit" class="p-4 daisy-form-control space-y-2">
        <div class="text-center text-2xl text-primary">Register</div>
        <div>
            <InputLabel for="name" value="Name" />
            <TextInput
                id="name"
                type="text"
                class="w-full"
                v-model="form.name"
                required
                autofocus
                autocomplete="name"
                :class="{ 'daisy-input-error': form.errors.name }"
            />
            <InputError :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="email" value="Email" />
            <TextInput
                id="email"
                type="email"
                class="w-full"
                v-model="form.email"
                required
                autocomplete="username"
                :class="{ 'daisy-input-error': form.errors.email }"
            />
            <InputError :message="form.errors.email" />
        </div>

        <div>
            <InputLabel for="password" value="Password" />
            <TextInput
                id="password"
                type="password"
                class="w-full"
                v-model="form.password"
                required
                autocomplete="new-password"
                :class="{ 'daisy-input-error': form.errors.password }"
            />
            <InputError :message="form.errors.password" />
        </div>

        <div>
            <InputLabel for="password_confirmation" value="Confirm Password" />
            <TextInput
                id="password_confirmation"
                type="password"
                class="w-full"
                v-model="form.password_confirmation"
                required
                autocomplete="new-password"
            />
            <InputError
                class="mt-2"
                :message="form.errors.password_confirmation"
            />
        </div>

        <div class="flex items-center justify-end">
            <Link
                :href="route('login')"
                class="underline text-sm text-primary hover:text-gray-900"
            >
                Already registered?
            </Link>

            <PrimaryButton
                type="submit"
                class="ml-4"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                Register
            </PrimaryButton>
        </div>
    </form>
</template>
