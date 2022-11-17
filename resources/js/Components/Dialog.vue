<script setup>
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
</script>

<template>
    <Teleport to="body">
        <div
            class="daisy-modal daisy-modal-bottom sm:daisy-modal-middle"
            :class="{ 'daisy-modal-open': open }"
        >
            <div class="daisy-modal-box">
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
