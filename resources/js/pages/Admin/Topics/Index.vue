<script setup lang="ts">
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { ref, watch, computed } from 'vue';
import { store, update, destroy, index } from '@/actions/App/Http/Controllers/Admin/TopicController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { Subject, SchoolClass, PaginatedData } from '@/types/academics';

interface Topic {
    id: string;
    name: string;
    description: string | null;
    subject_id: string;
    school_class_id: string;
    subject: Subject;
    school_class: SchoolClass;
    questions_count: number;
}

const props = defineProps<{
    topics: PaginatedData<Topic>;
    subjects: Subject[];
    classes: SchoolClass[];
    levels: { value: string; label: string }[];
    filters: {
        subject_id?: string;
        school_class_id?: string;
        level?: string;
        search?: string;
    };
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingTopic = ref<Topic | null>(null);

const form = useForm({
    level: 'primary',
    subject_id: '',
    school_class_id: '',
    name: '',
    description: '',
});

// Dynamic filtering for modal dropdowns
const modalSubjects = computed(() => {
    return props.subjects.filter(s => s.level === form.level);
});

const modalClasses = computed(() => {
    return props.classes.filter(c => c.level === form.level);
});

// Reset subject/class when level changes in modal
watch(() => form.level, () => {
    form.subject_id = '';
    form.school_class_id = '';
});

// Filtering logic for the main table
const search = ref(props.filters.search || '');
const levelFilter = ref(props.filters.level || '');
const subjectFilter = ref(props.filters.subject_id || '');
const classFilter = ref(props.filters.school_class_id || '');

const applyFilters = debounce(() => {
    router.get(index().url, {
        search: search.value,
        level: levelFilter.value,
        subject_id: subjectFilter.value,
        school_class_id: classFilter.value,
    }, { preserveState: true, replace: true });
}, 300);

watch([search, levelFilter, subjectFilter, classFilter], () => applyFilters());

const clearFilters = () => {
    search.value = '';
    levelFilter.value = '';
    subjectFilter.value = '';
    classFilter.value = '';
};

const openCreateModal = () => {
    isEditing.value = false;
    editingTopic.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (topic: Topic) => {
    isEditing.value = true;
    editingTopic.value = topic;
    form.level = topic.subject.level;
    form.subject_id = topic.subject_id;
    form.school_class_id = topic.school_class_id;
    form.name = topic.name;
    form.description = topic.description || '';
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingTopic.value) {
        form.put(update(editingTopic.value.id).url, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(store().url, {
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const getLevelClasses = (level: string) => {
    switch (level) {
        case 'nursery': return 'bg-pink-100 text-pink-800';
        case 'secondary': return 'bg-indigo-100 text-indigo-800';
        default: return 'bg-orange-100 text-orange-800';
    }
};

const isDeleteModalOpen = ref(false);
const topicToDelete = ref<Topic | null>(null);

const confirmDelete = (topic: Topic) => {
    topicToDelete.value = topic;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (topicToDelete.value) {
        router.delete(destroy(topicToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                topicToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Curriculum Topics" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Curriculum Units</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Curriculum Topics</h1>
                    <p class="text-sm text-gray-500 mt-1">
                        Syllabus Units • {{ topics.total }} Definitions
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Add Topic
                </button>
            </div>

            <!-- Topics Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <!-- Filters Header -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-b border-gray-200">
                                <div class="relative flex-1 max-w-md">
                                    <div class="absolute inset-y-0 start-0 flex items-center pointer-events-none ps-3">
                                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                    </div>
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search topics..."
                                        class="py-2 px-3 ps-9 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    />
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="w-36">
                                        <select 
                                            v-model="levelFilter"
                                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                        >
                                            <option value="">All Levels</option>
                                            <option v-for="l in levels" :key="l.value" :value="l.value">{{ l.label }}</option>
                                        </select>
                                    </div>
                                    <div class="w-48">
                                        <select 
                                            v-model="subjectFilter"
                                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary"
                                        >
                                            <option value="">All Subjects</option>
                                            <option v-for="s in subjects" :key="s.id" :value="s.id">{{ s.name }} ({{ s.level }})</option>
                                        </select>
                                    </div>
                                    <button
                                        v-if="search || levelFilter || subjectFilter || classFilter"
                                        @click="clearFilters"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-red-600 hover:bg-red-50 focus:outline-none"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Topic Details</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Academic Mapping</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Assets</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="topic in topics.data" :key="topic.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="block text-sm font-semibold text-gray-800">{{ topic.name }}</span>
                                            <span class="block text-xs text-gray-500 mt-0.5 line-clamp-1 max-w-xs">{{ topic.description || 'No description provided' }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center py-1 px-2 rounded-md text-[10px] font-bold bg-primary/10 text-primary uppercase">
                                                    {{ topic.subject.name }}
                                                </span>
                                                <span 
                                                    class="inline-flex items-center py-1 px-2 rounded-md text-[10px] font-bold uppercase"
                                                    :class="getLevelClasses(topic.subject.level)"
                                                >
                                                    {{ topic.school_class.name }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-xs font-medium text-gray-600">{{ topic.questions_count }} Questions</span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <button @click="openEditModal(topic)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(topic)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="topics.data.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <p class="text-sm">No curriculum topics registered yet</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div v-if="topics.total > topics.per_page" class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Showing <span class="font-semibold text-gray-800">{{ topics.from }}</span> to <span class="font-semibold text-gray-800">{{ topics.to }}</span> of <span class="font-semibold text-gray-800">{{ topics.total }}</span>
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in topics.links"
                                        :key="link.label"
                                        :href="link.url || '#'"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                                        :class="[
                                            link.active ? 'bg-gray-100' : '',
                                            !link.url && 'opacity-50 pointer-events-none',
                                        ]"
                                    >
                                        <span v-html="link.label" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
            <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
            <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200">
                <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-800 uppercase tracking-tight text-sm">{{ isEditing ? 'Update Topic Details' : 'Register New Knowledge Unit' }}</h3>
                    <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50">
                        <span class="sr-only">Close</span>
                        <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <form @submit.prevent="submit" class="p-6 overflow-y-auto max-h-[calc(100vh-150px)]">
                    <div class="space-y-6">
                        <!-- Step 1: Academic Level Selection -->
                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Academic Tier</label>
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="level in levels"
                                    :key="level.value"
                                    type="button"
                                    @click="form.level = level.value"
                                    class="py-3 px-2 text-center text-[10px] font-bold uppercase rounded-lg border-2 transition-all shadow-sm"
                                    :class="form.level === level.value ? 'bg-slate-900 border-slate-900 text-white' : 'bg-white border-gray-100 text-gray-400 hover:border-gray-200'"
                                >
                                    {{ level.label }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Subject Area</label>
                                <select
                                    v-model="form.subject_id"
                                    required
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                >
                                    <option value="" disabled>Select Subject</option>
                                    <option v-for="subject in modalSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                                <p v-if="form.errors.subject_id" class="text-sm text-red-600 mt-2">{{ form.errors.subject_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Class Level</label>
                                <select
                                    v-model="form.school_class_id"
                                    required
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                >
                                    <option value="" disabled>Select Class</option>
                                    <option v-for="cls in modalClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                                <p v-if="form.errors.school_class_id" class="text-sm text-red-600 mt-2">{{ form.errors.school_class_id }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800 uppercase tracking-widest text-[10px]">Topic Nomenclature</label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                placeholder="e.g. CELL BIOLOGY"
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm font-semibold text-gray-800 focus:border-primary focus:ring-primary disabled:opacity-50"
                            />
                            <p v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-2 text-gray-800">Detailed Description</label>
                            <textarea
                                v-model="form.description"
                                rows="3"
                                placeholder="Provide a high-level summary..."
                                class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                            ></textarea>
                            <p v-if="form.errors.description" class="text-sm text-red-600 mt-2">{{ form.errors.description }}</p>
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end gap-x-2">
                        <button
                            type="button"
                            @click="closeModal"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none shadow-md shadow-primary/20"
                        >
                            {{ isEditing ? 'Update Topic' : 'Confirm Registration' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Purge Knowledge Unit?"
            :message="`Are you sure you want to permanently remove ${topicToDelete?.name}?`"
            confirm-label="Purge Record"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
