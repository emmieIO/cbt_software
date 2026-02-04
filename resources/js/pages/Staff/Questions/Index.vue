<script setup lang="ts">
import { Head, Link, usePage, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import { create, index as indexAction, importMethod, exportMethod, downloadTemplate } from '@/actions/App/Http/Controllers/Staff/StaffController';
import StaffLayout from '@/layouts/StaffLayout.vue';

const props = defineProps<{
    questions: {
        data: any[];
        links: any[];
    };
    subjects: any[];
    classes: any[];
    difficulties: any[];
    filters: {
        search?: string;
        subject_id?: string;
        school_class_id?: string;
        difficulty?: string;
    };
}>();

const page = usePage();
const flash = computed(() => page.props.flash as any);

const importForm = useForm({
    file: null as File | null,
});

const handleImport = () => {
    importForm.post(importMethod().url, {
        onSuccess: () => importForm.reset(),
    });
};

const filterForm = ref({
    search: props.filters.search || '',
    subject_id: props.filters.subject_id || '',
    school_class_id: props.filters.school_class_id || '',
    difficulty: props.filters.difficulty || '',
});

watch(
    filterForm,
    debounce((value) => {
        router.get(indexAction().url, value, {
            preserveState: true,
            replace: true,
        });
    }, 300),
    { deep: true }
);

const clearFilters = () => {
    filterForm.value = {
        search: '',
        subject_id: '',
        school_class_id: '',
        difficulty: '',
    };
};
</script>

<template>
    <StaffLayout>
        <Head title="Question Repository" />

        <div class="p-8">
            <!-- Success Alert -->
            <div 
                v-if="flash.success" 
                class="mb-8 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center justify-between shadow-sm"
            >
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    <span class="font-bold">{{ flash.success }}</span>
                </div>
                <button @click="flash.success = null" class="text-green-500 hover:text-green-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="w-full space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900">Question Bank</h2>
                        <p class="text-slate-500 mt-1">Review and manage the existing question repository.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2 border-r border-slate-200 pr-4">
                            <a 
                                :href="downloadTemplate().url"
                                class="flex items-center px-4 py-3 rounded-xl font-bold border-2 border-slate-200 text-slate-600 hover:border-primary hover:text-primary transition-all"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                Template
                            </a>
                            <a 
                                :href="exportMethod().url"
                                class="flex items-center px-4 py-3 rounded-xl font-bold border-2 border-slate-200 text-slate-600 hover:border-primary hover:text-primary transition-all"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                Export All
                            </a>
                        </div>

                        <form @submit.prevent="handleImport" class="flex items-center gap-2">
                            <label 
                                class="flex items-center px-4 py-3 rounded-xl font-bold border-2 border-slate-200 text-slate-600 hover:border-primary hover:text-primary transition-all cursor-pointer"
                            >
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                                {{ importForm.file ? importForm.file.name : 'Choose Import File' }}
                                <input 
                                    type="file" 
                                    class="hidden" 
                                    accept=".csv,.xlsx"
                                    @input="importForm.file = $event.target.files[0]"
                                />
                            </label>
                            <button 
                                v-if="importForm.file"
                                type="submit"
                                :disabled="importForm.processing"
                                class="bg-primary text-white px-4 py-3 rounded-xl font-bold hover:bg-primary/90 transition-all"
                            >
                                Import
                            </button>
                        </form>

                        <Link 
                            :href="create().url"
                            class="flex items-center px-6 py-3 rounded-xl font-bold bg-primary text-white shadow-lg hover:bg-primary/90 hover:scale-105 active:scale-95 transition-all"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                            Add New Question
                        </Link>
                    </div>
                </div>

                <!-- Repository Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-8 border-b border-slate-100">
                        <div class="flex items-center justify-between mb-8">
                            <div>
                                <h3 class="text-xl font-bold text-slate-800">Available Questions</h3>
                                <p class="text-sm text-slate-500 mt-1">Review and manage the existing question bank.</p>
                            </div>
                            <div class="flex gap-4">
                                <span class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-bold">
                                    Total Questions: {{ questions.data.length }}
                                </span>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                </span>
                                <input
                                    v-model="filterForm.search"
                                    type="text"
                                    placeholder="Search questions..."
                                    class="block w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-primary focus:ring-primary transition-all"
                                />
                            </div>

                            <select
                                v-model="filterForm.subject_id"
                                class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-primary focus:ring-primary transition-all"
                            >
                                <option value="">All Subjects</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                    {{ subject.name }}
                                </option>
                            </select>

                            <select
                                v-model="filterForm.school_class_id"
                                class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-primary focus:ring-primary transition-all"
                            >
                                <option value="">All Classes</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                                    {{ cls.name }}
                                </option>
                            </select>

                            <select
                                v-model="filterForm.difficulty"
                                class="block w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:border-primary focus:ring-primary transition-all"
                            >
                                <option value="">All Difficulties</option>
                                <option v-for="diff in difficulties" :key="diff.value" :value="diff.value">
                                    {{ diff.label }}
                                </option>
                            </select>
                        </div>

                        <div v-if="filterForm.search || filterForm.subject_id || filterForm.school_class_id || filterForm.difficulty" class="mt-4 flex justify-end">
                            <button 
                                @click="clearFilters"
                                class="text-xs font-bold text-slate-400 hover:text-primary transition-colors flex items-center"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                Clear all filters
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Question</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Subject / Topic</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Class</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Difficulty</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="question in questions.data" :key="question.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-slate-900 line-clamp-2">{{ question.content }}</p>
                                        <p class="text-xs text-slate-400 mt-1">Options: {{ question.options.length }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700">{{ question.topic.subject.name }}</span>
                                            <span class="text-xs text-slate-500">{{ question.topic.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ question.school_class.name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span 
                                            :class="[
                                                'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                                question.difficulty === 'easy' ? 'bg-green-100 text-green-700' :
                                                question.difficulty === 'medium' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'
                                            ]"
                                        >
                                            {{ question.difficulty }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button class="text-primary hover:text-primary/80 font-bold text-sm">Edit</button>
                                        <button class="text-slate-400 hover:text-red-500 font-bold text-sm">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="questions.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic text-lg">
                                        No questions found in the repository yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8 flex justify-center gap-2">
                        <Link
                            v-for="link in questions.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                            :class="[
                                link.active ? 'bg-primary text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>