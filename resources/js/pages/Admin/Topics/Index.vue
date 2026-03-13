<script setup lang="ts">
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store, update, destroy, index as topicsIndex } from '@/actions/App/Http/Controllers/Admin/TopicController';
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
    filters: {
        subject_id?: string;
        school_class_id?: string;
    };
}>();

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingTopic = ref<Topic | null>(null);

const form = useForm({
    subject_id: props.filters.subject_id || '',
    school_class_id: props.filters.school_class_id || '',
    name: '',
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingTopic.value = null;

    form.subject_id = props.filters.subject_id || '';
    form.school_class_id = props.filters.school_class_id || '';
    form.name = '';
    form.description = '';

    isModalOpen.value = true;
};

const openEditModal = (topic: Topic) => {
    isEditing.value = true;
    editingTopic.value = topic;

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

// Filtering logic
const filterForm = useForm({
    subject_id: props.filters.subject_id || '',
    school_class_id: props.filters.school_class_id || '',
});

const applyFilters = () => {
    router.get(
        topicsIndex().url,
        {
            subject_id: filterForm.subject_id,
            school_class_id: filterForm.school_class_id,
        },
        { preserveState: true },
    );
};

const clearFilters = () => {
    filterForm.subject_id = '';
    filterForm.school_class_id = '';
    applyFilters();
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
        <Head title="Curriculum Management" />

        <div class="space-y-6 sm:space-y-10">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="hover:text-primary transition-colors">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                <span class="text-gray-800">Curriculum Topics</span>
            </nav>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Curriculum Topics</h1>
                    <p class="text-sm text-gray-500 mt-1">Manage specific topics for each subject and class level.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Topic
                </button>
            </div>

            <!-- Filters -->
            <div class="p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-2 text-gray-800">Filter by Subject</label>
                        <select
                            v-model="filterForm.subject_id"
                            @change="applyFilters"
                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <option value="">All Subjects</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-2 text-gray-800">Filter by Class</label>
                        <select
                            v-model="filterForm.school_class_id"
                            @change="applyFilters"
                            class="py-2 px-3 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                        >
                            <option value="">All Classes</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                    <button
                        v-if="filterForm.subject_id || filterForm.school_class_id"
                        @click="clearFilters"
                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-transparent text-red-600 hover:bg-red-50 hover:text-red-700 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                    >
                        Clear Filters
                    </button>
                </div>
            </div>

            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="p-1.5 min-w-full inline-block align-middle">
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Topic Name</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Subject</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Class</th>
                                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Questions</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="topic in topics.data" :key="topic.id" class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <span class="block text-sm font-semibold text-gray-800">{{ topic.name }}</span>
                                            <span class="block text-xs text-gray-500 mt-0.5 line-clamp-1">
                                                {{ topic.description || 'No description provided.' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-x-1.5 py-1.5 px-3 rounded-md text-xs font-medium bg-primary/10 text-primary uppercase">
                                                {{ topic.subject.name }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-gray-800">{{ topic.school_class.name }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-sm font-medium text-gray-500">{{ topic.questions_count }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex justify-end items-center gap-x-2">
                                                <button @click="openEditModal(topic)" class="text-gray-500 hover:text-primary transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                </button>
                                                <button @click="confirmDelete(topic)" class="text-gray-500 hover:text-red-500 transition-colors focus:outline-none">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="topics.data.length === 0">
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 text-sm">
                                            No topics found for the selected filters.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination Footer -->
                            <div class="px-6 py-4 grid gap-3 md:flex md:justify-between md:items-center border-t border-gray-200">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Showing <span class="font-semibold text-gray-800">{{ topics.from }}</span> to <span class="font-semibold text-gray-800">{{ topics.to }}</span> of <span class="font-semibold text-gray-800">{{ topics.total }}</span> results
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in topics.links"
                                        :key="link.label"
                                        :href="link.url || '#'"
                                        class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:bg-gray-50"
                                        :class="[
                                            link.active ? 'bg-primary text-white border-primary hover:bg-primary-hover hover:text-white' : '',
                                            !link.url ? 'pointer-events-none opacity-50' : '',
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

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[80] overflow-y-auto overflow-x-hidden flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>
                <div class="relative w-full max-w-lg bg-white rounded-xl shadow-lg border border-gray-200">
                    <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200">
                        <h3 class="font-semibold text-gray-800">{{ isEditing ? 'Edit Topic' : 'Add Topic to Curriculum' }}</h3>
                        <button @click="closeModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-lg border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none">
                            <span class="sr-only">Close</span>
                            <svg class="flex-shrink-0 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-4 overflow-y-auto max-h-[calc(100vh-150px)]">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-800">Subject</label>
                                    <select
                                        v-model="form.subject_id"
                                        required
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                    >
                                        <option value="" disabled>Select Subject</option>
                                        <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                    </select>
                                    <div v-if="form.errors.subject_id" class="text-sm text-red-600 mt-2">{{ form.errors.subject_id }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-2 text-gray-800">Target Class</label>
                                    <select
                                        v-model="form.school_class_id"
                                        required
                                        class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                    >
                                        <option value="" disabled>Select Class</option>
                                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                    </select>
                                    <div v-if="form.errors.school_class_id" class="text-sm text-red-600 mt-2">
                                        {{ form.errors.school_class_id }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Topic Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Enter Topic Title (e.g. Linear Equations)"
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                />
                                <div v-if="form.errors.name" class="text-sm text-red-600 mt-2">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium mb-2 text-gray-800">Description (Optional)</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Details about this curriculum topic..."
                                    class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none"
                                ></textarea>
                                <div v-if="form.errors.description" class="text-sm text-red-600 mt-2">{{ form.errors.description }}</div>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-x-2">
                            <button
                                type="button"
                                @click="closeModal"
                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary-hover disabled:opacity-50 disabled:pointer-events-none focus:outline-none"
                            >
                                {{ isEditing ? 'Update' : 'Add to Bank' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Remove Topic?"
            :message="`Are you sure you want to remove ${topicToDelete?.name} from the curriculum?`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
