<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { store as storeExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
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

const props = defineProps<{
    assignments: Assignment[];
    batches: Batch[];
    subjects: { id: string; name: string }[];
    classes: { id: string; name: string }[];
    academic_session: any | null;
}>();

const page = usePage();
const branches = computed(() => (page.props as any).branches || {});
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const useGlobalSelection = ref(isAdmin.value);

const form = useForm({
    title: '',
    branch: 'primary_vgc',
    assignment_id: '', 
    subject_id: '',
    school_class_id: '',
    prospective_class_id: '',
    duration: 60,
    type: 'terminal',
    start_time: '',
    end_time: '',
    description: '',
    instructions: '',
    compositions: [] as Array<{
        subject_id: string;
        topic_id: string;
        question_count: number;
        marks_per_question: number;
        available_topics: Topic[];
    }>,
});

// For entrance exams, filter subjects based on what is taught at the target level
// Note: In this system, subjects aren't strictly locked to classes in the DB, 
// but we can look at "assignments" to see what is typical for that level.
const filteredSubjects = computed(() => {
    if (form.type !== 'entrance' || !form.school_class_id) return props.subjects;
    
    // Extract subject IDs assigned to this class level from teacher assignments
    const assignedToLevel = props.assignments
        .filter(a => a.school_class?.id === form.school_class_id)
        .map(a => a.subject.id);
    
    if (assignedToLevel.length === 0) return props.subjects; // Fallback
    
    return props.subjects.filter(s => assignedToLevel.includes(s.id));
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
    if (!subjectId) return;

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

const submit = () => {
    form.post(storeExamAction().url);
};
</script>

<template>
    <component :is="Layout">
        <Head title="Configure New Examination" />

        <div class="mx-auto max-w-6xl space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-[10px] font-bold tracking-widest text-slate-500 uppercase">
                <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="text-slate-500 transition-colors hover:text-slate-800">Dashboard</Link>
                <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                <Link href="/staff/exams" class="text-slate-500 transition-colors hover:text-slate-800">Vault</Link>
                <svg class="h-3 w-3 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" /></svg>
                <span class="text-slate-900">New Examination</span>
            </nav>
            <div v-if="!academic_session" class="rounded-xl border border-red-200 bg-red-50 p-6 shadow-sm">
                <div class="flex items-center gap-4 text-red-600">
                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-black uppercase tracking-widest">Action Required: No Active Session</h4>
                        <p class="mt-1 text-xs font-bold leading-relaxed opacity-80">
                            Exams must be linked to an active academic session. 
                            Please contact an administrator to set a current session in Settings before creating exams.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <Link href="/staff/exams" class="group flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white transition-all hover:border-slate-900 hover:text-slate-900 active:scale-95">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" /></svg>
                        </Link>
                        <h2 class="text-4xl font-black tracking-tight text-slate-900 italic">New Examination</h2>
                    </div>
                    <p class="mt-2 text-sm font-bold tracking-widest text-slate-500 uppercase px-1">Stage 1: Assessment Configuration</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white p-12 shadow-2xl">
                <form @submit.prevent="submit" class="space-y-10">
                    <div class="space-y-8">
                        <!-- Basic Info -->
                        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                            <div class="lg:col-span-1">
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Exam Title</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Enter Examination Title (e.g. First Term Mathematics Exam)"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-lg font-black text-slate-800 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.title" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.title }}</div>
                            </div>

                            <div class="lg:col-span-1">
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">School Branch</label>
                                <select
                                    v-model="form.branch"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-lg font-black text-slate-800 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option v-for="(info, key) in branches" :key="key" :value="key">{{ info.name }}</option>
                                </select>
                                <div v-if="form.errors.branch" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.branch }}</div>
                            </div>

                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Duration (Minutes)</label>
                                <input
                                    v-model="form.duration"
                                    type="number"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.duration" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.duration }}</div>
                            </div>

                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Examination Type</label>
                                <select
                                    v-model="form.type"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="terminal">Terminal Exam</option>
                                    <option value="ca">Continuous Assessment (CA)</option>
                                    <option value="entrance">Entrance Exam (Multi-Subject)</option>
                                </select>
                                <div v-if="form.errors.type" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.type }}</div>
                            </div>
                        </div>

                        <!-- Target Context Selection -->
                        <div class="rounded-2xl bg-slate-50 p-8 space-y-6">
                            <h3 class="text-xs font-black tracking-[0.2em] text-slate-400 uppercase italic">Target Context</h3>
                            
                            <div v-if="useGlobalSelection" class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                        {{ form.type === 'entrance' ? 'Target Entry Level' : 'Target School Class' }}
                                    </label>
                                    <select v-model="form.school_class_id" class="w-full rounded-xl border-slate-100 bg-white px-6 py-4 text-sm font-bold text-slate-700 shadow-sm transition-all focus:border-primary focus:ring-primary">
                                        <option value="">Select Level</option>
                                        <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                    <div v-if="form.errors.school_class_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.school_class_id }}</div>
                                </div>

                                <div v-if="form.type === 'entrance'">
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Entrance Batch</label>
                                    <select v-model="form.prospective_class_id" class="w-full rounded-xl border-slate-100 bg-white px-6 py-4 text-sm font-bold text-slate-700 shadow-sm transition-all focus:border-primary focus:ring-primary">
                                        <option value="">Select Batch</option>
                                        <option v-for="b in batches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </select>
                                    <div v-if="form.errors.prospective_class_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.prospective_class_id }}</div>
                                </div>

                                <div v-if="form.type !== 'entrance'">
                                    <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Academic Subject</label>
                                    <select v-model="form.subject_id" class="w-full rounded-xl border-slate-100 bg-white px-6 py-4 text-sm font-bold text-slate-700 shadow-sm transition-all focus:border-primary focus:ring-primary">
                                        <option value="">Select Subject</option>
                                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <div v-if="form.errors.subject_id" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.subject_id }}</div>
                                </div>
                            </div>

                            <div v-else>
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
                        <div v-if="form.type === 'entrance'" class="rounded-2xl border-2 border-dashed border-primary/20 bg-primary/5 p-8 space-y-8">
                            <div>
                                <h3 class="text-sm font-black tracking-[0.2em] text-primary uppercase italic">Assessment Blueprint</h3>
                                <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Configure sections for {{ form.school_class_id ? classes.find(c => c.id === form.school_class_id)?.name : 'selected level' }}</p>
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
                                            <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                        </select>
                                        <div v-if="form.errors[`compositions.${index}.subject_id`]" class="mt-1 text-[9px] font-bold text-red-500">
                                            Required
                                        </div>
                                    </div>
                                    <div class="col-span-4">
                                        <label class="mb-2 block text-[9px] font-black tracking-widest text-slate-400 uppercase">Specific Topic (Optional)</label>
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
                                        <div v-if="form.errors[`compositions.${index}.question_count`]" class="mt-1 text-[9px] font-bold text-red-500">
                                            Min 1
                                        </div>
                                    </div>
                                    <div class="col-span-2 flex justify-end gap-2">
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
                            :disabled="form.processing || !academic_session"
                            class="flex flex-1 items-center justify-center gap-3 rounded-xl bg-primary py-5 text-sm font-black tracking-[0.2em] text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                        >
                            Create Assessment Configuration
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </component>
</template>
