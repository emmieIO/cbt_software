<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { index as indexAction, batchStore } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { AppPageProps } from '@/types';
import type { Subject } from '@/types/academics';

const props = defineProps<{
    subjects: Subject[];
    classes: any[];
    batches: any[];
    types: any[];
    difficulties: any[];
}>();

const page = usePage<AppPageProps>();
const isAdmin = computed(() => page.props.auth.user.permissions.includes('sys:manage_settings'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

// Individual row structure
const createEmptyRow = () => ({
    subject_id: '',
    topic_id: '',
    school_class_id: '',
    prospective_class_id: '',
    content: '',
    explanation: '',
    type: 'multiple_choice',
    difficulty: 'medium',
    image: null as File | null,
    imagePreview: null as string | null,
    options: [
        { content: '', is_correct: true },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
        { content: '', is_correct: false },
    ],
});

const questions = ref([createEmptyRow()]);

// Bulk Metadata tools
const bulkSubject = ref('');
const bulkClass = ref('');
const bulkTopic = ref('');

const applyBulkMetadata = () => {
    questions.value.forEach(q => {
        if (bulkSubject.value) q.subject_id = bulkSubject.value;
        if (bulkClass.value) q.school_class_id = bulkClass.value;
        if (bulkTopic.value) q.topic_id = bulkTopic.value;
    });
};

const addRow = () => {
    const lastRow = questions.value[questions.value.length - 1];
    const newRow = createEmptyRow();
    
    if (lastRow) {
        newRow.subject_id = lastRow.subject_id;
        newRow.school_class_id = lastRow.school_class_id;
        newRow.topic_id = lastRow.topic_id;
        newRow.prospective_class_id = lastRow.prospective_class_id;
        newRow.type = lastRow.type;
        newRow.difficulty = lastRow.difficulty;
    }
    
    questions.value.push(newRow);
};

const removeRow = (index: number) => {
    if (questions.value.length > 1) {
        questions.value.splice(index, 1);
    }
};

const handleImageUpload = (index: number, e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) {
        questions.value[index].image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            questions.value[index].imagePreview = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const triggerFileInput = (index: number) => {
    const input = document.getElementById(`file-input-${index}`) as HTMLInputElement;
    if (input) input.click();
};

const removeImage = (index: number) => {
    questions.value[index].image = null;
    questions.value[index].imagePreview = null;
    const input = document.getElementById(`file-input-${index}`) as HTMLInputElement;
    if (input) input.value = '';
};

// Form submission
const form = useForm({
    questions: [] as any[]
});

const submit = () => {
    form.questions = questions.value.map(q => ({
        topic_id: q.topic_id,
        school_class_id: q.school_class_id,
        prospective_class_id: q.prospective_class_id,
        content: q.content,
        explanation: q.explanation,
        type: q.type,
        difficulty: q.difficulty,
        image: q.image,
        options: q.options
    }));

    form.post(batchStore().url);
};

const getFilteredTopics = (subjectId: string, classId: string) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    if (!subject || !classId) return [];
    
    // Show topics that either belong to the selected class OR have no class assigned (global topics)
    return (subject as any).topics.filter((t: any) => !t.school_class_id || t.school_class_id === classId);
};

const getAvailableClasses = (subjectId: string) => {
    const subject = props.subjects.find(s => s.id === subjectId);
    if (!subject) return [];

    // We always show all authorized classes passed from the server
    // as QuestionService already filters these based on the user's context.
    return props.classes;
};
</script>

<template>
    <component :is="Layout" wide>
        <Head title="Spreadsheet Mode" />

        <div class="space-y-6">
            <!-- Minimal Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="indexAction().url" class="inline-flex items-center justify-center size-8 rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-gray-50 transition-all">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                    </Link>
                    <h1 class="text-xl font-semibold text-gray-800">Spreadsheet Mode</h1>
                    <span class="inline-flex items-center py-1 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ questions.length }} Rows</span>
                </div>
                
                <div class="flex items-center gap-3">
                    <button 
                        @click="submit"
                        :disabled="form.processing"
                        class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
                    >
                        <span v-if="form.processing" class="animate-spin inline-block size-4 border-[3px] border-current border-t-transparent text-white rounded-full"></span>
                        <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        Publish All
                    </button>
                </div>
            </div>

            <!-- Compact Metadata Bar -->
            <div class="flex items-center gap-4 bg-white border border-gray-200 rounded-xl shadow-sm p-3">
                <div class="flex items-center gap-2 px-3 border-r border-gray-200">
                    <div class="h-2 w-2 rounded-full bg-primary"></div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider whitespace-nowrap">Bulk Actions</span>
                </div>
                <select v-model="bulkSubject" class="py-2 px-3 block w-full max-w-[200px] border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50">
                    <option value="">Subject</option>
                    <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <select v-model="bulkClass" class="py-2 px-3 block w-full max-w-[150px] border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50">
                    <option value="">Class</option>
                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <button 
                    @click="applyBulkMetadata"
                    class="py-2 px-4 inline-flex items-center gap-x-2 text-xs font-semibold rounded-lg border border-transparent bg-gray-900 text-white hover:bg-black disabled:opacity-50 disabled:pointer-events-none"
                >
                    Apply to all
                </button>
            </div>

            <!-- Compact Grid -->
            <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">#</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200 w-64">Context</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200 w-24">Image</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200">Content</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-r border-gray-200 w-80">Options (A-D)</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(q, idx) in questions" :key="idx" class="hover:bg-gray-50 transition-colors">
                                <!-- Index -->
                                <td class="px-4 py-3 text-xs font-medium text-gray-400 text-center border-r border-gray-200">{{ idx + 1 }}</td>
                                
                                <!-- Compact Context -->
                                <td class="px-4 py-3 space-y-2 border-r border-gray-200 bg-gray-50/30">
                                    <select v-model="q.subject_id" class="py-1 px-2 block w-full border-gray-200 rounded-md text-[11px] focus:border-primary focus:ring-primary bg-white">
                                        <option value="">Subject</option>
                                        <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <select v-model="q.school_class_id" class="py-1 px-2 block w-full border-gray-200 rounded-md text-[11px] focus:border-primary focus:ring-primary bg-white">
                                        <option value="">Class</option>
                                        <option v-for="c in getAvailableClasses(q.subject_id)" :key="c.id" :value="c.id">{{ c.name }}</option>
                                    </select>
                                    <select v-model="q.topic_id" class="py-1 px-2 block w-full border-gray-200 rounded-md text-[11px] focus:border-primary focus:ring-primary bg-white">
                                        <option value="">Topic</option>
                                        <option v-for="t in getFilteredTopics(q.subject_id, q.school_class_id)" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                </td>

                                <!-- Compact Image -->
                                <td class="px-4 py-3 text-center border-r border-gray-200">
                                    <div v-if="q.imagePreview" class="relative group inline-block">
                                        <img :src="q.imagePreview" class="size-12 rounded-lg object-cover border border-gray-200" />
                                        <button @click="removeImage(idx)" class="absolute -top-1.5 -right-1.5 size-5 rounded-full bg-red-600 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                                            <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <button v-else @click="triggerFileInput(idx)" class="size-12 flex items-center justify-center rounded-lg border border-dashed border-gray-300 text-gray-300 hover:text-primary hover:border-primary transition-colors">
                                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        <input :id="'file-input-'+idx" type="file" class="hidden" accept="image/*" @change="(e) => handleImageUpload(idx, e)" />
                                    </button>
                                </td>

                                <!-- Compact Content -->
                                <td class="px-4 py-3 border-r border-gray-200">
                                    <textarea v-model="q.content" rows="2" placeholder="Question content..." class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary bg-white resize-none"></textarea>
                                    <input v-model="q.explanation" placeholder="Explanation (Optional)" class="mt-2 py-1 px-2 block w-full border-gray-200 rounded-md text-xs focus:border-primary focus:ring-primary bg-white" />
                                </td>

                                <!-- Compact Options -->
                                <td class="px-4 py-3 border-r border-gray-200 bg-gray-50/10">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="flex items-center gap-2">
                                            <input 
                                                type="radio" 
                                                :checked="opt.is_correct"
                                                @change="() => q.options.forEach((o, i) => o.is_correct = i === oIdx)"
                                                class="shrink-0 size-3.5 border-gray-200 rounded-full text-primary focus:ring-primary"
                                            />
                                            <input v-model="opt.content" :placeholder="'Option '+String.fromCharCode(65+oIdx)" class="py-1 px-2 block w-full border-gray-200 rounded-md text-xs focus:border-primary focus:ring-primary bg-white" />
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-center">
                                    <button @click="removeRow(idx)" class="text-gray-400 hover:text-red-600 transition-colors">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Floating Minimal Action Bar -->
            <div class="flex justify-center pt-4 pb-8">
                <button 
                    @click="addRow"
                    class="py-3 px-6 inline-flex items-center gap-x-2 text-sm font-semibold rounded-full border border-transparent bg-gray-900 text-white hover:bg-black transition-all shadow-lg"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    Add New Row
                </button>
            </div>
        </div>
    </component>
</template>

<style scoped>
.sidebar-scrollbar::-webkit-scrollbar {
    height: 4px;
}
.sidebar-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.sidebar-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.radio-primary {
    --chkbg: var(--color-primary);
}
/* Standard Spreadsheet Look */
input, textarea, select {
    outline: none !important;
}
</style>
