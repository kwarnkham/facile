<script setup>
import { computed, onMounted, provide, ref, watch } from "vue";
import { HomeIcon, ArrowLeftIcon } from "@heroicons/vue/24/outline";
import { usePage, Link } from "@inertiajs/inertia-vue3";
import { ShoppingCartIcon } from "@heroicons/vue/24/solid";
import { store } from "@/store";
import Dialog from "@/Components/Dialog.vue";
import Button from "@/Components/Button.vue";

const message = ref("");
const back = () => {
    window.history.back();
};
const flashMessage = (incoming) => {
    message.value = incoming;
    setTimeout(() => {
        message.value = "";
    }, 2000);
};
const updateMessage = (newValue) => {
    flashMessage(newValue);
};
const openConfirmDialog = ref(false);
const confirmInvoke = ref(() => {});
const updateConfirmInvoke = (payload) => {
    confirmInvoke.value = payload;
};
const confirmInvokeAndClose = () => {
    confirmInvoke.value();
    openConfirmDialog.value = false;
};
const updateOpenConfirmDialog = (payload) => {
    openConfirmDialog.value = payload;
};
const confirmTitle = ref("");
const updateConfirmTitle = (payload) => {
    confirmTitle.value = payload;
};

provide("confirmDialog", {
    updateOpenConfirmDialog,
    updateConfirmInvoke,
    updateConfirmTitle,
});
provide("message", {
    message,
    updateMessage,
});
let printCharacteristic = ref(null);
const setPrintCharacteristic = (characteristic) => {
    printCharacteristic.value = characteristic;
};
provide("printCharacteristic", {
    printCharacteristic,
    setPrintCharacteristic,
});
watch(
    () => usePage().props.value.flash,
    (flash) => {
        flashMessage(flash.error || flash.message);
    },
    { deep: true }
);

const cartItems = computed(() => {
    return store.cart.items;
});
// onMounted(() => {
//     const setHeight = () => {
//         const vh = window.innerHeight * 0.01;
//         document.querySelector(".layout").style.setProperty("--vh", `${vh}px`);
//     };
//     setHeight();
//     window.addEventListener("resize", setHeight);
// });
</script>

<template>
    <div class="w-screen flex flex-col relative layout" data-theme="garden">
        <div class="flex-grow flex-shrink-0 basis-0 overflow-y-auto">
            <slot />
        </div>
        <div
            class="h-12 w-full bg-secondary text-primary flex items-center justify-between px-4 relative"
            v-if="$page.props.auth.user"
        >
            <div
                :class="{
                    invisible:
                        cartItems.length == 0 ||
                        $page.props.ziggy.location == route('routes.cart'),
                }"
            >
                <ShoppingCartIcon
                    class="h-6 w-6"
                    @click="$inertia.visit(route('routes.cart'))"
                />
            </div>

            <div
                class="absolute -top-6 left-1/2 -translate-x-1/2 bg-primary p-2 rounded-full border-4"
            >
                <HomeIcon
                    class="h-6 w-6 text-accent"
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
            <!-- <Link
                :href="route('register')"
                :class="{
                    'font-semibold underline pointer-events-none':
                        $page.props.ziggy.location == route('register'),
                }"
                >Register</Link
            > -->
        </div>
        <div
            class="absolute bottom-10 right-4 rounded-md px-2 py-1 text-sm font-semibold shadow-md"
            v-if="message"
            :class="{
                'bg-secondary-focus text-secondary-content':
                    $page.props.flash.message,
                'bg-error text-error-content': $page.props.flash.error,
                'bg-info text-info-content':
                    !$page.props.flash.message && !$page.props.flash.error,
            }"
        >
            {{ message }}
        </div>
        <Dialog :open="openConfirmDialog" title="Confirm">
            <div>{{ confirmTitle }}</div>
            <div class="flex flex-row justify-end space-x-2">
                <Button @click="openConfirmDialog = false">No</Button>
                <Button @click="confirmInvokeAndClose">Yes</Button>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.layout {
    min-height: -webkit-fill-available;
    height: 100vh;
}
</style>
