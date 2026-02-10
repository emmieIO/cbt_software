<script setup lang="ts">
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index, store, destroy } from '@/actions/App/Http/Controllers/Admin/TeachingLoadController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData, Subject, SchoolClass } from '@/types/academics';
import { computed, watch } from 'vue';

interface Teacher {
    id: string;
    name: string;
    school_id: string | null;
}

interface Assignment {
    id: string;
    teacher: Teacher;
    subject: Subject;
    school_class: SchoolClass;
    academic_session: { name: string };
}

const props = defineProps<{
    assignments: PaginatedData<Assignment>;
    teachers: Teacher[];
    subjects: any[]; // Changed to any to handle topics
    classes: SchoolClass[];
    filters: {
        user_id?: string;
        school_class_id?: string;
    };
    current_session: { name: string } | null;
}>();

const isModalOpen = ref(false);
const form = useForm({
    user_id: '',
    subject_id: '',
    school_class_id: '',
});

const filteredClasses = computed(() => {
    if (!form.subject_id) return [];

    const selectedSubject = props.subjects.find((s) => s.id === form.subject_id);
    if (!selectedSubject || !selectedSubject.topics) return [];

    const eligibleClassIds = new Set(selectedSubject.topics.map((t: any) => t.school_class_id));
    return props.classes.filter((c) => eligibleClassIds.has(c.id));
});

watch(() => form.subject_id, () => {
    form.school_class_id = '';
});

const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset('subject_id', 'school_class_id');
        },
    });
};

// Filters
const filterForm = useForm({
    user_id: props.filters.user_id || '',
    school_class_id: props.filters.school_class_id || '',
});

const applyFilters = () => {
    router.get(
        index().url,
        {
            user_id: filterForm.user_id,
            school_class_id: filterForm.school_class_id,
        },
        { preserveState: true },
    );
};

const isDeleteModalOpen = ref(false);
const assignmentToDelete = ref<Assignment | null>(null);

const confirmDelete = (assignment: Assignment) => {
    assignmentToDelete.value = assignment;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (assignmentToDelete.value) {
        router.delete(destroy(assignmentToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                assignmentToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Teaching Loads" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Teaching Loads</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Assign subjects and classes to teachers for the active session.</p>
                </div>
                <button
                    @click="isModalOpen = true"
                    class="flex h-12 items-center gap-3 rounded-2xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Assign Load
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-4 rounded-4xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex flex-1 gap-4">
                    <div class="flex-1">
                        <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Filter by Teacher</label>
                        <select
                            v-model="filterForm.user_id"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                        >
                            <option value="">All Teachers</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Filter by Class</label>
                        <select
                            v-model="filterForm.school_class_id"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                        >
                            <option value="">All Classes</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 rounded-xl bg-slate-50 px-4 py-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Active Session:</span>
                    <span class="ml-2 text-xs font-black text-primary uppercase">{{ current_session?.name || 'Not Set' }}</span>
                </div>
            </div>

            <div class="overflow-hidden rounded-[2.5rem] border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Teacher</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Subject</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Class</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="assignment in assignments.data" :key="assignment.id" class="group transition-all hover:bg-slate-50/80">
                                <td class="px-8 py-6">
                                    <div>
                                        <h4 class="text-sm font-black text-slate-800">{{ assignment.teacher.name }}</h4>
                                        <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">
                                            {{ assignment.teacher.school_id || 'No ID' }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        class="inline-flex items-center rounded-lg bg-primary/5 px-2.5 py-1 text-[10px] font-black text-primary uppercase"
                                    >
                                        {{ assignment.subject.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-6">
                                    <span class="text-xs font-bold text-slate-600">{{ assignment.school_class.name }}</span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <button
                                        @click="confirmDelete(assignment)"
                                        class="ml-auto flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
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
                                </td>
                            </tr>
                            <tr v-if="assignments.data.length === 0">
                                <td colspan="4" class="px-8 py-12 text-center text-sm font-bold text-slate-400 italic">
                                    No teaching assignments found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-slate-50 px-8 py-6">
                    <p class="text-xs font-bold text-slate-400 italic">
                        Showing {{ assignments.from }} to {{ assignments.to }} of {{ assignments.total }} results.
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in assignments.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="flex h-9 items-center justify-center rounded-xl border px-4 text-xs font-black transition-all"
                            :class="[
                                link.active
                                    ? 'border-primary bg-primary text-white shadow-lg shadow-primary/20'
                                    : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50',
                                !link.url ? 'pointer-events-none opacity-50' : '',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Create Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="isModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">Assign Teaching Load</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Select Teacher</label>
                            <select
                                v-model="form.user_id"
                                required
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="" disabled>Choose Personnel</option>
                                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                    {{ teacher.name }} ({{ teacher.school_id }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Subject</label>
                            <select
                                v-model="form.subject_id"
                                required
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="" disabled>Select Subject</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Assign to Class</label>
                            <select
                                v-model="form.school_class_id"
                                required
                                :disabled="!form.subject_id"
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <option value="" disabled>Select Class Level</option>
                                <option v-for="cls in filteredClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                            </select>
                        </div>

                        <div class="flex gap-3 border-t border-slate-50 pt-4">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-2xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            >
                                Confirm Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Remove Teaching Load?"
            :message="`Are you sure you want to remove the assignment for ${assignmentToDelete?.teacher.name}? They will no longer be able to manage exams for this class.`"
            confirm-label="Remove Load"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
