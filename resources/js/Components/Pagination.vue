<script setup>
defineProps({
    data: {
        required: true,
        type: Object,
    },
    navigate: {
        required: true,
        type: Function,
    },
});
</script>

<template>
    <div class="daisy-btn-group" v-if="data.per_page < data.total">
        <button
            class="daisy-btn daisy-btn-sm"
            :class="{
                'daisy-btn-disabled text-gray-500': data.current_page == 1,
                'text-info': data.current_page != 1,
            }"
            @click="navigate(1)"
        >
            «
        </button>
        <button
            class="daisy-btn daisy-btn-sm capitalize"
            :class="{
                'daisy-btn-disabled text-gray-500': !data.prev_page_url,
                'text-info': data.prev_page_url,
            }"
            @click="navigate(data.current_page - 1)"
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
            @click="navigate(data.current_page + 1)"
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
            @click="navigate(data.last_page)"
        >
            »
        </button>
    </div>
</template>
