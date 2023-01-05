<script setup>
import { XMarkIcon } from "@heroicons/vue/24/solid";
import { ref } from "vue";
const props = defineProps({
    title: {
        type: String,
        default: "",
    },
    open: {
        type: Boolean,
        default: false,
    },
});

defineEmits(["close"]);
</script>

<template>
    <Teleport to="body">
        <div class="daisy-modal" :class="{ 'daisy-modal-open': open }">
            <div class="daisy-modal-box">
                <XMarkIcon
                    class="w-5 h-5 absolute right-3 top-3"
                    @click="$emit('close')"
                />
                <h3 class="font-bold text-lg">
                    {{ title }}
                </h3>
                <div>
                    <slot />
                </div>
            </div>
        </div>
    </Teleport>
</template>
