<script setup lang="ts">
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { update as updateExamAction, destroy as deleteExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import type { Batch, Subject, SchoolClass } from '@/types/academics';

interface Assignment {
    id: string;
    subject: { id: string; name: string };
    school_class: { id: string; name: string } | null;
    prospective_class: { id: string; name: string } | null;
}

interface Exam {
    id: string;
    title: string;
    subject_id: string;
    school_class_id: string | null;
    prospective_class_id: string | null;
    duration: number;
    type: string;
    status: string;
    start_time: string | null;
    end_time: string | null;
}

const props = defineProps<{
    exam: Exam;
    assignments: Assignment[];
    batches: Batch[];
    subjects: { id: string; name: string }[];
    classes: { id: string; name: string }[];
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const canManageEntrance = computed(() => (page.props.auth.user as any).permissions.includes('manage entrance exams'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

import { ref, watch } from 'vue';
const useGlobalSelection = ref(isAdmin.value && !props.exam.school_class_id && !props.exam.prospective_class_id ? true : isAdmin.value);

// Helper to format date for datetime-local input
const formatDateForInput = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
};

const form = useForm({
    title: props.exam.title,
    assignment_id: props.assignments.find(a => 
        a.subject.id === props.exam.subject_id && 
        (a.school_class?.id === props.exam.school_class_id || a.prospective_class?.id === props.exam.prospective_class_id)
    )?.id || '',
    subject_id: props.exam.subject_id,
    school_class_id: props.exam.school_class_id || '',
    prospective_class_id: props.exam.prospective_class_id || '',
    duration: props.exam.duration,
    type: props.exam.type,
    status: props.exam.status,
    start_time: formatDateForInput(props.exam.start_time),
    end_time: formatDateForInput(props.exam.end_time),
});

const handleAssignmentChange = () => {
    const assignment = props.assignments.find((a) => a.id === form.assignment_id);
    if (assignment) {
        form.subject_id = assignment.subject.id;
        form.school_class_id = assignment.school_class?.id || '';
        form.prospective_class_id = assignment.prospective_class?.id || '';
    }
};

const submit = () => {
    form.put(updateExamAction(props.exam.id).url);
};

// Delete Logic
const isDeleteModalOpen = ref(false);
const confirmDelete = () => {
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    form.delete(deleteExamAction(props.exam.id).url, {
        onSuccess: () => {
            isDeleteModalOpen.value = false;
        }
    });
};
</script>

<template>
    <component :is="Layout">
        <Head :title="`Edit ${exam.title}`" />

        <div class="mx-auto max-w-3xl space-y-10">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900 italic">Edit Examination</h2>
                    <p class="mt-2 text-sm font-bold text-slate-500 uppercase tracking-widest">Update configuration for {{ exam.title }}</p>
                </div>
                <button 
                    @click="confirmDelete"
                    class="rounded-xl border-2 border-red-100 bg-red-50 px-6 py-3 text-[10px] font-black tracking-widest text-red-600 uppercase transition-all hover:bg-red-600 hover:text-white"
                >
                    Delete Exam
                </button>
            </div>

            <!-- Global Selection Toggle for Admins -->
            <div v-if="isAdmin && assignments.length > 0" class="flex items-center justify-center gap-4">
                <button 
                    type="button"
                    @click="useGlobalSelection = false"
                    :class="!useGlobalSelection ? 'bg-primary text-white' : 'bg-white text-slate-400 border border-slate-100'"
                    class="rounded-xl px-6 py-3 text-[10px] font-black uppercase tracking-widest transition-all"
                >
                    Assigned Loads
                </button>
                <button 
                    type="button"
                    @click="useGlobalSelection = true"
                    :class="useGlobalSelection ? 'bg-slate-900 text-white shadow-xl' : 'bg-white text-slate-400 border border-slate-100'"
                    class="rounded-xl px-6 py-3 text-[10px] font-black uppercase tracking-widest transition-all"
                >
                    Global Management (Admin)
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white p-12 shadow-2xl">
                <form @submit.prevent="submit" class="space-y-10">
                    <div class="space-y-6">
                        <!-- ... Title and duration ... -->
                        <div>
                            <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Exam Title</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-lg font-black text-slate-800 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                            <div v-if="form.errors.title" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.title }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Duration (Minutes)</label>
                                <input
                                    v-model="form.duration"
                                    type="number"
                                    required
                                    min="1"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.duration" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.duration }}</div>
                            </div>
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Exam Status</label>
                                <select
                                    v-model="form.status"
                                    required
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="draft">Draft</option>
                                    <option value="live">Live (Visible to Students)</option>
                                    <option value="closed">Closed / Archived</option>
                                </select>
                                <div v-if="form.errors.status" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.status }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Examination Type</label>
                                <select
                                    v-model="form.type"
                                    required
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="terminal">Terminal Exam</option>
                                    <option value="ca">Continuous Assessment (CA)</option>
                                    <option value="entrance">Entrance Exam</option>
                                </select>
                                <div v-if="form.errors.type" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.type }}</div>
                            </div>
                        </div>

                        <!-- Target Audience -->
                        <div v-if="useGlobalSelection" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Academic Subject</label>
                                    <select
                                        v-model="form.subject_id"
                                        required
                                        class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                    >
                                        <option value="" disabled>Select Subject</option>
                                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                    </select>
                                    <div v-if="form.errors.subject_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.subject_id }}</div>
                                </div>
                                <div v-if="form.type === 'entrance'">
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Entrance Batch</label>
                                    <select
                                        v-model="form.prospective_class_id"
                                        required
                                        class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                    >
                                        <option value="" disabled>Select Batch</option>
                                        <option v-for="batch in batches" :key="batch.id" :value="batch.id">{{ batch.name }}</option>
                                    </select>
                                    <div v-if="form.errors.prospective_class_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.prospective_class_id }}</div>
                                </div>
                                <div v-else>
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Target Class</label>
                                    <select
                                        v-model="form.school_class_id"
                                        required
                                        class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                    >
                                        <option value="" disabled>Select Class</option>
                                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                    </select>
                                    <div v-if="form.errors.school_class_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.school_class_id }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="col-span-full">
                            <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Assigned Load</label>
                            <select
                                v-model="form.assignment_id"
                                @change="handleAssignmentChange"
                                required
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="" disabled>Choose Subject & Class</option>
                                <option v-for="load in assignments" :key="load.id" :value="load.id">
                                    {{ load.subject.name }} — {{ load.school_class?.name || load.prospective_class?.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.assignment_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.assignment_id }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Schedule Start</label>
                                <input
                                    v-model="form.start_time"
                                    type="datetime-local"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.start_time" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.start_time }}</div>
                            </div>
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Schedule End</label>
                                <input
                                    v-model="form.end_time"
                                    type="datetime-local"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.end_time" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.end_time }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 border-t border-slate-50 pt-8">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex flex-1 items-center justify-center gap-3 rounded-xl bg-primary py-5 text-sm font-black tracking-[0.2em] text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                        >
                            Save Changes
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Examination?"
            :message="`Are you sure you want to delete ${exam.title}? All allocated question mappings will be removed. This action is irreversible.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </component>
</template>
