<script setup>
import { onMounted, ref } from "vue";

defineEmits(["remove"]);
const img = ref(null);

onMounted(() => {
    img.value.onload = () => {
        if (img.value) URL.revokeObjectURL(img.value.src); // free memory
    };
});
</script>

<template>
    <div class="relative">
        <div class="absolute top-2 right-2" @click="$emit('remove')">X</div>
        <img ref="img" v-bind="$attrs" />
    </div>
</template>
