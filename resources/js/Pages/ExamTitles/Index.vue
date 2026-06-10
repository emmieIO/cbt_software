<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

type ExamTitle = {
    id: string;
    name: string;
    is_active: boolean;
};

defineProps<{
    examTitles: {
        data: ExamTitle[];
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
}>();

const showForm = ref(false);
const editing = ref<string | null>(null);
const deleteTarget = ref<string | null>(null);
const form = ref({ name: '', is_active: true });

const openCreate = () => {
    editing.value = null;
    form.value = { name: '', is_active: true };
    showForm.value = true;
};

const openEdit = (examTitle: ExamTitle) => {
    editing.value = examTitle.id;
    form.value = { name: examTitle.name, is_active: examTitle.is_active };
    showForm.value = true;
};

const save = () => {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = false;
        },
    };

    if (editing.value) {
        router.put(`/exam-titles/${editing.value}`, form.value, options);
        return;
    }

    router.post('/exam-titles', form.value, options);
};

const confirmDelete = () => {
    if (!deleteTarget.value) return;

    router.delete(`/exam-titles/${deleteTarget.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            deleteTarget.value = null;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Exam Titles" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Exam Titles</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage the title options shown when creating exams.</p>
                </div>
                <button
                    type="button"
                    @click="openCreate"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 sm:w-auto dark:border-green-900/60 dark:shadow-none"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Title
                </button>
            </div>

            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-green-950/60">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ editing ? 'Edit Exam Title' : 'New Exam Title' }}</h2>
                    <form @submit.prevent="save" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Title</label>
                            <input v-model="form.name" type="text" required class="mt-1" />
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                            <input v-model="form.is_active" type="checkbox" />
                            Active
                        </label>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="showForm = false" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-green-900/60 dark:bg-green-950/60 dark:shadow-none">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-green-950/45">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Title</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Status</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="examTitle in examTitles.data" :key="examTitle.id" class="hover:bg-gray-50 dark:hover:bg-green-950/55">
                                <td class="px-5 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">{{ examTitle.name }}</td>
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="
                                            examTitle.is_active
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200'
                                                : 'bg-slate-100 text-slate-600 dark:bg-slate-800/80 dark:text-slate-200'
                                        "
                                    >
                                        {{ examTitle.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openEdit(examTitle)" class="text-xs font-medium text-primary hover:underline">Edit</button>
                                        <button @click="deleteTarget = examTitle.id" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="examTitles.data.length === 0">
                                <td colspan="3" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No exam titles yet.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="examTitles.last_page > 1" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">Page {{ examTitles.current_page }} of {{ examTitles.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="examTitles.prev_page_url" :href="examTitles.prev_page_url" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/45 dark:text-gray-300">Previous</Link>
                    <Link v-if="examTitles.next_page_url" :href="examTitles.next_page_url" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-green-900/60 dark:bg-green-950/45 dark:text-gray-300">Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>

    <ConfirmationModal
        :show="!!deleteTarget"
        title="Delete Exam Title"
        message="This action cannot be undone."
        confirm-label="Delete"
        variant="danger"
        @close="deleteTarget = null"
        @confirm="confirmDelete"
    />
</template>
