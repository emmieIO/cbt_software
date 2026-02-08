<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
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

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Academic Subjects</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Define the global subjects offered across all grade levels.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex h-12 items-center gap-3 rounded-2xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Subject
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="subject in subjects"
                    :key="subject.id"
                    class="group relative overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-xl hover:shadow-primary/5"
                >
                    <div class="relative z-10 flex h-full flex-col justify-between">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/5 text-primary">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                        />
                                    </svg>
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        @click="openEditModal(subject)"
                                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-primary hover:text-white"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmDelete(subject)"
                                        class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-slate-800">{{ subject.name }}</h3>
                                <p class="mt-2 line-clamp-2 text-xs leading-relaxed font-medium text-slate-500">
                                    {{ subject.description || 'No description provided.' }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-8 flex items-center gap-4 border-t border-slate-50 pt-6">
                            <div class="flex items-center gap-2">
                                <div class="h-1.5 w-1.5 rounded-full bg-primary"></div>
                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                    {{ subject.topics_count }} Topics
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">{{ isEditing ? 'Edit Subject' : 'Create New Subject' }}</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Subject Name</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. Further Mathematics"
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                            <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Description (Optional)</label>
                            <textarea
                                v-model="form.description"
                                rows="4"
                                placeholder="Provide a brief overview of the subject curriculum..."
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.description }}</div>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-2xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
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
