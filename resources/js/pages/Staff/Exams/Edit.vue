<script setup lang="ts">
import { Head, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch, onMounted } from 'vue';
import { update as updateExamAction, destroy as deleteExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Batch } from '@/types/academics';

interface Assignment {
    id: string;
    subject: { id: string; name: string };
    school_class: { id: string; name: string } | null;
    prospective_class: { id: string; name: string } | null;
}

interface Topic {
    id: string;
    name: string;
    subject_id: string;
}

interface ExamComposition {
    id?: string;
    subject_id: string;
    topic_id: string | null;
    question_count: number;
    marks_per_question: number;
    available_topics?: Topic[];
}

interface Exam {
    id: string;
    title: string;
    branch: string;
    subject_id: string | null;
    school_class_id: string | null;
    prospective_class_id: string | null;
    duration: number;
    type: string;
    status: string;
    start_time: string | null;
    end_time: string | null;
    compositions: ExamComposition[];
}

const props = defineProps<{
    exam: Exam;
    assignments: Assignment[];
    batches: Batch[];
    subjects: { id: string; name: string }[];
    classes: { id: string; name: string }[];
    academic_session: any | null;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const useGlobalSelection = ref(
    isAdmin.value && !props.exam.school_class_id && !props.exam.prospective_class_id ? true : isAdmin.value
);

// Helper to format date for datetime-local input
const formatDateForInput = (dateString: string | null) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    // Adjust for timezone offset to get local time
    const tzOffset = date.getTimezoneOffset() * 60000;
    const localISOTime = new Date(date.getTime() - tzOffset).toISOString().slice(0, 16);
    return localISOTime;
};

const form = useForm({
    title: props.exam.title,
    branch: props.exam.branch,
    assignment_id:
        props.assignments.find(
            (a) =>
                a.subject?.id === props.exam.subject_id &&
                (a.school_class?.id === props.exam.school_class_id || a.prospective_class?.id === props.exam.prospective_class_id),
        )?.id || '',
    subject_id: props.exam.subject_id || '',
    school_class_id: props.exam.school_class_id || '',
    prospective_class_id: props.exam.prospective_class_id || '',
    duration: props.exam.duration,
    type: props.exam.type,
    status: props.exam.status,
    start_time: formatDateForInput(props.exam.start_time),
    end_time: formatDateForInput(props.exam.end_time),
    compositions: props.exam.compositions.map(c => ({
        ...c,
        topic_id: c.topic_id || '',
        marks_per_question: Number(c.marks_per_question),
        available_topics: [] as Topic[]
    })),
});

const addCompositionRow = () => {
    form.compositions.push({
        subject_id: '',
        topic_id: '',
        question_count: 10,
        marks_per_question: 1,
        available_topics: [],
    });
};

const removeCompositionRow = (index: number) => {
    form.compositions.splice(index, 1);
};

const fetchTopicsForComposition = async (index: number) => {
    const subjectId = form.compositions[index].subject_id;
    if (!subjectId) {
        form.compositions[index].available_topics = [];
        return;
    }

    try {
        const response = await fetch(`/api/subjects/${subjectId}/topics`);
        if (!response.ok) throw new Error('Network response was not ok');
        form.compositions[index].available_topics = await response.json();
    } catch (error) {
        console.error('Failed to fetch topics', error);
    }
};

const handleAssignmentChange = () => {
    const assignment = props.assignments.find((a) => a.id === form.assignment_id);
    if (assignment) {
        form.subject_id = assignment.subject?.id || '';
        form.school_class_id = assignment.school_class?.id || '';
        form.prospective_class_id = assignment.prospective_class?.id || '';
        
        if (assignment.prospective_class) {
            form.type = 'entrance';
        }
    }
};

watch(() => form.type, (newType) => {
    if (newType === 'entrance' && form.compositions.length === 0) {
        addCompositionRow();
    }
});

onMounted(() => {
    // Fetch topics for existing compositions
    form.compositions.forEach((_, index) => {
        fetchTopicsForComposition(index);
    });
});

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
        },
    });
};
</script>

<template>
    <component :is="Layout">
        <Head :title="`Edit ${exam.title}`" />

        <div class="mx-auto max-w-4xl space-y-10">
            <div v-if="!academic_session" class="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex items-center gap-4 text-red-600">
                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-black uppercase tracking-widest">Warning: No Active Session</h4>
                        <p class="mt-1 text-xs font-bold leading-relaxed opacity-80">
                            The system is currently running without an active academic session. 
                            While you can edit existing exams, new operations may be restricted.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-4xl font-black tracking-tight text-slate-900 italic">Edit Examination</h2>
                    <p class="mt-2 text-sm font-bold tracking-widest text-slate-500 uppercase">Update configuration for {{ exam.title }}</p>
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
                    :class="!useGlobalSelection ? 'bg-primary text-white' : 'border border-slate-100 bg-white text-slate-400'"
                    class="rounded-xl px-6 py-3 text-[10px] font-black tracking-widest uppercase transition-all"
                >
                    Assigned Loads
                </button>
                <button
                    type="button"
                    @click="useGlobalSelection = true"
                    :class="useGlobalSelection ? 'bg-slate-900 text-white shadow-xl' : 'border border-slate-100 bg-white text-slate-400'"
                    class="rounded-xl px-6 py-3 text-[10px] font-black tracking-widest uppercase transition-all"
                >
                    Global Management (Admin)
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white p-12 shadow-2xl">
                <form @submit.prevent="submit" class="space-y-10">
                    <div class="space-y-8">
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

                        <div>
                            <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">School Branch</label>
                            <select
                                v-model="form.branch"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-lg font-black text-slate-800 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="primary">Chrisland Primary School VGC</option>
                                <option value="nursery">Chrisland Nursery School VGC</option>
                            </select>
                            <div v-if="form.errors.branch" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.branch }}</div>
                        </div>

                        <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Duration (Minutes)</label
                                >
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
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Examination Type</label
                                >
                                <select
                                    v-model="form.type"
                                    required
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="terminal">Terminal Exam</option>
                                    <option value="ca">Continuous Assessment (CA)</option>
                                    <option value="entrance">Entrance Exam (Multi-Subject)</option>
                                </select>
                                <div v-if="form.errors.type" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.type }}</div>
                            </div>
                        </div>

                        <!-- Target Audience (If not entrance) -->
                        <div v-if="form.type !== 'entrance'" class="space-y-6">
                            <div v-if="useGlobalSelection" class="rounded-2xl bg-slate-50 p-8 space-y-6">
                                <h3 class="text-xs font-black tracking-[0.2em] text-slate-400 uppercase italic">Target Audience</h3>
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Academic Subject</label>
                                        <select v-model="form.subject_id" class="w-full rounded-xl border-slate-100 bg-white px-6 py-4 text-sm font-bold text-slate-700 shadow-sm transition-all focus:border-primary focus:ring-primary">
                                            <option value="">Select Subject</option>
                                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                        </select>
                                        <div v-if="form.errors.subject_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.subject_id }}</div>
                                    </div>
                                    <div>
                                        <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Target Class</label>
                                        <select v-model="form.school_class_id" class="w-full rounded-xl border-slate-100 bg-white px-6 py-4 text-sm font-bold text-slate-700 shadow-sm transition-all focus:border-primary focus:ring-primary">
                                            <option value="">Select Class</option>
                                            <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                        <div v-if="form.errors.school_class_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.school_class_id }}</div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="rounded-2xl bg-slate-50 p-8">
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Assigned Load</label>
                                <select
                                    v-model="form.assignment_id"
                                    @change="handleAssignmentChange"
                                    required
                                    class="w-full rounded-xl border-slate-100 bg-white px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="" disabled>Choose Subject & Class</option>
                                    <option v-for="load in assignments" :key="load.id" :value="load.id">
                                        {{ load.subject?.name || 'All Subjects (Coordinator)' }} — {{ load.school_class?.name || load.prospective_class?.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.assignment_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.assignment_id }}</div>
                            </div>
                        </div>

                        <!-- Entrance Exam Blueprint Builder -->
                        <div v-else class="rounded-2xl border-2 border-dashed border-primary/20 bg-primary/5 p-8 space-y-8">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-sm font-black tracking-[0.2em] text-primary uppercase italic">Assessment Blueprint</h3>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Configure sections for different subjects & topics</p>
                                </div>
                                <div class="w-48">
                                    <label class="mb-2 block text-[9px] font-black tracking-widest text-slate-400 uppercase">Target Entrance Batch</label>
                                    <select v-model="form.prospective_class_id" class="w-full rounded-lg border-primary/10 bg-white px-4 py-2 text-xs font-bold text-slate-700 focus:border-primary focus:ring-primary">
                                        <option value="">Select Batch</option>
                                        <option v-for="b in batches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </select>
                                    <div v-if="form.errors.prospective_class_id" class="mt-1 text-[10px] font-bold text-red-500">{{ form.errors.prospective_class_id }}</div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div v-if="form.errors.compositions" class="p-4 bg-red-50 rounded-lg text-xs font-bold text-red-600 border border-red-100">
                                    {{ form.errors.compositions }}
                                </div>

                                <div v-for="(comp, index) in form.compositions" :key="index" class="group relative grid grid-cols-12 gap-4 items-end bg-white p-6 rounded-xl border border-primary/10 shadow-sm">
                                    <div class="col-span-4">
                                        <label class="mb-2 block text-[9px] font-black tracking-widest text-slate-400 uppercase">Subject</label>
                                        <select 
                                            v-model="comp.subject_id" 
                                            @change="fetchTopicsForComposition(index)"
                                            class="w-full rounded-lg border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 focus:bg-white focus:ring-primary"
                                        >
                                            <option value="">Choose Subject</option>
                                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                        </select>
                                        <div v-if="form.errors[`compositions.${index}.subject_id`]" class="mt-1 text-[9px] font-bold text-red-500">
                                            Required
                                        </div>
                                    </div>
                                    <div class="col-span-3">
                                        <label class="mb-2 block text-[9px] font-black tracking-widest text-slate-400 uppercase">Topic (Optional)</label>
                                        <select 
                                            v-model="comp.topic_id"
                                            class="w-full rounded-lg border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 focus:bg-white focus:ring-primary"
                                        >
                                            <option value="">Entire Subject pool</option>
                                            <option v-for="t in comp.available_topics" :key="t.id" :value="t.id">{{ t.name }}</option>
                                        </select>
                                    </div>
                                    <div class="col-span-2">
                                        <label class="mb-2 block text-[9px] font-black tracking-widest text-slate-400 uppercase">Questions</label>
                                        <input 
                                            v-model="comp.question_count" 
                                            type="number" 
                                            class="w-full rounded-lg border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 focus:bg-white focus:ring-primary text-center" 
                                        />
                                    </div>
                                    <div class="col-span-2">
                                        <label class="mb-2 block text-[9px] font-black tracking-widest text-slate-400 uppercase">Marks/Q</label>
                                        <input 
                                            v-model="comp.marks_per_question" 
                                            type="number" 
                                            step="0.1"
                                            class="w-full rounded-lg border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 focus:bg-white focus:ring-primary text-center" 
                                        />
                                    </div>
                                    <div class="col-span-1 flex justify-end">
                                        <button 
                                            type="button" 
                                            @click="removeCompositionRow(index)"
                                            class="h-10 w-10 flex items-center justify-center rounded-lg bg-red-50 text-red-400 hover:bg-red-500 hover:text-white transition-all"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    @click="addCompositionRow"
                                    class="w-full py-4 border-2 border-dashed border-primary/20 rounded-xl text-[10px] font-black tracking-widest text-primary uppercase hover:bg-primary hover:text-white transition-all"
                                >
                                    + Add Assessment Section
                                </button>
                            </div>
                        </div>

                        <!-- Timing -->
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Schedule Start</label>
                                <input v-model="form.start_time" type="datetime-local" class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-primary" />
                                <div v-if="form.errors.start_time" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.start_time }}</div>
                            </div>
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Schedule End</label>
                                <input v-model="form.end_time" type="datetime-local" class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 focus:bg-white focus:ring-primary" />
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
