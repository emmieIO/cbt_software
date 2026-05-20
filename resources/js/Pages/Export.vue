<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    subjects: Array<{
        id: string;
        name: string;
        topics: Array<{ id: string; name: string }>;
    }>;
    levels: Array<{ value: string; label: string }>;
}>();

const form = ref({
    title: '',
    subject_id: '',
    level: 'js',
    mcq_count: 10,
    theory_count: 2,
    instructions: 'Answer all questions carefully.',
});

const submitting = ref(false);
const errors = ref<Record<string, string>>({});

const filteredSubjects = computed(() => props.subjects.filter(s => !s.level || s.level === form.value.level));

const onLevelChange = () => { form.value.subject_id = ''; };

const submit = () => {
    submitting.value = true;
    errors.value = {};

    router.post('/export/generate', form.value, {
        onFinish: () => { submitting.value = false; },
        onError: (err) => { errors.value = err as Record<string, string>; },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Export Exam" />

        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Export Exam Paper</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Generate a printable PDF exam with marking scheme.</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm dark:shadow-none dark:border-gray-700">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Exam Details</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Exam Title</label>
                            <input v-model="form.title" type="text" required class="mt-1" placeholder="e.g., First Term Examination" />
                            <p v-if="errors.title" class="mt-1 text-xs text-red-600">{{ errors.title }}</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Level</label>
                                <select v-model="form.level" class="mt-1" @change="onLevelChange">
                                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject</label>
                                <select v-model="form.subject_id" class="mt-1">
                                    <option value="" disabled>Select subject</option>
                                    <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                                <p v-if="!filteredSubjects.length" class="mt-1 text-xs text-amber-600">No subjects for this level.</p>
                                <p v-if="errors.subject_id" class="mt-1 text-xs text-red-600">{{ errors.subject_id }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm dark:shadow-none dark:border-gray-700">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Question Selection</h2>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">Questions will be randomly selected from the question bank.</p>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">MCQ Questions</label>
                            <input v-model.number="form.mcq_count" type="number" min="0" max="100" class="mt-1" />
                            <p v-if="errors.mcq_count" class="mt-1 text-xs text-red-600">{{ errors.mcq_count }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Theory Questions</label>
                            <input v-model.number="form.theory_count" type="number" min="0" max="20" class="mt-1" />
                            <p v-if="errors.theory_count" class="mt-1 text-xs text-red-600">{{ errors.theory_count }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-sm dark:shadow-none dark:border-gray-700">
                    <h2 class="text-sm font-bold text-gray-900 dark:text-gray-100">Instructions</h2>
                    <div class="mt-4">
                        <textarea v-model="form.instructions" rows="3" class="mt-1" placeholder="Instructions for the exam..." />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link href="/dashboard" class="btn-secondary dark:bg-gray-800/50">Cancel</Link>
                    <button type="submit" :disabled="submitting"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-gray-700 hover:bg-primary/90 disabled:opacity-50">
                        <svg v-if="!submitting" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span v-if="submitting" class="inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Generate PDF
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
