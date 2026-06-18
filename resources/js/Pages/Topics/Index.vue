<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const props = defineProps<{
    topics: {
        data: Array<{ id: string; name: string; description: string; class_level?: string | null; subject: { id: string; name: string }; questions_count: number }>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    subjects: Array<{ id: string; name: string; level: string }>;
    levels: Array<{ value: string; label: string }>;
    classLevels: Record<string, Array<{ value: string; label: string }>>;
    filters: {
        level?: string;
        class_level?: string;
    };
}>();

const showForm = ref(false);
const editing = ref<string | null>(null);
const deleteTarget = ref<string | null>(null);
const filterLevel = ref(props.filters.level || '');
const filterClassLevel = ref(props.filters.class_level || '');
const formSubjectId = ref('');
const form = ref({ name: '', subject_id: '', class_level: '', description: '' });

const filteredSubjects = computed(() => props.subjects.filter(s => !filterLevel.value || s.level === filterLevel.value));
const classLevelOptions = computed(() => props.classLevels[filterLevel.value] || []);
const selectedFormSubject = computed(() => props.subjects.find((subject) => subject.id === formSubjectId.value));
const formClassLevelOptions = computed(() => props.classLevels[selectedFormSubject.value?.level || filterLevel.value] || []);
const classLevelLabel = (value?: string | null) =>
    value ? (Object.values(props.classLevels).flat().find((option) => option.value === value)?.label ?? value) : 'All';

const syncFormClassLevel = () => {
    if (!formClassLevelOptions.value.some((option) => option.value === form.value.class_level)) {
        form.value.class_level = formClassLevelOptions.value[0]?.value || '';
    }
};

watch(filterLevel, (value) => {
    filterClassLevel.value = '';
    router.get('/topics', {
        level: value || undefined,
        class_level: undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

watch(filterClassLevel, (value) => {
    router.get('/topics', {
        level: filterLevel.value || undefined,
        class_level: value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
});

const openCreate = () => {
    editing.value = null;
    formSubjectId.value = filteredSubjects.value[0]?.id || '';
    form.value = { name: '', subject_id: formSubjectId.value, class_level: '', description: '' };
    syncFormClassLevel();
    showForm.value = true;
};

const openEdit = (topic: any) => {
    editing.value = topic.id;
    formSubjectId.value = topic.subject.id;
    const subject = props.subjects.find((item) => item.id === topic.subject.id);
    filterLevel.value = subject?.level || filterLevel.value;
    form.value = { name: topic.name, subject_id: topic.subject.id, class_level: topic.class_level || classLevelOptions.value[0]?.value || '', description: topic.description || '' };
    syncFormClassLevel();
    showForm.value = true;
};

const save = () => {
    form.value.subject_id = formSubjectId.value;
    if (editing.value) {
        router.put(`/topics/${editing.value}`, form.value, { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
    } else {
        router.post('/topics', form.value, { preserveScroll: true, onSuccess: () => { showForm.value = false; } });
    }
};

const confirmDelete = () => {
    if (deleteTarget.value) {
        router.delete(`/topics/${deleteTarget.value}`, { preserveScroll: true });
        deleteTarget.value = null;
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Topics" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Topics</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Manage topics under each subject.</p>
                </div>
                <button @click="openCreate" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white shadow-sm dark:shadow-none dark:border-green-900/60 hover:bg-primary/90">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Topic
                </button>
            </div>

            <!-- Level Filter -->
            <div class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 p-4">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-200">Level:</label>
                <select v-model="filterLevel" class="w-full sm:w-auto sm:min-w-[160px]">
                    <option value="">All Levels</option>
                    <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                </select>
                <select v-model="filterClassLevel" class="w-full sm:w-auto sm:min-w-[140px]" :disabled="!filterLevel">
                    <option value="">All Classes</option>
                    <option v-for="classLevel in classLevelOptions" :key="classLevel.value" :value="classLevel.value">{{ classLevel.label }}</option>
                </select>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ filteredSubjects.length }} subject{{ filteredSubjects.length !== 1 ? 's' : '' }}
                </span>
            </div>

            <!-- Form Modal -->
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-green-950/60 p-6 shadow-xl">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">{{ editing ? 'Edit Topic' : 'New Topic' }}</h2>
                    <form @submit.prevent="save" class="mt-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Subject</label>
                            <select v-model="formSubjectId" required class="mt-1" @change="syncFormClassLevel">
                                <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Class Level</label>
                            <select v-model="form.class_level" required class="mt-1">
                                <option value="" disabled>Select class level</option>
                                <option v-for="classLevel in formClassLevelOptions" :key="classLevel.value" :value="classLevel.value">{{ classLevel.label }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Topic Name</label>
                            <input v-model="form.name" type="text" required class="mt-1" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description (optional)</label>
                            <textarea v-model="form.description" rows="3" class="mt-1" />
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showForm = false" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Topics List -->
            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-green-900/60 bg-white dark:bg-green-950/60 shadow-sm dark:shadow-none dark:border-green-900/60">
                <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 dark:bg-green-950/45">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Topic</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Subject</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Class</th>
                            <th class="px-5 py-3 text-center text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Questions</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold tracking-wide text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="topic in topics.data" :key="topic.id" class="hover:bg-gray-50 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-4">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ topic.name }}</p>
                                <p v-if="topic.description" class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500 line-clamp-1">{{ topic.description }}</p>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ topic.subject.name }}</td>
                            <td class="px-5 py-4 text-sm text-gray-600 dark:text-gray-300">{{ classLevelLabel(topic.class_level) }}</td>
                            <td class="px-5 py-4 text-center text-sm text-gray-600 dark:text-gray-300">{{ topic.questions_count }}</td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="openEdit(topic)" class="text-xs font-medium text-primary hover:underline">Edit</button>
                                    <button @click="deleteTarget = topic.id" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="topics.data.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">No topics yet.</td>
                        </tr>
                    </tbody>
                </table></div>
            </div>

            <div v-if="topics.last_page > 1" class="flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">Page {{ topics.current_page }} of {{ topics.last_page }}</p>
                <div class="flex gap-2">
                    <Link v-if="topics.prev_page_url" :href="topics.prev_page_url" class="rounded-lg border border-gray-200 dark:border-green-900/60 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-green-950/45">Previous</Link>
                    <Link v-if="topics.next_page_url" :href="topics.next_page_url" class="rounded-lg border border-gray-200 dark:border-green-900/60 px-3 py-1.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:bg-green-950/45">Next</Link>
                </div>
            </div>
        </div>
    </AppLayout>

    <ConfirmationModal
        :show="!!deleteTarget"
        title="Delete Topic"
        message="This action cannot be undone."
        confirm-label="Delete"
        variant="danger"
        @close="deleteTarget = null"
        @confirm="confirmDelete"
    />
</template>
