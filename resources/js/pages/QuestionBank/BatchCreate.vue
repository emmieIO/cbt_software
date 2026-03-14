<script setup lang="ts">
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { index as indexAction, batchStore } from '@/actions/App/Http/Controllers/Staff/StaffQuestionController';
import CustomSelect from '@/components/Form/CustomSelect.vue';
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

const branches = computed(() => {
    const rawBranches = (page.props as any).branches || {};
    return Object.values(rawBranches).map((info: any) => ({
        id: info.id,
        name: info.name,
        type: info.type
    }));
});

// Context Awareness
const selectedBranchId = ref(page.props.auth.user.school_id || (branches.value.length > 0 ? branches.value[0].id : ''));
const selectedBranch = computed(() => branches.value.find(b => b.id === selectedBranchId.value));

const filteredSubjects = computed(() => {
    if (!selectedBranch.value) return props.subjects;
    return props.subjects
        .filter(s => s.level === selectedBranch.value?.type)
        .map(s => ({ ...s, name: `${s.name} (${s.level.toUpperCase()})` }));
});

const filteredClasses = computed(() => {
    if (!selectedBranch.value) return props.classes;
    return props.classes.filter(c => c.level === selectedBranch.value?.type);
});

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

// Reset bulk and row metadata if tier changes to avoid mismatch
watch(selectedBranchId, () => {
    bulkSubject.value = '';
    bulkClass.value = '';
    bulkTopic.value = '';
    
    // Optional: questions.value.forEach(q => { q.subject_id = ''; q.school_class_id = ''; q.topic_id = ''; });
});

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

const getAvailableClasses = () => {
    return filteredClasses.value;
};
</script>

<template>
    <component :is="Layout" wide>
        <Head title="Spreadsheet Mode" />

        <div class="space-y-6 pb-24">
            <!-- Header -->
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
                        class="py-2 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none transition-all shadow-sm active:scale-95"
                    >
                        <span v-if="form.processing" class="animate-spin inline-block size-4 border-[3px] border-current border-t-transparent text-white rounded-full"></span>
                        <svg v-else class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                        Publish All
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Institutional Scope & Context -->
                <div class="lg:col-span-4">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4 h-full">
                        <div class="flex items-center gap-3">
                            <div class="size-8 flex items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">{{ isAdmin ? 'Campus Context' : 'Authorized Context' }}</h2>
                                <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">{{ isAdmin ? 'Select target branch' : 'Current active level' }}</p>
                            </div>
                        </div>

                        <CustomSelect 
                            v-if="isAdmin"
                            v-model="selectedBranchId"
                            :options="branches"
                            placeholder="Choose Branch"
                            size="sm"
                        />

                        <div v-else class="p-3 bg-gray-50 rounded-lg border border-gray-100">
                            <div class="text-xs font-bold text-gray-800">{{ selectedBranch?.name || 'Loading branch...' }}</div>
                        </div>

                        <div v-if="selectedBranch" class="flex items-center gap-3 p-3 bg-teal-50 border border-teal-100 rounded-lg transition-all">
                            <div class="size-2 rounded-full bg-teal-500 animate-pulse"></div>
                            <span class="text-xs font-black text-teal-800 uppercase tracking-tighter">Verified Level: {{ selectedBranch.type }}</span>
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="lg:col-span-8">
                    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 space-y-4 h-full">
                        <div class="flex items-center gap-3">
                            <div class="size-8 flex items-center justify-center rounded-lg bg-slate-900 text-white">
                                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                            </div>
                            <div>
                                <h2 class="text-sm font-bold text-gray-800">Bulk Metadata</h2>
                                <p class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Apply settings to all rows</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 items-end">
                            <div class="flex-1 min-w-[180px]">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Subject</label>
                                <select v-model="bulkSubject" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary bg-gray-50">
                                    <option value="">Select Subject</option>
                                    <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div class="flex-1 min-w-[140px]">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Class</label>
                                <select v-model="bulkClass" class="py-2 px-3 block w-full border-gray-200 rounded-lg text-xs focus:border-primary focus:ring-primary bg-gray-50">
                                    <option value="">Select Class</option>
                                    <option v-for="c in filteredClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                            <button 
                                @click="applyBulkMetadata"
                                class="py-2 px-6 inline-flex items-center gap-x-2 text-xs font-black uppercase rounded-lg border border-transparent bg-slate-900 text-white hover:bg-black transition-all shadow-sm active:scale-95"
                            >
                                Apply All
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Spreadsheet Grid -->
            <div class="flex flex-col bg-white border border-gray-200 rounded-xl shadow-sm overflow-visible">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-center text-[10px] font-black text-gray-400 uppercase border-r border-gray-200 w-12">#</th>
                                <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase border-r border-gray-200 w-64">Syllabus Context</th>
                                <th scope="col" class="px-4 py-3 text-center text-[10px] font-black text-gray-400 uppercase border-r border-gray-200 w-24">Media</th>
                                <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase border-r border-gray-200">Question Content</th>
                                <th scope="col" class="px-4 py-3 text-left text-[10px] font-black text-gray-400 uppercase border-r border-gray-200 w-80">Options (A-D)</th>
                                <th scope="col" class="px-4 py-3 text-center text-[10px] font-black text-gray-400 uppercase w-16"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(q, idx) in questions" :key="idx" class="hover:bg-gray-50 transition-colors">
                                <!-- Index -->
                                <td class="px-4 py-3 text-xs font-bold text-gray-400 text-center border-r border-gray-200">{{ idx + 1 }}</td>
                                
                                <!-- Compact Context -->
                                <td class="px-4 py-3 space-y-2 border-r border-gray-200 bg-gray-50/20">
                                    <div class="relative group">
                                        <select v-model="q.subject_id" class="py-1.5 px-2 block w-full border-gray-200 rounded-md text-[11px] font-semibold focus:border-primary focus:ring-primary bg-white shadow-sm">
                                            <option value="">Subject Area</option>
                                            <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">{{ s.name }}</option>
                                        </select>
                                    </div>
                                    <div class="relative group">
                                        <select v-model="q.school_class_id" class="py-1.5 px-2 block w-full border-gray-200 rounded-md text-[11px] font-semibold focus:border-primary focus:ring-primary bg-white shadow-sm">
                                            <option value="">Academic Class</option>
                                            <option v-for="c in getAvailableClasses()" :key="c.id" :value="c.id">{{ c.name }}</option>
                                        </select>
                                    </div>
                                    <div class="relative group">
                                        <select v-model="q.topic_id" class="py-1.5 px-2 block w-full border-gray-200 rounded-md text-[11px] font-semibold focus:border-primary focus:ring-primary bg-white shadow-sm">
                                            <option value="">Topic Context</option>
                                            <option v-for="t in getFilteredTopics(q.subject_id, q.school_class_id)" :key="t.id" :value="t.id">{{ t.name }}</option>
                                        </select>
                                    </div>
                                </td>

                                <!-- Media -->
                                <td class="px-4 py-3 text-center border-r border-gray-200">
                                    <div v-if="q.imagePreview" class="relative group inline-block">
                                        <img :src="q.imagePreview" class="size-14 rounded-lg object-cover border-2 border-primary/20 shadow-sm" />
                                        <button @click="removeImage(idx)" class="absolute -top-2 -right-2 size-6 rounded-full bg-red-600 text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:scale-110 shadow-lg">
                                            <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                    <button v-else @click="triggerFileInput(idx)" class="size-14 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-200 text-gray-300 hover:text-primary hover:border-primary hover:bg-primary/5 transition-all group">
                                        <svg class="size-5 mb-1 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        <span class="text-[8px] font-black uppercase">Add Image</span>
                                        <input :id="'file-input-'+idx" type="file" class="hidden" accept="image/*" @change="(e) => handleImageUpload(idx, e)" />
                                    </button>
                                </td>

                                <!-- Content -->
                                <td class="px-4 py-3 border-r border-gray-200">
                                    <textarea v-model="q.content" rows="2" placeholder="Describe the question requirement..." class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm font-medium focus:border-primary focus:ring-primary bg-white resize-none shadow-sm"></textarea>
                                    <div class="mt-2 flex items-center gap-2">
                                        <div class="shrink-0 text-[10px] font-black text-gray-400 uppercase">Expl:</div>
                                        <input v-model="q.explanation" placeholder="Context for the correct answer (Optional)" class="py-1 px-2 block w-full border-gray-200 rounded-md text-[11px] focus:border-primary focus:ring-primary bg-gray-50/50" />
                                    </div>
                                </td>

                                <!-- Options -->
                                <td class="px-4 py-3 border-r border-gray-200 bg-gray-50/5">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div v-for="(opt, oIdx) in q.options" :key="oIdx" class="relative">
                                            <div class="flex items-center gap-2 p-1.5 rounded-lg border border-gray-100 bg-white shadow-sm transition-all focus-within:border-primary/50 focus-within:ring-1 focus-within:ring-primary/20">
                                                <input 
                                                    type="radio" 
                                                    :checked="opt.is_correct"
                                                    @change="() => q.options.forEach((o, i) => o.is_correct = i === oIdx)"
                                                    class="shrink-0 size-3.5 border-gray-300 rounded-full text-primary focus:ring-primary cursor-pointer"
                                                />
                                                <input v-model="opt.content" :placeholder="'Option '+String.fromCharCode(65+oIdx)" class="p-0 block w-full border-none rounded-md text-[11px] font-medium focus:ring-0 bg-transparent" />
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-center">
                                    <button @click="removeRow(idx)" class="size-8 flex items-center justify-center rounded-lg text-gray-300 hover:bg-red-50 hover:text-red-600 transition-all active:scale-90">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Floating Minimal Action Bar -->
            <div class="fixed bottom-10 left-1/2 -translate-x-1/2 z-50">
                <button 
                    @click="addRow"
                    class="py-3 px-8 inline-flex items-center gap-x-2 text-sm font-black uppercase tracking-widest rounded-full border border-transparent bg-slate-900 text-white hover:bg-black transition-all shadow-2xl active:scale-95"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    New Row
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
