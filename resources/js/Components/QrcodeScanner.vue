<script setup>
import Button from "./Button.vue";
import { Html5Qrcode } from "html5-qrcode";
import { onBeforeUnmount, ref } from "vue";
const props = defineProps({
    btnText: {
        type: String,
        default: "Scan QR",
    },
});
const emit = defineEmits(["result"]);
const html5QrCode = ref(null);
const scan = () => {
    html5QrCode.value = new Html5Qrcode("reader");
    const qrCodeSuccessCallback = (decodedText, decodedResult) => {
        emit("result", decodedText, decodedResult);
        html5QrCode.value
            .stop()
            .then((ignore) => {
                // QR Code scanning is stopped.
            })
            .catch((err) => {
                // Stop failed, handle it.
            });
    };

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    html5QrCode.value.start(
        { facingMode: "environment" },
        config,
        qrCodeSuccessCallback
    );
};
onBeforeUnmount(() => {
    if (html5QrCode.value)
        html5QrCode.value
            .stop()
            .then((ignore) => {
                // QR Code scanning is stopped.
            })
            .catch((err) => {
                // Stop failed, handle it.
            });
});
</script>

<template>
    <Button class="daisy-btn-info" @click="scan" v-if="!html5QrCode">
        {{ btnText }}
    </Button>
    <div id="reader"></div>
</template>
