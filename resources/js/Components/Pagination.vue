<script setup>
import { Inertia } from "@inertiajs/inertia";
import debounce from "lodash/debounce";
import pickBy from "lodash/pickBy";
import { computed, watch } from "vue";

const props = defineProps({
    data: {
        required: true,
        type: Object,
    },
    url: {
        required: true,
        type: String,
    },
    query: {
        required: false,
        type: Object,
    },
});

const params = computed(() => {
    const temp = JSON.parse(JSON.stringify(props.query));
    if (temp.hasOwnProperty("stocked")) temp.stocked = Number(temp.stocked);
    return temp;
});

const getPage = (page = 1) => {
    Inertia.visit(props.url, {
        method: "get",
        replace: true,
        data: { ...pickBy(params.value), page: page },
        preserveState: true,
    });
};

watch(
    props.query,
    debounce(() => {
        getPage();
    }, 400),
    { deep: true }
);
</script>

<template>
    <div class="daisy-btn-group" v-if="data.per_page < data.total">
        <button
            class="daisy-btn daisy-btn-sm"
            :class="{
                'daisy-btn-disabled text-gray-500': data.current_page == 1,
                'text-info': data.current_page != 1,
            }"
            @click="getPage(1)"
        >
            «
        </button>
        <button
            class="daisy-btn daisy-btn-sm capitalize"
            :class="{
                'daisy-btn-disabled text-gray-500': !data.prev_page_url,
                'text-info': data.prev_page_url,
            }"
            @click="getPage(data.current_page - 1)"
        >
            Prev
        </button>
        <button
            class="daisy-btn daisy-btn-sm daisy-btn-primary pointer-events-none capitalize"
        >
            Page {{ data.current_page }} of {{ data.last_page }}
        </button>
        <button
            class="daisy-btn daisy-btn-sm capitalize"
            :class="{
                'daisy-btn-disabled text-gray-500': !data.next_page_url,
                'text-info': data.next_page_url,
            }"
            @click="getPage(data.current_page + 1)"
        >
            Next
        </button>
        <button
            class="daisy-btn daisy-btn-sm"
            :class="{
                'daisy-btn-disabled text-gray-500':
                    data.current_page == data.last_page,
                'text-info': data.current_page != data.last_page,
            }"
            @click="getPage(data.last_page)"
        >
            »
        </button>
    </div>
</template>
