<script setup>
import Button from "@/Components/Button.vue";
import { Head } from "@inertiajs/inertia-vue3";
import domtoimage from "dom-to-image";
import { onMounted } from "vue";

let imageData;
let canvas = document.createElement("canvas");
let printCharacteristic;

onMounted(() => {
    const node = document.getElementById("app");
    if (node)
        domtoimage
            .toJpeg(node)
            .then(function (dataUrl) {
                //screen shot the page
                const img = new Image();
                img.src = dataUrl;
                img.id = "print";
                document.body.appendChild(img);

                const image = document.querySelector("#print");
                // Canvas dimensions need to be a multiple of 40 for this printer
                canvas.width = 120;
                canvas.height = 120;
                let context = canvas.getContext("2d");
                context.drawImage(image, 0, 0, canvas.width, canvas.height);
                imageData = context.getImageData(
                    0,
                    0,
                    canvas.width,
                    canvas.height
                ).data;
            })
            .catch(function (error) {
                console.error("oops, something went wrong!", error);
            });
});

const getDarkPixel = (x, y) => {
    // Return the pixels that will be printed black
    let red = imageData[(canvas.width * y + x) * 4];
    let green = imageData[(canvas.width * y + x) * 4 + 1];
    let blue = imageData[(canvas.width * y + x) * 4 + 2];
    return red + green + blue > 0 ? 1 : 0;
};

const getImagePrintData = () => {
    if (imageData == null) {
        console.log("No image to print!");
        return new Uint8Array([]);
    }
    // Each 8 pixels in a row is represented by a byte
    let printData = new Uint8Array((canvas.width / 8) * canvas.height + 8);
    let offset = 0;
    // Set the header bytes for printing the image
    printData[0] = 29; // Print raster bitmap
    printData[1] = 118; // Print raster bitmap
    printData[2] = 48; // Print raster bitmap
    printData[3] = 0; // Normal 203.2 DPI
    printData[4] = canvas.width / 8; // Number of horizontal data bits (LSB)
    printData[5] = 0; // Number of horizontal data bits (MSB)
    printData[6] = canvas.height % 256; // Number of vertical data bits (LSB)
    printData[7] = canvas.height / 256; // Number of vertical data bits (MSB)
    offset = 7;
    // Loop through image rows in bytes
    for (let i = 0; i < canvas.height; ++i) {
        for (let k = 0; k < canvas.width / 8; ++k) {
            let k8 = k * 8;
            //  Pixel to bit position mapping
            printData[++offset] =
                getDarkPixel(k8 + 0, i) * 128 +
                getDarkPixel(k8 + 1, i) * 64 +
                getDarkPixel(k8 + 2, i) * 32 +
                getDarkPixel(k8 + 3, i) * 16 +
                getDarkPixel(k8 + 4, i) * 8 +
                getDarkPixel(k8 + 5, i) * 4 +
                getDarkPixel(k8 + 6, i) * 2 +
                getDarkPixel(k8 + 7, i);
        }
    }
    return printData;
};

const sendImageData = () => {
    let index = 0;
    let data = getImagePrintData();
    const sendNextImageDataBatch = (resolve, reject) => {
        // Can only write 512 bytes at a time to the characteristic
        // Need to send the image data in 512 byte batches
        if (index + 512 < data.length) {
            printCharacteristic
                .writeValue(data.slice(index, index + 512))
                .then(() => {
                    index += 512;
                    sendNextImageDataBatch(resolve, reject);
                })
                .catch((error) => reject(error));
        } else {
            // Send the last bytes
            if (index < data.length) {
                printCharacteristic
                    .writeValue(data.slice(index, data.length))
                    .then(() => {
                        resolve();
                    })
                    .catch((error) => reject(error));
            } else {
                resolve();
            }
        }
    };
    return new Promise(function (resolve, reject) {
        sendNextImageDataBatch(resolve, reject);
    });
};
const getPrinter = () =>
    new Promise((resolve, reject) => {
        navigator.bluetooth
            .requestDevice({
                filters: [
                    {
                        services: ["000018f0-0000-1000-8000-00805f9b34fb"],
                    },
                ],
            })
            .then((device) => {
                console.log("> Found " + device.name);
                console.log("Connecting to GATT Server...");
                return device.gatt.connect();
            })
            .then((server) =>
                server.getPrimaryService("000018f0-0000-1000-8000-00805f9b34fb")
            )
            .then((service) =>
                service.getCharacteristic(
                    "00002af1-0000-1000-8000-00805f9b34fb"
                )
            )
            .then((characteristic) => {
                resolve(characteristic);
            })
            .catch((e) => {
                reject(e);
            });
    });

const print = () => {
    if (!printCharacteristic)
        getPrinter().then((characteristic) => {
            printCharacteristic = characteristic;
            sendImageData().then(() => {
                console.log("finished");
            });
        });
    else
        sendImageData().then(() => {
            console.log("finished");
        });
};
</script>

<template>
    <head title="Welcome" />
    <div class="flex flex-col h-full p-2 space-y-2">
        <button @click="$inertia.visit(route('items.create'))">Add item</button>
        <button @click="$inertia.visit(route('items.index'))">Item List</button>
        <button @click="$inertia.visit(route('orders.index'))">Orders</button>
        <button @click="$inertia.visit(route('merchant_payments.index'))">
            Payments
        </button>
        <button
            @click="$inertia.post(route('logout'))"
            v-if="$page.props.auth.user"
        >
            Logout
        </button>
        <button @click="print">Test</button>
        <div>စိုင်းကွမ်းခမ်း</div>
    </div>
</template>
