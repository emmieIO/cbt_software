<script setup lang="ts">
import { Head, router, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { store, update, destroy } from '@/actions/App/Http/Controllers/Admin/SubjectController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface Subject {
    id: string;
    name: string;
    description: string | null;
    level: string;
    topics_count: number;
}

const props = defineProps<{
    subjects: Subject[];
}>();

const selectedLevel = ref<string | null>(null);
const isModalOpen = ref(false);
const isEditing = ref(false);
const editingSubject = ref<Subject | null>(null);

const form = useForm({
    name: '',
    description: '',
    level: 'primary',
});

// Level Summaries
const levelStats = computed(() => {
    const levels = ['nursery', 'primary', 'secondary'];
    return levels.map(level => ({
        id: level,
        name: level.charAt(0).toUpperCase() + level.slice(1),
        count: props.subjects.filter(s => s.level === level).length,
        color: level === 'nursery' ? 'pink' : (level === 'secondary' ? 'indigo' : 'orange')
    }));
});

const filteredSubjects = computed(() => {
    if (!selectedLevel.value) return [];
    return props.subjects
        .filter(s => s.level === selectedLevel.value)
        .sort((a, b) => a.name.localeCompare(b.name));
});

const openCreateModal = () => {
    isEditing.value = false;
    editingSubject.value = null;
    form.reset();
    if (selectedLevel.value) form.level = selectedLevel.value;
    isModalOpen.value = true;
};

const openEditModal = (subject: Subject) => {
    isEditing.value = true;
    editingSubject.value = subject;
    form.name = subject.name;
    form.description = subject.description || '';
    form.level = subject.level;
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
        <Head title="Curriculum Directory" />

        <div class="space-y-8">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <button @click="selectedLevel = null" class="hover:text-primary transition-colors" :class="!selectedLevel ? 'text-gray-800 font-bold' : ''">Curriculum Registry</button>
                <template v-if="selectedLevel">
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="text-gray-800 font-bold uppercase tracking-tight">{{ selectedLevel }}</span>
                </template>
            </nav>

            <!-- 1. LEVEL OVERVIEW (Initial View) -->
            <div v-if="!selectedLevel" class="space-y-10">
                <div class="max-w-2xl">
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight uppercase">Curriculum Vault</h1>
                    <p class="text-sm font-semibold text-slate-400 uppercase tracking-widest mt-2">
                        Select an academic tier to manage specialized subjects and syllabi.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <button 
                        v-for="stat in levelStats" 
                        :key="stat.id"
                        @click="selectedLevel = stat.id"
                        class="group relative bg-white p-8 rounded-[32px] border border-slate-100 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1 text-left"
                    >
                        <div 
                            class="size-14 rounded-2xl flex items-center justify-center mb-6 transition-transform group-hover:scale-110"
                            :class="`bg-${stat.color}-50 text-${stat.color}-600`"
                        >
                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                        </div>
                        <h3 class="text-xl font-black text-slate-800 uppercase tracking-tight">{{ stat.name }} School</h3>
                        <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mt-1">{{ stat.count }} Active Subjects</p>
                        
                        <div class="mt-8 flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-primary group-hover:gap-3 transition-all">
                            Open Subjects
                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </div>
                    </button>
                </div>
            </div>

            <!-- 2. SUBJECT LIST (Detailed View) -->
            <div v-else class="space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <button @click="selectedLevel = null" class="size-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary transition-all active:scale-90">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </button>
                        <div>
                            <h1 class="text-2xl font-black text-slate-800 tracking-tight uppercase">{{ selectedLevel }} Subjects</h1>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Registry • {{ filteredSubjects.length }} Specialized Items</p>
                        </div>
                    </div>
                    <button
                        @click="openCreateModal"
                        class="py-3 px-6 inline-flex items-center gap-x-2 text-xs font-black uppercase tracking-widest rounded-xl border border-transparent bg-primary text-white hover:bg-primary-hover transition-all shadow-md shadow-primary/20 active:scale-95"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4" /></svg>
                        Register Subject
                    </button>
                </div>

                <div class="bg-white border border-slate-100 rounded-[32px] shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50/50">
                                <tr>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Core Information</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Curriculum Units</th>
                                    <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-end">Control</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="subject in filteredSubjects" :key="subject.id" class="hover:bg-slate-50/30 transition-colors group">
                                    <td class="px-8 py-5">
                                        <div class="flex items-center gap-x-5">
                                            <div class="size-12 flex-shrink-0 flex items-center justify-center rounded-2xl bg-white border border-slate-100 shadow-sm text-xs font-black text-slate-400 group-hover:text-primary transition-colors">
                                                {{ subject.name.substring(0, 2).toUpperCase() }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-sm font-black text-slate-800 uppercase tracking-tight">{{ subject.name }}</span>
                                                <span class="text-[10px] font-bold text-slate-400 line-clamp-1 max-w-sm mt-1 uppercase tracking-wider">
                                                    {{ subject.description || 'No detailed syllabus overview provided.' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col items-center">
                                            <span class="text-sm font-black text-slate-800">{{ subject.topics_count }}</span>
                                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-tighter mt-0.5">Verified Topics</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-end">
                                        <div class="flex justify-end items-center gap-x-3 opacity-0 group-hover:opacity-100 transition-all">
                                            <button 
                                                @click="openEditModal(subject)"
                                                class="size-9 inline-flex justify-center items-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-primary hover:border-primary transition-all"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            </button>
                                            <button 
                                                @click="confirmDelete(subject)"
                                                class="size-9 inline-flex justify-center items-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-red-500 hover:border-red-200 transition-all"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredSubjects.length === 0">
                                    <td colspan="3" class="px-8 py-20 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="size-20 bg-slate-50 rounded-[32px] flex items-center justify-center text-slate-200 mb-6">
                                                <svg class="size-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                            </div>
                                            <p class="text-sm font-black text-slate-400 uppercase tracking-[0.2em]">Tier Registry Empty</p>
                                            <p class="text-xs font-bold text-slate-300 mt-2 uppercase">No subjects have been defined for the {{ selectedLevel }} tier.</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
            <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-lg bg-white rounded-[40px] shadow-2xl border border-slate-100 overflow-hidden">
                <div class="flex justify-between items-center py-6 px-8 border-b border-slate-50">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">{{ isEditing ? 'Update Subject Record' : 'Initialize New Subject' }}</h3>
                    <button @click="closeModal" type="button" class="size-10 inline-flex justify-center items-center rounded-2xl bg-slate-50 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition-all active:scale-90">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-8 space-y-8">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Academic Nomenclature</label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. CORE MATHEMATICS"
                            class="py-4 px-6 block w-full bg-slate-50 border-none rounded-[20px] text-sm font-black text-slate-800 focus:ring-4 focus:ring-primary/10 transition-all uppercase tracking-tight placeholder:text-slate-300"
                        />
                        <div v-if="form.errors.name" class="text-[10px] font-bold text-red-500 mt-2 uppercase tracking-wide">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Institutional Tier</label>
                        <div class="grid grid-cols-3 gap-3">
                            <button
                                v-for="level in ['nursery', 'primary', 'secondary']"
                                :key="level"
                                type="button"
                                @click="form.level = level"
                                class="py-4 px-2 text-center text-[10px] font-black uppercase rounded-2xl border-2 transition-all"
                                :class="form.level === level ? 'bg-slate-900 border-slate-900 text-white shadow-lg' : 'bg-white border-slate-100 text-slate-400 hover:border-slate-200'"
                            >
                                {{ level }}
                            </button>
                        </div>
                        <div v-if="form.errors.level" class="text-[10px] font-bold text-red-500 mt-2 uppercase tracking-wide">{{ form.errors.level }}</div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-3">Syllabus Context (Optional)</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            placeholder="Provide a high-level summary of the subject scope..."
                            class="py-4 px-6 block w-full bg-slate-50 border-none rounded-[20px] text-sm font-bold text-slate-600 focus:ring-4 focus:ring-primary/10 transition-all placeholder:text-slate-300"
                        ></textarea>
                        <div v-if="form.errors.description" class="text-[10px] font-bold text-red-500 mt-2 uppercase tracking-wide">{{ form.errors.description }}</div>
                    </div>

                    <div class="pt-4 flex justify-end gap-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="py-4 px-8 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-800 transition-colors"
                        >
                            Abort
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="py-4 px-8 inline-flex items-center gap-x-2 text-[10px] font-black uppercase tracking-widest rounded-2xl border border-transparent bg-primary text-white hover:bg-primary-hover shadow-xl shadow-primary/20 transition-all active:scale-95 disabled:opacity-50"
                        >
                            {{ isEditing ? 'Update Registry' : 'Confirm Entry' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Purge Subject Record?"
            :message="`Are you sure you want to delete ${subjectToDelete?.name}? This action is irreversible and will only succeed if the subject has no associated curriculum units (topics).`"
            confirm-label="Purge Permanently"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>

<style scoped>
.bg-orange-50 { background-color: rgb(255 247 237); }
.text-orange-600 { color: rgb(234 88 12); }
.bg-pink-50 { background-color: rgb(253 242 248); }
.text-pink-600 { color: rgb(219 39 119); }
.bg-indigo-50 { background-color: rgb(238 242 255); }
.text-indigo-600 { color: rgb(79 70 229); }
</style>
