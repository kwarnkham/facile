<script setup>
import { computed } from "vue";

const props = defineProps({
    title: {
        required: true,
        type: String,
    },
    checked: {
        type: [Array, Boolean],
        default: false,
    },
    value: {
        default: null,
    },
});

const emit = defineEmits(["update:checked"]);

const proxyChecked = computed({
    get() {
        return props.checked;
    },

    set(val) {
        emit("update:checked", val);
    },
});
</script>

<template>
    <div class="daisy-collapse daisy-collapse-arrow">
        <input
            type="checkbox"
            class="min-h-8"
            :value="value"
            v-model="proxyChecked"
        />
        <div class="daisy-collapse-title text-xl font-bold min-h-8 py-2">
            {{ title }}
        </div>
        <div class="daisy-collapse-content">
            <slot />
        </div>
    </div>
</template>
