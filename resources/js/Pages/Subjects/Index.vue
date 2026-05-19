<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps<{
    subjects: {
        data: Array<{ id: string; name: string; description: string; level: string; topics_count: number }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    levels: Array<{ value: string; label: string }>;
}>();

const showForm = ref(false);
const editing = ref<string | null>(null);
const form = ref({ name: '', description: '', level: 'lp' });

const openCreate = () => {
    editing.value = null;
    form.value = { name: '', description: '', level: 'lp' };
    showForm.value = true;
};

const openEdit = (subject: any) => {
    editing.value = subject.id;
    form.value = { name: subject.name, description: subject.description || '', level: subject.level || 'lp' };
    showForm.value = true;
};

const save = () => {
    if (editing.value) {
        router.put(`/subjects/${editing.value}`, form.value, { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
    } else {
        router.post('/subjects', form.value, { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
    }
};

const deleteTarget = ref<string | null>(null);

const confirmDelete = () => {
    if (deleteTarget.value) {
        router.delete(`/subjects/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Subjects" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Subjects</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Manage academic subjects in the curriculum.</p>
                </div>
                <button @click="openCreate" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 hover:bg-primary/90">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Subject
                </button>
            </div>

            <!-- Form Modal -->
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-800 p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ editing ? 'Edit Subject' : 'New Subject' }}</h2>
                    <form @submit.prevent="save" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject Name</label>
                            <input v-model="form.name" type="text" required class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Level</label>
                            <select v-model="form.level" class="mt-1">
                                <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description (optional)</label>
                            <textarea v-model="form.description" rows="3" class="mt-1" />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showForm = false" class="rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-2 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Subjects List -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="subject in subjects.data" :key="subject.id" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5 shadow-sm dark:shadow-none dark:border-gray-700 transition-all hover:shadow-md">
                    <div class="flex items-start justify-between">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100">{{ subject.name }}</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ subject.topics_count }} topics</p>
                            <span v-if="subject.level" class="mt-1 inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary uppercase">{{ subject.level }}</span>
                            <p v-if="subject.description" class="mt-2 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 line-clamp-2">{{ subject.description }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-2">
                        <button @click="openEdit(subject)" class="text-xs font-medium text-primary hover:underline">Edit</button>
                        <button @click="deleteTarget = subject.id" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                    </div>
                </div>
                <div v-if="subjects.data.length === 0" class="col-span-full py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                    No subjects yet. Click "Add Subject" to create one.
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="subjects.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Page {{ subjects.current_page }} of {{ subjects.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="subjects.prev_page_url" :href="subjects.prev_page_url" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Previous</Link>
                    <Link v-if="subjects.next_page_url" :href="subjects.next_page_url" class="rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-gray-800/50">Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>

    <ConfirmationModal
        :show="!!deleteTarget"
        title="Delete Subject"
        message="This action cannot be undone."
        confirm-label="Delete"
        variant="danger"
        @close="deleteTarget = null"
        @confirm="confirmDelete"
    />
</template>
