<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue';
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

const form = useForm('post', store().url, {
    subject_id: props.filters.subject_id || '',
    school_class_id: props.filters.school_class_id || '',
    name: '',
    description: '',
});

const openCreateModal = () => {
    isEditing.value = false;
    editingTopic.value = null;
    form.setData({
        subject_id: props.filters.subject_id || '',
        school_class_id: props.filters.school_class_id || '',
        name: '',
        description: '',
    });
    isModalOpen.value = true;
};

const openEditModal = (topic: Topic) => {
    isEditing.value = true;
    editingTopic.value = topic;
    form.setData({
        subject_id: topic.subject_id,
        school_class_id: topic.school_class_id,
        name: topic.name,
        description: topic.description || '',
    });
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingTopic.value) {
        form.submit({
            method: 'put',
            url: update(editingTopic.value.id).url,
            onSuccess: () => closeModal(),
        });
    } else {
        form.submit({
            onSuccess: () => closeModal(),
        });
    }
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

// Filtering logic for the list - normal useForm is fine here as it's a GET search
import { useForm as useInertiaForm } from '@inertiajs/vue3';
const filterForm = useInertiaForm({
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

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Curriculum Topics</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Manage specific topics for each subject and class level.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex h-12 items-center gap-3 rounded-xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Create Topic
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-4 rounded-xl border border-slate-100 bg-white p-6 shadow-sm">
                <div class="flex flex-1 gap-4">
                    <div class="flex-1">
                        <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Filter by Subject</label>
                        <select
                            v-model="filterForm.subject_id"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                        >
                            <option value="">All Subjects</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Filter by Class</label>
                        <select
                            v-model="filterForm.school_class_id"
                            @change="applyFilters"
                            class="w-full rounded-xl border-slate-100 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                        >
                            <option value="">All Classes</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                </div>
                <button
                    v-if="filterForm.subject_id || filterForm.school_class_id"
                    @click="clearFilters"
                    class="mt-6 px-4 py-3 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:text-red-500"
                >
                    Clear Filters
                </button>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black tracking-widest text-slate-400 uppercase">Topic Name</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">Subject</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">Class</th>
                                <th class="px-6 py-5 text-[10px] font-black tracking-widest whitespace-nowrap text-slate-400 uppercase">Questions</th>
                                <th class="px-8 py-5 text-right text-[10px] font-black tracking-widest text-slate-400 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="topic in topics.data" :key="topic.id" class="group transition-all hover:bg-slate-50/80">
                                <td class="px-8 py-6">
                                    <h4 class="text-sm font-black text-slate-800">{{ topic.name }}</h4>
                                    <p class="mt-1 line-clamp-1 text-xs font-medium text-slate-400">
                                        {{ topic.description || 'No description.' }}
                                    </p>
                                </td>
                                <td class="px-6 py-6">
                                    <span
                                        class="inline-flex items-center rounded-xl bg-primary/5 px-2.5 py-1 text-[10px] font-black text-primary uppercase"
                                    >
                                        {{ topic.subject.name }}
                                    </span>
                                </td>
                                <td class="px-6 py-6 whitespace-nowrap">
                                    <span class="text-xs font-bold text-slate-600">{{ topic.school_class.name }}</span>
                                </td>
                                <td class="px-6 py-6 text-center">
                                    <span class="text-xs font-black text-slate-400">{{ topic.questions_count }}</span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            @click="openEditModal(topic)"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-primary hover:text-white"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2.5"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmDelete(topic)"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
                                        >
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2.5"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="topics.data.length === 0">
                                <td colspan="5" class="px-8 py-12 text-center text-sm font-bold text-slate-400">
                                    No topics found for the selected filters.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-slate-50 px-8 py-6">
                    <p class="text-xs font-bold text-slate-400">Showing {{ topics.from }} to {{ topics.to }} of {{ topics.total }} results.</p>
                    <div class="flex gap-2">
                        <Link
                            v-for="link in topics.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            class="flex h-9 items-center justify-center rounded-xl border px-4 text-xs font-black transition-all"
                            :class="[
                                link.active
                                    ? 'border-primary bg-primary text-white shadow-lg shadow-primary/20'
                                    : 'border-slate-100 bg-white text-slate-400 hover:bg-slate-50',
                                !link.url ? 'pointer-events-none opacity-50' : '',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="closeModal" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-lg overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">{{ isEditing ? 'Edit Topic' : 'Add Topic to Curriculum' }}</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Subject</label>
                                <select
                                    v-model="form.subject_id"
                                    @change="form.validate('subject_id')"
                                    required
                                    :class="{'border-red-500': form.invalid('subject_id')}"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="" disabled>Select Subject</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                                <div v-if="form.errors.subject_id" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.subject_id }}</div>
                            </div>
                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Target Class</label>
                                <select
                                    v-model="form.school_class_id"
                                    @change="form.validate('school_class_id')"
                                    required
                                    :class="{'border-red-500': form.invalid('school_class_id')}"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="" disabled>Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                                <div v-if="form.errors.school_class_id" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.school_class_id }}</div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Topic Name</label>
                            <input
                                v-model="form.name"
                                @change="form.validate('name')"
                                type="text"
                                required
                                placeholder="e.g. Linear Equations"
                                :class="{'border-red-500': form.invalid('name')}"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                            <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                >Description (Optional)</label
                            >
                            <textarea
                                v-model="form.description"
                                @change="form.validate('description')"
                                rows="3"
                                placeholder="Details about this curriculum topic..."
                                :class="{'border-red-500': form.invalid('description')}"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            ></textarea>
                            <div v-if="form.errors.description" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.description }}</div>
                        </div>

                        <div class="flex gap-3 pt-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="flex-1 rounded-xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
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
