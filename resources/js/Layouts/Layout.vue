<script setup>
import { ref, watch } from "vue";
import { HomeIcon, ArrowLeftIcon } from "@heroicons/vue/24/outline";
import { usePage, Link } from "@inertiajs/inertia-vue3";

const message = ref("");
const back = () => {
    window.history.back();
};
watch(
    () => usePage().props.value.flash,
    (flash) => {
        message.value = flash.error || flash.message;
        setTimeout(() => {
            message.value = "";
        }, 2000);
    },
    { deep: true }
);
</script>

<template>
    <div class="h-screen w-screen flex flex-col relative" data-theme="garden">
        <div class="flex-1">
            <slot />
        </div>
        <div
            class="h-12 w-full bg-secondary flex items-center text-primary justify-between px-4 relative"
            v-if="$page.props.auth.user"
        >
            <div
                class="absolute -top-6 left-1/2 -translate-x-1/2 bg-accent p-2 rounded-full border-4"
            >
                <HomeIcon
                    class="h-6 w-6"
                    @click="$inertia.visit(route('index'))"
                    :class="{
                        'pointer-events-none':
                            $page.props.ziggy.location == route('index'),
                    }"
                />
            </div>
            <div v-if="$page.props.ziggy.location != route('index')">
                <ArrowLeftIcon class="h-6 w-6" @click="back" />
            </div>
        </div>
        <div
            class="h-12 w-full bg-secondary flex items-center text-primary justify-between px-4"
            v-else
        >
            <Link
                :href="route('login')"
                :class="{
                    'font-semibold underline pointer-events-none':
                        $page.props.ziggy.location == route('login'),
                }"
                >Login</Link
            >
            <Link
                :href="route('register')"
                :class="{
                    'font-semibold underline pointer-events-none':
                        $page.props.ziggy.location == route('register'),
                }"
                >Register</Link
            >
        </div>
        <div
            class="absolute bottom-4 right-4 rounded-md px-2 py-1 text-sm font-semibold shadow-md"
            v-if="message"
            :class="[
                $page.props.flash.message
                    ? 'bg-secondary-focus text-secondary-content'
                    : 'bg-error text-error-content',
            ]"
        >
            {{ message }}
        </div>
    </div>
</template>
