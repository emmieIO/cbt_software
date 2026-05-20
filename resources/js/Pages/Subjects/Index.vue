<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    subjects: {
        data: Array<{ id: string; name: string; description: string; level: string; topics_count: number }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    levels: Array<{ value: string; label: string }>;
    filters: {
        level?: string;
    };
}>();

const showForm = ref(false);
const editing = ref<string | null>(null);
const filterLevel = ref(props.filters.level || '');
const form = ref({ name: '', description: '', level: 'lp' });

watch(filterLevel, (value) => {
    router.get('/subjects', {
        level: value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

const openCreate = () => {
    editing.value = null;
    form.value = { name: '', description: '', level: filterLevel.value || 'lp' };
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
        router.delete(`/subjects/${deleteTarget.value}`, { preserveScroll: true });
        deleteTarget.value = null;
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Subjects" />

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Subjects</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Manage academic subjects in the curriculum.</p>
                </div>
                <button @click="openCreate" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-primary/90 dark:border-gray-700 dark:shadow-none sm:w-auto">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Subject
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Level:</label>
                <select v-model="filterLevel" class="w-full sm:w-auto sm:min-w-[160px]">
                    <option value="">All Levels</option>
                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ subjects.data.length }} subject{{ subjects.data.length !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- Form Modal -->
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800">
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
                        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <button type="button" @click="showForm = false" class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300">Cancel</button>
                            <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary/90">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:shadow-none">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800/50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subject</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Level</th>
                                <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Topics</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="subject in subjects.data" :key="subject.id" class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ subject.name }}</p>
                                    <p v-if="subject.description" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 line-clamp-1">{{ subject.description }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="inline-flex rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold uppercase text-primary">
                                        {{ subject.level }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-300">{{ subject.topics_count }}</td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button @click="openEdit(subject)" class="text-xs font-medium text-primary hover:underline">Edit</button>
                                        <button @click="deleteTarget = subject.id" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="subjects.data.length === 0">
                                <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No subjects found for this filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="subjects.last_page > 1" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">Page {{ subjects.current_page }} of {{ subjects.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="subjects.prev_page_url" :href="subjects.prev_page_url" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300">Previous</Link>
                    <Link v-if="subjects.next_page_url" :href="subjects.next_page_url" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-300">Next</Link>
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
