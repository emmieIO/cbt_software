<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { store as storeExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import DatePicker from '@/components/Form/DatePicker.vue';
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
    sessions: any[];
}>();

const page = usePage();
const academic_session = computed(() => (page.props as any).academic_session);

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
    title: '',
    school_id: '',
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

const useGlobalSelection = ref(isAdmin.value);

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
    form.post(storeExamAction().url);
};

// Step Validation Helpers
const isStep1Complete = computed(() => form.title && form.school_id && form.type && form.duration);
const isStep2Complete = computed(() => {
    if (useGlobalSelection.value) {
        return form.school_class_id && (form.type === 'entrance' ? form.prospective_class_id : form.subject_id);
    }
    return form.assignment_id;
});
const isStep3Complete = computed(() => form.start_time && form.end_time);

</script>

<template>
    <component :is="Layout">
        <Head title="Configure New Examination" />

        <div class="max-w-7xl mx-auto pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="hover:text-primary transition-colors">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <Link href="/staff/exams" class="hover:text-primary transition-colors">Examinations</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    <span class="text-gray-800">Configure New</span>
                </nav>

                <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Configure Examination</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Step {{ currentStep }} of {{ totalSteps }} • {{
                            currentStep === 1 ? 'Primary Configuration' :
                            currentStep === 2 ? 'Contextual Logic' :
                            currentStep === 3 ? 'Scheduling & Rules' : 'Structural Breakdown'
                        }}
                    </p>
                </div>
            </div>

            <!-- Global Error Alert -->
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 text-sm text-red-800 rounded-lg p-4 flex flex-col gap-2" role="alert">
                <div class="flex items-center gap-3">
                    <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" x2="12" y1="8" y2="12"/><line x1="12" x2="12.01" y1="16" y2="16"/></svg>
                    <span class="font-bold">Please correct the following errors:</span>
                </div>
                <ul class="list-disc list-inside ml-7">
                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                </ul>
            </div>

            <!-- Wizard Steps Layout -->
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <!-- Step Indicators -->
                <nav class="flex border-b border-gray-200">
                    <button
                        v-for="step in totalSteps"
                        :key="step"
                        @click="step < currentStep ? currentStep = step : null"
                        class="flex-1 py-4 px-2 text-center border-b-2 transition-colors focus:outline-none"
                        :class="[
                            currentStep === step ? 'border-primary text-primary font-semibold' :
                            currentStep > step ? 'border-teal-500 text-teal-600 font-medium' : 'border-transparent text-gray-400'
                        ]"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <span v-if="currentStep > step" class="size-5 flex items-center justify-center rounded-full bg-teal-100 text-teal-600">
                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                            </span>
                            <span v-else :class="currentStep === step ? 'text-primary' : ''" class="text-xs">{{ step }}</span>
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
                            <p class="text-sm text-gray-500 mt-1">Define the core metadata of this examination</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-800">Official Examination Title</label>
                                <input
                                    v-model="form.title"
                                    type="text"
                                    placeholder="e.g. 2026 First Term Mock Examination"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                />
                                <div v-if="form.errors.title" class="text-xs text-red-500 mt-1">{{ form.errors.title }}</div>
                            </div>

                            <div class="space-y-2">
                                <CustomSelect
                                    v-model="form.school_id"
                                    label="Target Campus Branch"
                                    :options="branches"
                                    placeholder="Select Location"
                                    size="md"
                                    :error="form.errors.school_id"
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
                                <div v-if="form.errors.type" class="text-xs text-red-500 mt-1">{{ form.errors.type }}</div>
                            </div>

                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-800">Time Allocation (Minutes)</label>
                                <div class="relative">
                                    <input
                                        v-model="form.duration"
                                        type="number"
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pointer-events-none pr-4">
                                        <span class="text-xs text-gray-400">MINS</span>
                                    </div>
                                </div>
                                <div v-if="form.errors.duration" class="text-xs text-red-500 mt-1">{{ form.errors.duration }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: CONTEXT -->
                    <div v-if="currentStep === 2" class="space-y-8">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Contextual Blueprint</h2>
                            <p class="text-sm text-gray-500 mt-1">Map this assessment to the academic structure</p>
                        </div>

                        <div v-if="useGlobalSelection" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <CustomSelect
                                v-model="form.school_class_id"
                                :label="form.type === 'entrance' ? 'Target Entry Level' : 'Academic Class'"
                                :options="classes"
                                placeholder="Choose Level"
                                :error="form.errors.school_class_id"
                                size="md"
                            />

                            <CustomSelect
                                v-if="form.type === 'entrance'"
                                v-model="form.prospective_class_id"
                                label="Candidate Batch"
                                :options="batches"
                                placeholder="Choose Batch"
                                :error="form.errors.prospective_class_id"
                                size="md"
                            />

                            <CustomSelect
                                v-if="form.type !== 'entrance'"
                                v-model="form.subject_id"
                                label="Primary Subject Area"
                                :options="subjects"
                                placeholder="Choose Subject"
                                :error="form.errors.subject_id"
                                size="md"
                            />
                        </div>

                        <div v-else class="space-y-6">
                            <CustomSelect
                                v-model="form.assignment_id"
                                label="Verified Teaching Load"
                                :options="assignments.map(a => ({
                                    id: a.id,
                                    name: `${a.subject?.name || 'All Subjects'} — ${a.school_class?.name || a.prospective_class?.name}`
                                }))"
                                placeholder="Select assigned context"
                                :error="form.errors.assignment_id"
                                size="md"
                                @change="handleAssignmentChange"
                            />
                        </div>

                        <!-- Toggle for Admins -->
                        <div v-if="isAdmin" class="pt-6 border-t border-gray-200 mt-4">
                            <div class="flex items-center">
                                <input
                                    v-model="useGlobalSelection"
                                    type="checkbox"
                                    id="bypass_assignments"
                                    class="shrink-0 mt-0.5 border-gray-200 rounded text-primary focus:ring-primary"
                                />
                                <label for="bypass_assignments" class="text-sm text-gray-500 ms-3">Bypass restricted assignments (Admin Override)</label>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: SCHEDULING -->
                    <div v-if="currentStep === 3" class="space-y-8">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Scheduling & Rules</h2>
                            <p class="text-sm text-gray-500 mt-1">Define the window of availability for this assessment</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <DatePicker
                                v-model="form.start_time"
                                label="Examination Start Date & Time"
                                placeholder="Select Date"
                                size="md"
                                :error="form.errors.start_time"
                            />
                            <DatePicker
                                v-model="form.end_time"
                                label="Automatic Closure (Deadline)"
                                placeholder="Select Date"
                                size="md"
                                :error="form.errors.end_time"
                            />
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-800">Special Instructions for Candidates</label>
                            <textarea
                                v-model="form.instructions"
                                rows="4"
                                placeholder="e.g. Ensure your camera is active throughout. No calculators allowed."
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                            ></textarea>
                        </div>
                    </div>

                    <!-- STEP 4: STRUCTURE -->
                    <div v-if="currentStep === 4 && form.type === 'entrance'" class="space-y-8">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800">Exam Structure</h2>
                                <p class="text-sm text-gray-500 mt-1">Break down the question distribution by subject</p>
                            </div>
                            <button
                                type="button"
                                @click="addCompositionRow"
                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-none focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none transition-all"
                            >
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
                                            placeholder="Choose Area"
                                            size="sm"
                                            @change="fetchTopicsForComposition(index)"
                                        />
                                    </div>
                                    <div class="md:col-span-4">
                                        <CustomSelect
                                            v-model="comp.topic_id"
                                            label="Topic Context"
                                            :options="comp.available_topics"
                                            placeholder="Universal Coverage"
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
                                :disabled="currentStep === 1 && !isStep1Complete || currentStep === 2 && !isStep2Complete || currentStep === 3 && !isStep3Complete"
                                class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition-all"
                            >
                                Continue
                                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                            </button>

                            <button
                                v-else
                                @click="submit"
                                :disabled="form.processing || !academic_session"
                                class="py-2.5 px-6 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition-all"
                            >
                                <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                                Finalize Exam
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </component>
</template>

<style scoped>
input:focus {
    outline: none !important;
}
</style>
