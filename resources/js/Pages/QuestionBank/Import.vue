<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { index as indexAction, importMethod, downloadTemplate } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Subject, SchoolClass } from '@/types/academics';
import type { User } from '@/types/auth';

const props = defineProps<{
    subjects: Subject[];
    classes: SchoolClass[];
    types: { value: string; label: string }[];
    difficulties: { value: string; label: string }[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => (page.props.auth.user as User).permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));
const compactLevelTag = (level: string) => {
    const normalized = String(level).toLowerCase();
    if (normalized === 'primary') return 'Pry';
    if (normalized === 'secondary') return 'Sec';
    if (normalized === 'nursery') return 'Nur';

    return normalized.slice(0, 3).toUpperCase();
};

const form = useForm({
    file: null as File | null,
    level: '',
    school_class_id: '',
    subject_id: '',
    difficulty: '',
    question_type: '',
});

const levels = computed(() => {
    const unique = Array.from(new Set(props.classes.map((c) => String(c.level)).filter(Boolean)));
    return unique.map((value) => ({
        id: value,
        name: value.toUpperCase(),
    }));
});

const availableClasses = computed(() => {
    if (!form.level) return [];
    return props.classes
        .filter((schoolClass) => String(schoolClass.level) === String(form.level))
        .map((schoolClass) => ({
            id: schoolClass.id,
            name: schoolClass.name,
        }));
});

const availableSubjects = computed(() => {
    if (!form.level) return [];
    return props.subjects
        .filter((subject) => String(subject.level) === String(form.level))
        .map((subject) => ({
            id: subject.id,
            name: subject.name,
            badge: compactLevelTag(String(subject.level)),
        }));
});

const importReady = computed(() => !!form.file && !!form.level && !!form.school_class_id && !!form.subject_id && !!form.difficulty);
const selectedClass = computed(() => availableClasses.value.find((schoolClass) => String(schoolClass.id) === String(form.school_class_id)));
const selectedSubject = computed(() => availableSubjects.value.find((subject) => String(subject.id) === String(form.subject_id)));
const selectedType = computed(() => props.types.find((type) => String(type.value) === String(form.question_type)));
const selectedDifficulty = computed(() => props.difficulties.find((difficulty) => String(difficulty.value) === String(form.difficulty)));
const selectedFileName = computed(() => form.file?.name || 'No file selected yet');

watch(
    () => form.level,
    () => {
        form.school_class_id = '';
        form.subject_id = '';
    },
);

const submit = () => {
    form.post(importMethod().url);
};
</script>

<template>
    <component :is="Layout">
        <Head title="Import Question Bank" />

        <div class="mx-auto max-w-7xl pb-24">
            <div class="space-y-6 sm:space-y-10">
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link :href="isAdmin ? '/admin/dashboard' : '/staff/dashboard'" class="transition-colors hover:text-primary">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link :href="indexAction().url" class="transition-colors hover:text-primary">Question Bank</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-800">Import Questions</span>
                </nav>

                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="relative border-b border-primary/10 bg-gradient-to-r from-primary via-primary to-primary/90 px-6 py-8 md:px-8">
                        <div class="absolute inset-y-0 right-0 hidden w-1/3 bg-[radial-gradient(circle_at_top_right,_rgba(255,255,255,0.22),_transparent_58%)] md:block"></div>
                        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-xs font-semibold tracking-[0.2em] text-white/75 uppercase">Question Bank Import</p>
                                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-white">Bring questions in with context first</h1>
                                <p class="mt-3 max-w-xl text-sm leading-6 text-white/80">
                                    Set the academic context up front, upload the sheet, and let the importer normalize topics automatically when they are missing.
                                </p>
                            </div>
                            <a
                                :href="downloadTemplate().url"
                                class="inline-flex items-center gap-x-2 rounded-lg border border-white/20 bg-white/12 px-4 py-2.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/18"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v10m0 0l-4-4m4 4l4-4M4 20h16" />
                                </svg>
                                Download Template
                            </a>
                        </div>
                    </div>

                    <form @submit.prevent="submit" class="grid gap-6 p-6 md:p-8 xl:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="space-y-6">
                            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                <div class="mb-6 flex items-center gap-x-3">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">1</span>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800">Import Context</h2>
                                        <p class="text-sm text-gray-500">Choose the level, class, subject, and difficulty that should apply to this upload.</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <CustomSelect
                                        v-model="form.level"
                                        label="Academic Level"
                                        :options="levels"
                                        placeholder="Choose Level"
                                        :error="form.errors.level"
                                        size="md"
                                    />
                                    <CustomSelect
                                        v-model="form.question_type"
                                        label="Default Question Type"
                                        :options="types.map((type) => ({ id: type.value, name: type.label }))"
                                        placeholder="Use type from sheet"
                                        :error="form.errors.question_type"
                                        :clearable="true"
                                        size="md"
                                    />
                                    <CustomSelect
                                        v-model="form.difficulty"
                                        label="Difficulty"
                                        :options="difficulties.map((difficulty) => ({ id: difficulty.value, name: difficulty.label }))"
                                        placeholder="Choose Difficulty"
                                        :error="form.errors.difficulty"
                                        size="md"
                                    />
                                    <CustomSelect
                                        v-model="form.school_class_id"
                                        label="Target Class"
                                        :options="availableClasses"
                                        placeholder="Choose Class"
                                        :disabled="!form.level"
                                        :error="form.errors.school_class_id"
                                        size="md"
                                    />
                                    <CustomSelect
                                        v-model="form.subject_id"
                                        label="Subject Area"
                                        :options="availableSubjects"
                                        placeholder="Choose Subject"
                                        :disabled="!form.level"
                                        :error="form.errors.subject_id"
                                        size="md"
                                    />
                                </div>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                                <div class="mb-6 flex items-center gap-x-3">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">2</span>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800">Upload Sheet</h2>
                                        <p class="text-sm text-gray-500">CSV, TXT, and XLSX are supported.</p>
                                    </div>
                                </div>

                                <label
                                    class="group block cursor-pointer overflow-hidden rounded-2xl border-2 border-dashed border-gray-200 bg-gradient-to-br from-gray-50 to-white p-8 transition hover:border-primary/40 hover:from-primary/5 hover:to-white"
                                >
                                    <input type="file" class="hidden" @input="form.file = ($event.target as HTMLInputElement).files?.[0] || null" />
                                    <div class="flex flex-col items-center text-center">
                                        <div class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary transition group-hover:scale-105">
                                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                                                />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800">{{ form.file ? 'Replace selected file' : 'Choose import file' }}</p>
                                        <p class="mt-1 text-sm text-gray-500">{{ selectedFileName }}</p>
                                        <p class="mt-3 max-w-lg text-xs leading-5 text-gray-500">
                                            Since the setup is chosen here, your sheet can stay lean. Class and difficulty do not need to be typed into the import file, and missing topics will be created automatically.
                                        </p>
                                    </div>
                                </label>
                                <p v-if="form.errors.file" class="mt-2 text-xs text-red-600">{{ form.errors.file }}</p>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="rounded-xl border border-gray-200 bg-slate-50 p-6 shadow-sm">
                                <div class="mb-5 flex items-center gap-x-3">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-slate-800 text-sm font-semibold text-white">3</span>
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800">Import Summary</h2>
                                        <p class="text-sm text-gray-500">Quick confirmation before we process the file.</p>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div class="rounded-lg border border-white bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Level</p>
                                        <p class="mt-1 text-sm font-medium text-gray-800">{{ form.level || 'Not selected' }}</p>
                                    </div>
                                    <div class="rounded-lg border border-white bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Class</p>
                                        <p class="mt-1 text-sm font-medium text-gray-800">{{ selectedClass?.name || 'Not selected' }}</p>
                                    </div>
                                    <div class="rounded-lg border border-white bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Subject</p>
                                        <p class="mt-1 text-sm font-medium text-gray-800">{{ selectedSubject?.name || 'Not selected' }}</p>
                                    </div>
                                    <div class="rounded-lg border border-white bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Question Type</p>
                                        <p class="mt-1 text-sm font-medium text-gray-800">{{ selectedType?.label || 'Use sheet values' }}</p>
                                    </div>
                                    <div class="rounded-lg border border-white bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase">Difficulty</p>
                                        <p class="mt-1 text-sm font-medium text-gray-800">{{ selectedDifficulty?.label || 'Not selected' }}</p>
                                    </div>
                                    <div class="rounded-lg border border-white bg-white px-4 py-3">
                                        <p class="text-[11px] font-semibold tracking-wide text-gray-400 uppercase">File</p>
                                        <p class="mt-1 truncate text-sm font-medium text-gray-800">{{ selectedFileName }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                                <h3 class="text-sm font-semibold text-amber-900">Normalization rules</h3>
                                <p class="mt-2 text-sm leading-6 text-amber-800">
                                    Topics are matched case-insensitively. The selected setup class and difficulty are applied to every imported question, and missing topics are created automatically.
                                </p>
                            </div>
                        </div>

                        <div class="xl:col-span-2">
                            <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="flex items-center gap-1.5" :class="form.level ? 'text-teal-600' : 'text-gray-400'">
                                        <span class="size-2 rounded-full" :class="form.level ? 'bg-teal-600' : 'bg-gray-300'"></span>
                                        <span class="text-xs font-medium">Context Selected</span>
                                    </div>
                                    <div class="flex items-center gap-1.5" :class="form.file ? 'text-teal-600' : 'text-gray-400'">
                                        <span class="size-2 rounded-full" :class="form.file ? 'bg-teal-600' : 'bg-gray-300'"></span>
                                        <span class="text-xs font-medium">File Attached</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <Link
                                        :href="indexAction().url"
                                        class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    >
                                        Back to Bank
                                    </Link>
                                    <button
                                        type="submit"
                                        :disabled="!importReady || form.processing"
                                        class="inline-flex min-w-36 items-center justify-center gap-x-2 rounded-lg border border-transparent bg-primary px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
                                    >
                                        <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                                        {{ form.processing ? 'Importing...' : 'Start Import' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </component>
</template>
