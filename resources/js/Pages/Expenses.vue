<script setup>
import Button from "@/Components/Button.vue";
import Dialog from "@/Components/Dialog.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/inertia-vue3";
import { ref } from "vue";

const form = useForm({
    name: "",
});

const recordExpenseForm = useForm({
    price: "",
});

const showRecordExpenseForm = (expense) => {
    open.value = true;
    expenseBeingRecorded.value = expense;
};

const props = defineProps({
    expenses: {
        type: Array,
        required: true,
    },
});

const submit = () => {
    form.post(route("expenses.store"));
};

const recordExpense = () => {
    recordExpenseForm.post(
        route("expenses.record", {
            expense: expenseBeingRecorded?.value.id,
        }),
        {
            preserveState: true,
            onSuccess: () => {
                recordExpenseForm.reset("price");
                open.value = false;
            },
        }
    );
};
const expenseBeingRecorded = ref(null);

const open = ref(false);
</script>
<template>
    <Head title="Expenses" />
    <div>
        <form @submit.prevent="submit" class="daisy-form-control p-4 space-y-2">
            <div class="text-center text-2xl text-primary">Create Expenses</div>
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 w-full"
                    v-model="form.name"
                    required
                    autofocus
                    :class="{ 'daisy-input-error': form.errors.name }"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="flex items-center justify-end">
                <Button
                    type="submit"
                    class="ml-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Create
                </Button>
            </div>
        </form>

        <div class="px-2">
            <Button
                class="daisy-btn-accent"
                v-for="expense in expenses"
                :key="expense"
                @click="showRecordExpenseForm(expense)"
            >
                {{ expense.name }}
            </Button>
        </div>
    </div>
    <Dialog
        @close="open = false"
        :open="open"
        :title="'Record expense, ' + expenseBeingRecorded?.name"
    >
        <form @submit.prevent="recordExpense">
            <div>
                <InputLabel for="price" value="Price" />
                <TextInput
                    id="price"
                    type="tel"
                    class="mt-1 w-full"
                    v-model.number="recordExpenseForm.price"
                    required
                    autofocus
                    :class="{
                        'daisy-input-error': recordExpenseForm.errors.name,
                    }"
                />
                <InputError :message="recordExpenseForm.errors.price" />
            </div>

            <div class="flex items-center justify-end">
                <Button
                    type="submit"
                    class="mt-1"
                    :class="{ 'opacity-25': recordExpenseForm.processing }"
                    :disabled="recordExpenseForm.processing"
                >
                    Record
                </Button>
            </div>
        </form>
    </Dialog>
</template>
