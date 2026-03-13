<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { update as updateExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import DatePicker from '@/components/Form/DatePicker.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { Batch } from '@/types/academics';

interface Exam {
    id: string;
    title: string;
    duration: number;
    type: string;
    start_time: string;
    end_time: string;
    description: string | null;
    instructions: string | null;
    school_id: string;
    school_class_id: string | null;
    prospective_class_id: string | null;
    subject_id: string | null;
    compositions: Array<{
        subject_id: string;
        topic_id: string | null;
        question_count: number;
        marks_per_question: number;
        subject?: { name: string };
        topic?: { name: string };
    }>;
}

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
    exam: Exam;
    assignments: Assignment[];
    batches: Batch[];
    subjects: { id: string; name: string }[];
    classes: { id: string; name: string }[];
    sessions: any[];
}>();

const page = usePage();

const branches = computed(() => {
    const rawBranches = (page.props as any).branches || {};
    return Object.entries(rawBranches).map(([id, info]: [string, any]) => ({
        id,
        name: info.name
    }));
});

const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// Multi-Step Logic
const currentStep = ref(1);
const totalSteps = computed(() => (form.type === 'entrance' ? 4 : 3));

const form = useForm({
    title: props.exam.title,
    school_id: props.exam.school_id,
    assignment_id: '', 
    subject_id: props.exam.subject_id || '',
    school_class_id: props.exam.school_class_id || '',
    prospective_class_id: props.exam.prospective_class_id || '',
    duration: props.exam.duration,
    type: props.exam.type,
    start_time: props.exam.start_time,
    end_time: props.exam.end_time,
    description: props.exam.description || '',
    instructions: props.exam.instructions || '',
    compositions: props.exam.compositions.map(c => ({
        ...c,
        topic_id: c.topic_id || '',
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
    if (!subjectId) return;

    try {
        const response = await fetch(`/api/subjects/${subjectId}/topics`);
        if (!response.ok) throw new Error('Network response was not ok');
        form.compositions[index].available_topics = await response.json();
    } catch (error) {
        console.error('Failed to fetch topics', error);
    }
};

// Initialize topics for existing compositions
onMounted(async () => {
    for (let i = 0; i < form.compositions.length; i++) {
        await fetchTopicsForComposition(i);
    }
});

const nextStep = () => {
    if (currentStep.value < totalSteps.value) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submit = () => {
    form.put(updateExamAction(props.exam.id).url);
};

</script>

<template>
    <component :is="Layout">
        <Head title="Modify Examination" />

        <div class="max-w-5xl mx-auto pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="hover:text-primary transition-colors">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <Link href="/staff/exams" class="hover:text-primary transition-colors">Examinations</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="text-gray-800">Edit Configuration</span>
                </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Edit Examination</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Step {{ currentStep }} of {{ totalSteps }} • {{ 
                            currentStep === 1 ? 'Modify Primary Configuration' : 
                            currentStep === 2 ? 'Contextual Mappings' : 
                            currentStep === 3 ? 'Availability & Deadlines' : 'Question Breakdown'
                        }}
                    </p>
                </div>
            </div>

            <!-- Wizard Steps Layout -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <!-- Step Indicators -->
                <nav class="flex border-b border-gray-200">
                    <button 
                        v-for="step in totalSteps" 
                        :key="step"
                        @click="currentStep = step"
                        class="flex-1 py-4 px-2 text-center border-b-2 transition-colors focus:outline-none"
                        :class="[
                            currentStep === step ? 'border-primary text-primary font-semibold' : 'border-transparent text-gray-400'
                        ]"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <span :class="currentStep === step ? 'text-primary' : ''" class="text-xs">{{ step }}</span>
                            <span class="hidden sm:inline text-xs uppercase tracking-wider">
                                {{ step === 1 ? 'Identity' : step === 2 ? 'Context' : step === 3 ? 'Scheduling' : 'Structure' }}
                            </span>
                        </div>
                    </button>
                </nav>

                <div class="p-6 sm:p-10">
                    <!-- STEP 1: IDENTITY -->
                    <div v-if="currentStep === 1" class="space-y-8">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Assessment Identity</h2>
                            <p class="text-sm text-gray-500 mt-1">Modify the core metadata of this examination</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-800">Official Examination Title</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                />
                                <div v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</div>
                            </div>

                            <div class="space-y-2">
                                <CustomSelect
                                    v-model="form.school_id"
                                    label="Target Campus Branch"
                                    :options="branches"
                                    size="md"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-800">Examination Category</label>
                                <div class="flex flex-wrap gap-2">
                                    <button 
                                        v-for="type in [
                                            { id: 'ca', name: 'C.A Test' },
                                            { id: 'terminal', name: 'Terminal' },
                                            { id: 'entrance', name: 'Entrance' }
                                        ]"
                                        :key="type.id"
                                        type="button"
                                        @click="form.type = type.id"
                                        class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border transition-colors focus:outline-none"
                                        :class="form.type === type.id ? 'bg-primary text-white border-transparent' : 'bg-white text-gray-800 border-gray-200 hover:bg-gray-50'"
                                    >
                                        {{ type.name }}
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-800">Time Allocation (Minutes)</label>
                                <div class="relative">
                                    <input
                                        v-model="form.duration"
                                        type="number"
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pointer-events-none pr-4">
                                        <span class="text-xs text-gray-400">MINS</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: CONTEXT -->
                    <div v-if="currentStep === 2" class="space-y-8">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Contextual Blueprint</h2>
                            <p class="text-sm text-gray-500 mt-1">Review academic mappings</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <CustomSelect
                                v-model="form.school_class_id"
                                :label="form.type === 'entrance' ? 'Target Entry Level' : 'Academic Class'"
                                :options="classes"
                                size="md"
                            />

                            <CustomSelect
                                v-if="form.type === 'entrance'"
                                v-model="form.prospective_class_id"
                                label="Candidate Batch"
                                :options="batches"
                                size="md"
                            />

                            <CustomSelect
                                v-if="form.type !== 'entrance'"
                                v-model="form.subject_id"
                                label="Primary Subject Area"
                                :options="subjects"
                                size="md"
                            />
                        </div>
                    </div>

                    <!-- STEP 3: SCHEDULING -->
                    <div v-if="currentStep === 3" class="space-y-8">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Scheduling & Rules</h2>
                            <p class="text-sm text-gray-500 mt-1">Update availability window</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <DatePicker
                                v-model="form.start_time"
                                label="Examination Start Date & Time"
                                size="md"
                            />
                            <DatePicker
                                v-model="form.end_time"
                                label="Automatic Closure (Deadline)"
                                size="md"
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-800">Special Instructions for Candidates</label>
                            <textarea
                                v-model="form.instructions"
                                rows="4"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                            ></textarea>
                        </div>
                    </div>

                    <!-- STEP 4: STRUCTURE -->
                    <div v-if="currentStep === 4 && form.type === 'entrance'" class="space-y-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Exam Structure</h2>
                                <p class="text-sm text-gray-500 mt-1">Modify question distribution</p>
                            </div>
                            <button type="button" @click="addCompositionRow" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 transition-all">
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                                Add Row
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div v-for="(comp, index) in form.compositions" :key="index" class="p-4 bg-gray-50 rounded-xl border border-gray-200 relative">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                    <div class="md:col-span-5">
                                        <CustomSelect
                                            v-model="comp.subject_id"
                                            label="Subject Area"
                                            :options="subjects"
                                            size="sm"
                                            @change="fetchTopicsForComposition(index)"
                                        />
                                    </div>
                                    <div class="md:col-span-4">
                                        <CustomSelect
                                            v-model="comp.topic_id"
                                            label="Topic Context"
                                            :options="comp.available_topics"
                                            size="sm"
                                        />
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-800 mb-1">Questions</label>
                                        <input 
                                            v-model="comp.question_count" 
                                            type="number" 
                                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary" 
                                        />
                                    </div>
                                    <div class="md:col-span-1 flex justify-end">
                                        <button 
                                            v-if="form.compositions.length > 1"
                                            type="button" 
                                            @click="removeCompositionRow(index)"
                                            class="p-2 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-gray-400 hover:bg-red-50 hover:text-red-500 focus:outline-none"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step Navigation -->
                    <div class="mt-10 flex justify-between items-center pt-6 border-t border-gray-200">
                        <button 
                            @click="prevStep"
                            :disabled="currentStep === 1"
                            class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-0 disabled:pointer-events-none transition-all"
                        >
                            <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                            Back
                        </button>

                        <div class="flex items-center gap-4">
                            <button 
                                v-if="currentStep < totalSteps"
                                @click="nextStep"
                                class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none transition-all"
                            >
                                Continue
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>

                            <button 
                                v-else
                                @click="submit"
                                :disabled="form.processing"
                                class="py-2.5 px-6 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none transition-all"
                            >
                                <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                                Update Exam
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </component>
</template>
