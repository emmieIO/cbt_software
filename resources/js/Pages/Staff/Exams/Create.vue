<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { store as storeExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import DateTimePicker from '@/components/Form/DateTimePicker.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Topic {
    id: string;
    name: string;
    subject_id: string;
}

const props = defineProps<{
    subjects: { id: string; name: string; level: string }[];
    classes: { id: string; name: string; level: string }[];
    sessions: any[];
}>();

const page = usePage();
const academic_session = computed(() => (page.props as any).academic_session);

const branches = computed(() => {
    const rawBranches = (page.props as any).branches || {};
    return Object.values(rawBranches).map((info: any) => ({
        id: info.id,
        name: info.name,
        type: info.type,
    }));
});

const isAdmin = computed(() => (page.props.auth.user as any).permissions.includes('sys:manage_settings'));
const canCreateCrossLevel = computed(() => {
    const permissions = (page.props.auth.user as any).permissions || [];
    return permissions.includes('access:cross-level-authoring')
        || permissions.includes('bank:create_cross_level')
        || permissions.includes('exam:create_cross_level')
        || isAdmin.value;
});
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));
const compactLevelTag = (level: string) => {
    const normalized = String(level).toLowerCase();
    if (normalized === 'primary') return 'Primary';
    if (normalized === 'secondary') return 'Secondary';
    if (normalized === 'nursery') return 'Nursery';

    return normalized.charAt(0).toUpperCase() + normalized.slice(1);
};

// Pre-select the branch if the staff member only has access to one
const defaultSchoolId = branches.value.length === 1 ? branches.value[0].id : '';

const isMultiSubject = ref(false);

const form = useForm({
    title: '',
    school_id: defaultSchoolId,
    subject_id: '',
    school_class_id: '',
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

const selectedBranch = computed(() => branches.value.find((b) => b.id === form.school_id));

// LEVEL-AWARE FILTERING
const filteredSubjects = computed(() => {
    if (canCreateCrossLevel.value || !selectedBranch.value) {
        return props.subjects.map((s) => ({ ...s, name: s.name, badge: compactLevelTag(String(s.level)) }));
    }

    return props.subjects
        .filter((s) => s.level === selectedBranch.value?.type)
        .map((s) => ({ ...s, name: s.name, badge: compactLevelTag(String(s.level)) }));
});

const filteredClasses = computed(() => {
    if (canCreateCrossLevel.value || !selectedBranch.value) return props.classes;

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
        if (newVal && oldVal && newVal !== oldVal) {
            form.subject_id = '';
            form.school_class_id = '';
        }
    },
);

watch(isMultiSubject, (val) => {
    if (val && form.compositions.length === 0) {
        addCompositionRow();
    }
});

const submit = () => {
    if (!isMultiSubject.value) {
        form.compositions = [];
    }
    form.post(storeExamAction().url);
};
</script>

<template>
    <component :is="Layout">
        <Head title="Configure Examination" />

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
                    <span class="text-gray-800 font-medium">New Assessment</span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Configure New Examination</h1>
                        <p class="mt-1 text-sm text-gray-500">Define the assessment framework and scheduling rules below.</p>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <Link
                            href="/staff/exams"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            Discard
                        </Link>
                        <button
                            @click="submit"
                            :disabled="form.processing || !academic_session"
                            class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-xl shadow-primary/30 transition-all active:scale-95 disabled:opacity-50"
                        >
                            <span
                                v-if="form.processing"
                                class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent text-white"
                            ></span>
                            Publish Assessment
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- Left Columns: Main Configuration -->
                    <div class="space-y-8 lg:col-span-2">
                        <!-- 01. Assessment Identity -->
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-10">
                            <div class="mb-8 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">1</span>
                                <h2 class="text-lg font-semibold text-gray-800">Assessment Identity</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                <div class="space-y-2 md:col-span-2">
                                    <label class="block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                        >Official Examination Title</label
                                    >
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        placeholder="e.g. 2026 First Term Mock Examination"
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
                                    <div v-if="form.errors.duration" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.duration }}</div>
                                </div>

                                <div class="space-y-2 md:col-span-2">
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
                                    <div v-if="form.errors.type" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.type }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- 02. Academic Context -->
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-10">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-x-3">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">2</span>
                                    <h2 class="text-lg font-semibold text-gray-800">Academic Context</h2>
                                </div>

                                <div class="flex items-center gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-2">
                                    <input
                                        v-model="isMultiSubject"
                                        type="checkbox"
                                        id="multi_subject_toggle"
                                        class="shrink-0 rounded border-blue-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label for="multi_subject_toggle" class="text-xs font-black tracking-tight text-blue-900 uppercase"
                                        >Multi-Subject</label
                                    >
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                                <CustomSelect
                                    v-model="form.school_class_id"
                                    label="Target Academic Class"
                                    :options="filteredClasses"
                                    placeholder="Choose Level"
                                    :error="form.errors.school_class_id"
                                    size="md"
                                />

                                <CustomSelect
                                    v-if="!isMultiSubject"
                                    v-model="form.subject_id"
                                    label="Primary Subject Area"
                                    :options="filteredSubjects"
                                    placeholder="Choose Subject"
                                    :error="form.errors.subject_id"
                                    size="md"
                                />
                            </div>

                            <div v-if="selectedBranch" class="mt-8 flex gap-3 rounded-xl border border-orange-100 bg-orange-50 p-4">
                                <svg class="size-5 shrink-0 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                <p class="text-xs leading-relaxed font-medium text-orange-800">
                                    Filtering for <strong class="uppercase">{{ selectedBranch.type }}</strong> tier based on your selected branch.
                                </p>
                            </div>
                        </div>

                        <!-- 03. Syllabus Breakdown (If Multi-Subject) -->
                        <div v-if="isMultiSubject" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm md:p-10">
                            <div class="flex items-center justify-between mb-8">
                                <div class="flex items-center gap-x-3">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">3</span>
                                    <h2 class="text-lg font-semibold text-gray-800">Syllabus Breakdown</h2>
                                </div>
                                <button
                                    type="button"
                                    @click="addCompositionRow"
                                    class="inline-flex items-center gap-x-2 rounded-xl border border-transparent bg-slate-100 px-4 py-2.5 text-xs font-black tracking-widest text-slate-800 uppercase shadow-sm transition-all hover:bg-slate-200 focus:outline-none"
                                >
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Subject
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
                                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                                                >{{ comp.question_count }} Qs x {{ comp.marks_per_question }} =
                                                {{ (comp.question_count * comp.marks_per_question).toFixed(1) }} Marks</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Scheduling & Instructions -->
                    <div class="space-y-8">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">
                                    {{ isMultiSubject ? '4' : '3' }}
                                </span>
                                <h2 class="text-lg font-semibold text-gray-800">Scheduling</h2>
                            </div>

                            <div class="space-y-6">
                                <DateTimePicker
                                    v-model="form.start_time"
                                    label="Start Date & Time"
                                    placeholder="Select Schedule"
                                    size="md"
                                    :error="form.errors.start_time"
                                />
                                <DateTimePicker
                                    v-model="form.end_time"
                                    label="Automatic Deadline"
                                    placeholder="Select Closure"
                                    size="md"
                                    :error="form.errors.end_time"
                                />
                            </div>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">
                                    {{ isMultiSubject ? '5' : '4' }}
                                </span>
                                <h2 class="text-lg font-semibold text-gray-800">Protocol</h2>
                            </div>

                            <div class="space-y-2">
                                <label class="block px-1 text-[10px] font-black tracking-widest text-gray-400 uppercase"
                                    >Instructions for Candidates</label
                                >
                                <textarea
                                    v-model="form.instructions"
                                    rows="6"
                                    placeholder="e.g. Ensure your camera is active. No external devices permitted."
                                    class="block w-full rounded-xl border-gray-200 px-4 py-3 text-sm font-medium text-gray-700 shadow-sm focus:border-primary focus:ring-primary"
                                ></textarea>
                                <div v-if="form.errors.instructions" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.instructions }}</div>
                            </div>
                        </div>

                        <!-- Active Session Warning -->
                        <div v-if="!academic_session" class="rounded-xl border border-red-100 bg-red-50 p-4">
                            <div class="flex gap-3">
                                <svg class="size-5 shrink-0 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-xs leading-relaxed font-bold text-red-800">
                                    No active academic session found. You cannot publish examinations until a session is activated by the administrator.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>

<style scoped>
textarea:focus,
input:focus {
    outline: none !important;
}
</style>
