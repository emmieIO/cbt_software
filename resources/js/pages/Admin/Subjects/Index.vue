<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store, update, destroy } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Subject {
    id: string;
    name: string;
    description: string | null;
    topics_count: number;
}

defineProps<{
    subjects: Subject[];
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingSubject = ref<Subject | null>(null);

const form = useForm({
    name: '',
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingSubject.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (subject: Subject) => {
    isEditing.value = true;
    editingSubject.value = subject;

    form.name = subject.name;
    form.description = subject.description || '';

    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingSubject.value) {
        form.put(update(editingSubject.value.id).url, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(store().url, {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const isDeleteModalOpen = ref(false);
const subjectToDelete = ref<Subject | null>(null);

const confirmDelete = (subject: Subject) => {
    subjectToDelete.value = subject;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (subjectToDelete.value) {
        router.delete(destroy(subjectToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                subjectToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Subject Management" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Academic Subjects</span>
            </nav>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Academic Subjects</h1>
                    <p class="text-sm text-gray-500 mt-1">Define the global subjects offered across all grade levels.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Subject
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="subject in subjects"
                    :key="subject.id"
                    class="group flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-shadow"
                >
                    <div class="p-4 md:p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="size-10 md:size-12 inline-flex justify-center items-center rounded-xl bg-primary/10 text-primary">
                                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    />
                                </svg>
                            </div>
                            <div class="flex items-center gap-x-2">
                                <button
                                    @click="openEditModal(subject)"
                                    class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-500 hover:bg-primary hover:text-white transition-colors"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </button>
                                <button
                                    @click="confirmDelete(subject)"
                                    class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-500 hover:bg-red-500 hover:text-white transition-colors"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">{{ subject.name }}</h3>
                            <p class="mt-2 text-sm text-gray-500 line-clamp-2">
                                {{ subject.description || 'No description provided.' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-auto px-4 md:px-6 py-3 border-t border-gray-200 bg-gray-50/50 rounded-b-xl">
                        <div class="flex items-center gap-x-2">
                            <span class="size-1.5 inline-block rounded-full bg-primary"></span>
                            <span class="text-xs font-medium text-gray-500 uppercase">
                                {{ subject.topics_count }} Topics
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
                <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200">
                    <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">{{ isEditing ? 'Edit Subject' : 'Create New Subject' }}</h3>
                        <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                            <span class="sr-only">Close</span>
                            <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-4 overflow-y-auto max-h-[calc(100vh-150px)]">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Subject Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Enter Subject Name (e.g. Further Mathematics)"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Description (Optional)</label>
                                <textarea
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Provide a brief overview of the subject curriculum..."
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-sm text-red-600 mt-2">{{ form.errors.description }}</div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-x-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                            >
                                {{ isEditing ? 'Update' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Subject?"
            :message="`Are you sure you want to delete ${subjectToDelete?.name}? This action cannot be undone and will fail if topics are attached.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
