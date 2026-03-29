<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, onMounted, watch } from 'vue';
import { update as updateExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import DateTimePicker from '@/components/Form/DateTimePicker.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

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
    subject_id: string | null;
    status: string;
    academic_session_id: string;
    compositions: Array<{
        subject_id: string;
        topic_id: string | null;
        question_count: number;
        marks_per_question: number;
        subject?: { name: string };
        topic?: { name: string };
    }>;
}

interface Topic {
    id: string;
    name: string;
    subject_id: string;
}

const props = defineProps<{
    exam: Exam;
    subjects: { id: string; name: string; level: string }[];
    classes: { id: string; name: string; level: string }[];
    sessions: any[];
}>();

const page = usePage();

const branches = computed(() => {
    const rawBranches = (page.props as any).branches || {};
    return Object.values(rawBranches).map((info: any) => ({
        id: info.id,
        name: info.name,
        type: info.type,
    }));
});

const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// UI State
const currentStep = ref(1);
const isMultiSubject = ref(props.exam.compositions.length > 0);
const totalSteps = computed(() => (isMultiSubject.value ? 4 : 3));
const isInitialMount = ref(true);

const form = useForm({
    title: props.exam.title,
    school_id: props.exam.school_id,
    subject_id: props.exam.subject_id || '',
    school_class_id: props.exam.school_class_id || '',
    duration: props.exam.duration,
    type: props.exam.type,
    start_time: props.exam.start_time,
    end_time: props.exam.end_time,
    description: props.exam.description || '',
    instructions: props.exam.instructions || '',
    status: props.exam.status,
    compositions: props.exam.compositions.map((c) => ({
        ...c,
        topic_id: c.topic_id || '',
        available_topics: [] as Topic[],
    })),
});

const selectedBranch = computed(() => branches.value.find((b) => b.id === form.school_id));

// LEVEL-AWARE FILTERING
const filteredSubjects = computed(() => {
    if (!selectedBranch.value) return props.subjects;
    return props.subjects.filter((s) => s.level === selectedBranch.value?.type).map((s) => ({ ...s, name: `${s.name} (${s.level.toUpperCase()})` }));
});

const filteredClasses = computed(() => {
    if (!selectedBranch.value) return props.classes;
    return props.classes.filter((c) => c.level === selectedBranch.value?.type);
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

// Reset subjects/classes if branch changes to avoid level mismatch
watch(
    () => form.school_id,
    (newVal, oldVal) => {
        if (!isInitialMount.value && newVal && oldVal && newVal !== oldVal) {
            form.subject_id = '';
            form.school_class_id = '';
        }
    },
);

// Initialize topics for existing compositions
onMounted(async () => {
    for (let i = 0; i < form.compositions.length; i++) {
        await fetchTopicsForComposition(i);
    }
    // Safeguard school_id against hydration shifts
    if (props.exam.school_id && form.school_id !== props.exam.school_id) {
        form.school_id = props.exam.school_id;
    }
    isInitialMount.value = false;
});

watch(isMultiSubject, (val) => {
    if (val && form.compositions.length === 0) {
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
    if (!isMultiSubject.value) {
        form.compositions = [];
    }
    form.put(updateExamAction(props.exam.id).url);
};
</script>

<template>
    <component :is="Layout">
        <Head title="Edit Examination" />

        <div class="mx-auto max-w-7xl pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="transition-colors hover:text-primary">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link href="/staff/exams" class="transition-colors hover:text-primary">Examinations</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-800 font-medium">Edit Configuration</span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Modify Assessment Protocol</h1>
                        <p class="mt-1 text-sm text-gray-500">Step {{ currentStep }} of {{ totalSteps }} • {{ 
                            currentStep === 1 ? 'Primary Configuration' : 
                            currentStep === 2 ? 'Contextual Mappings' : 
                            currentStep === 3 ? 'Availability & Deadlines' : 'Question Breakdown' 
                        }}</p>
                    </div>
                </div>

                <!-- Wizard Steps Layout -->
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                    <!-- Step Indicators -->
                    <nav class="flex border-b border-gray-200 bg-gray-50/50">
                        <button
                            v-for="step in totalSteps"
                            :key="step"
                            @click="currentStep = step"
                            class="flex-1 border-b-2 px-2 py-4 text-center transition-colors focus:outline-none"
                            :class="[
                                currentStep === step
                                    ? 'border-primary font-semibold text-primary'
                                    : 'border-transparent text-gray-400',
                            ]"
                        >
                            <div class="flex items-center justify-center gap-2">
                                <span :class="currentStep === step ? 'text-primary' : ''" class="text-xs font-bold">{{ step }}</span>
                                <span class="hidden text-[10px] font-black tracking-wider uppercase sm:inline">
                                    {{ step === 1 ? 'Identity' : step === 2 ? 'Context' : step === 3 ? 'Scheduling' : 'Blueprint' }}
                                </span>
                            </div>
                        </button>
                    </nav>

                    <div class="p-6 sm:p-10">
                        <!-- STEP 1: IDENTITY -->
                        <div v-if="currentStep === 1" class="animate-in fade-in slide-in-from-bottom-4 space-y-8 duration-500">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">1</span>
                                <h2 class="text-lg font-semibold text-gray-800">Assessment Identity</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label class="block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                        >Official Examination Title</label
                                    >
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        class="block w-full rounded-xl border-gray-200 px-4 py-3.5 text-sm font-semibold text-gray-800 shadow-sm focus:border-primary focus:ring-primary"
                                    />
                                    <div v-if="form.errors.title" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.title }}</div>
                                </div>

                                <div class="space-y-2">
                                    <CustomSelect
                                        v-model="form.school_id"
                                        label="School Branch"
                                        :options="branches"
                                        placeholder="Select Location"
                                        size="md"
                                        :error="form.errors.school_id"
                                    />
                                </div>

                                <div class="space-y-2">
                                    <label class="block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                        >Examination Category</label
                                    >
                                    <div class="flex flex-wrap gap-3">
                                        <button
                                            v-for="type in [
                                                { id: 'ca', name: 'C.A Test' },
                                                { id: 'terminal', name: 'Terminal' },
                                            ]"
                                            :key="type.id"
                                            type="button"
                                            @click="form.type = type.id"
                                            class="inline-flex items-center gap-x-2 rounded-xl border px-5 py-2.5 text-xs font-black uppercase shadow-sm transition-all"
                                            :class="
                                                form.type === type.id
                                                    ? 'border-slate-900 bg-slate-900 text-white shadow-slate-200'
                                                    : 'border-gray-200 bg-white text-gray-500 hover:border-gray-300 hover:bg-gray-50'
                                            "
                                        >
                                            {{ type.name }}
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                        >Duration (Minutes)</label
                                    >
                                    <div class="relative">
                                        <input
                                            v-model="form.duration"
                                            type="number"
                                            class="block w-full rounded-xl border-gray-200 px-4 py-3.5 text-sm font-black text-gray-800 shadow-sm focus:border-primary focus:ring-primary"
                                        />
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4">
                                            <span class="text-[10px] font-black text-gray-400 uppercase">MINS</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2: CONTEXT -->
                        <div v-if="currentStep === 2" class="animate-in fade-in slide-in-from-bottom-4 space-y-8 duration-500">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-x-3">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">2</span>
                                    <h2 class="text-lg font-semibold text-gray-800">Contextual Blueprint</h2>
                                </div>

                                <div class="flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-2">
                                    <input
                                        v-model="isMultiSubject"
                                        type="checkbox"
                                        id="multi_subject_toggle"
                                        class="shrink-0 rounded border-blue-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label for="multi_subject_toggle" class="text-xs font-black tracking-tight text-blue-900 uppercase"
                                        >Multi-Subject Assessment</label
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                <CustomSelect
                                    v-model="form.school_class_id"
                                    label="Academic Class"
                                    :options="filteredClasses"
                                    placeholder="Choose Level"
                                    size="md"
                                    :error="form.errors.school_class_id"
                                />

                                <CustomSelect
                                    v-if="!isMultiSubject"
                                    v-model="form.subject_id"
                                    label="Primary Subject Area"
                                    :options="filteredSubjects"
                                    placeholder="Choose Subject"
                                    size="md"
                                    :error="form.errors.subject_id"
                                />
                            </div>

                            <!-- Level Filter Note -->
                            <div v-if="selectedBranch" class="flex gap-3 rounded-xl border border-orange-100 bg-orange-50 p-4">
                                <svg class="size-5 shrink-0 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <p class="text-xs leading-relaxed font-medium text-orange-800">
                                    Selection currently restricted to the <strong class="uppercase">{{ selectedBranch.type }}</strong> tier based on
                                    your selected school branch.
                                </p>
                            </div>
                        </div>

                        <!-- STEP 3: SCHEDULING -->
                        <div v-if="currentStep === 3" class="animate-in fade-in slide-in-from-bottom-4 space-y-8 duration-500">
                            <div class="flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">3</span>
                                <h2 class="text-lg font-semibold text-gray-800">Scheduling & Protocol</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                <DateTimePicker
                                    v-model="form.start_time"
                                    label="Examination Start Date & Time"
                                    placeholder="Select Date & Time"
                                    size="md"
                                    :error="form.errors.start_time"
                                />
                                <DateTimePicker
                                    v-model="form.end_time"
                                    label="Automatic Closure (Deadline)"
                                    placeholder="Select Date & Time"
                                    size="md"
                                    :error="form.errors.end_time"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                    >Instructions for Candidates</label
                                >
                                <textarea
                                    v-model="form.instructions"
                                    rows="4"
                                    placeholder="e.g. Ensure your camera is active. No external devices permitted."
                                    class="block w-full rounded-xl border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary"
                                ></textarea>
                            </div>
                        </div>

                        <!-- STEP 4: STRUCTURE -->
                        <div v-if="currentStep === 4" class="animate-in fade-in slide-in-from-bottom-4 space-y-8 duration-500">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-x-3">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">4</span>
                                    <h2 class="text-lg font-semibold text-gray-800">Syllabus Breakdown</h2>
                                </div>
                                <button
                                    type="button"
                                    @click="addCompositionRow"
                                    class="inline-flex items-center gap-x-2 rounded-xl border border-transparent bg-slate-100 px-4 py-2.5 text-xs font-black tracking-widest text-slate-800 uppercase shadow-sm transition-all hover:bg-slate-200 focus:outline-none"
                                >
                                    <svg
                                        class="size-4 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M5 12h14" />
                                        <path d="M12 5v14" />
                                    </svg>
                                    Append Subject
                                </button>
                            </div>

                            <div class="space-y-4">
                                <div
                                    v-for="(comp, index) in form.compositions"
                                    :key="index"
                                    class="group relative rounded-2xl border border-gray-200 bg-gray-50/50 p-6 transition-all hover:bg-white hover:shadow-lg"
                                >
                                    <div class="grid grid-cols-1 items-end gap-6 md:grid-cols-12">
                                        <div class="md:col-span-4">
                                            <CustomSelect
                                                v-model="comp.subject_id"
                                                label="Subject Area"
                                                :options="filteredSubjects"
                                                placeholder="Choose Area"
                                                size="sm"
                                                @change="fetchTopicsForComposition(index)"
                                                :error="form.errors[`compositions.${index}.subject_id`]"
                                            />
                                        </div>
                                        <div class="md:col-span-3">
                                            <CustomSelect
                                                v-model="comp.topic_id"
                                                label="Topic Context"
                                                :options="comp.available_topics"
                                                placeholder="Universal Coverage"
                                                size="sm"
                                                :error="form.errors[`compositions.${index}.topic_id`]"
                                            />
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                                >Question Count</label
                                            >
                                            <input
                                                v-model="comp.question_count"
                                                type="number"
                                                class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm font-black text-slate-800 shadow-sm focus:border-primary focus:ring-primary"
                                                :class="form.errors[`compositions.${index}.question_count`] ? 'border-red-500' : ''"
                                            />
                                        </div>
                                        <div class="md:col-span-2">
                                            <label class="mb-1 block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                                >Marks/Q</label
                                            >
                                            <input
                                                v-model="comp.marks_per_question"
                                                type="number"
                                                step="0.5"
                                                class="block w-full rounded-lg border-gray-200 px-3 py-2 text-sm font-black text-slate-800 shadow-sm focus:border-primary focus:ring-primary"
                                                :class="form.errors[`compositions.${index}.marks_per_question`] ? 'border-red-500' : ''"
                                            />
                                        </div>
                                        <div class="flex justify-end md:col-span-1">
                                            <button
                                                v-if="form.compositions.length > 1"
                                                type="button"
                                                @click="removeCompositionRow(index)"
                                                class="inline-flex size-10 items-center justify-center rounded-xl border border-transparent p-2 text-gray-400 transition-all hover:bg-red-50 hover:text-red-500"
                                            >
                                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

                                    <!-- Feedback Row -->
                                    <div class="mt-3 flex items-center justify-between">
                                        <div class="flex gap-4">
                                            <div v-if="form.errors[`compositions.${index}.question_count`]" class="text-xs font-bold text-red-500">
                                                {{ form.errors[`compositions.${index}.question_count`] }}
                                            </div>
                                            <div
                                                v-if="form.errors[`compositions.${index}.marks_per_question`]"
                                                class="text-xs font-bold text-red-500"
                                            >
                                                {{ form.errors[`compositions.${index}.marks_per_question`] }}
                                            </div>
                                        </div>
                                        <div
                                            v-if="comp.question_count && comp.marks_per_question"
                                            class="text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                        >
                                            Subtotal:
                                            <span class="text-slate-900"
                                                >{{ comp.question_count }} questions x {{ comp.marks_per_question }} marks =
                                                {{ (comp.question_count * comp.marks_per_question).toFixed(1) }} marks</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step Navigation -->
                        <div class="mt-12 flex items-center justify-between border-t border-gray-100 pt-8">
                            <button
                                @click="prevStep"
                                :disabled="currentStep === 1"
                                class="inline-flex items-center gap-x-2 rounded-xl border border-gray-200 bg-white px-6 py-3 text-xs font-black tracking-widest text-gray-500 uppercase shadow-sm transition-all hover:bg-gray-50 active:scale-95 disabled:pointer-events-none disabled:opacity-0"
                            >
                                <svg
                                    class="size-4 shrink-0"
                                    xmlns="http://www.w3.org/2000/svg"
                                    width="24"
                                    height="24"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m15 18-6-6 6-6" />
                                </svg>
                                Back
                            </button>

                            <div class="flex items-center gap-4">
                                <button
                                    v-if="currentStep < totalSteps"
                                    @click="nextStep"
                                    class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-xl border border-transparent bg-primary px-8 py-3 text-xs font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all focus:outline-none active:scale-95"
                                >
                                    Continue
                                    <svg
                                        class="size-4 shrink-0"
                                        xmlns="http://www.w3.org/2000/svg"
                                        width="24"
                                        height="24"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="m9 18 6-6-6-6" />
                                    </svg>
                                </button>

                                <button
                                    v-else
                                    @click="submit"
                                    :disabled="form.processing"
                                    class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-xl border border-transparent bg-primary px-10 py-3 text-xs font-black tracking-widest text-white uppercase shadow-xl shadow-primary/30 transition-all focus:outline-none active:scale-95 disabled:opacity-50"
                                >
                                    <span
                                        v-if="form.processing"
                                        class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent text-white"
                                    ></span>
                                    Update Protocol
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
