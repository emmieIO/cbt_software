<script setup lang="ts">
import { Head, router, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { index, store, destroy } from '@/actions/App/Http/Controllers/Admin/TeachingLoadController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData, Subject, SchoolClass } from '@/types/academics';

interface Teacher {
    id: string;
    name: string;
    school_id: string | null;
}

interface Assignment {
    id: string;
    teacher: Teacher;
    subject: Subject;
    school_class: SchoolClass | null;
    prospective_class: { id: string; name: string } | null;
    academic_session: { name: string };
}

const props = defineProps<{
    assignments: PaginatedData<Assignment>;
    teachers: Teacher[];
    subjects: any[]; // Changed to any to handle topics
    classes: SchoolClass[];
    batches: { id: string; name: string }[];
    filters: {
        user_id?: string;
        school_class_id?: string;
        school_id?: string;
    };
    current_session: { name: string } | null;
}>();

const page = usePage();
const branches = computed(() => {
    const rawBranches = (page.props as any).branches || {};
    return Object.entries(rawBranches).map(([id, info]: [string, any]) => ({
        id,
        name: info.name
    }));
});

const isModalOpen = ref(false);
const form = useForm({
    user_id: '',
    subject_id: '',
    school_class_id: '',
    prospective_class_id: '',
});

const filteredClasses = computed(() => {
    if (!form.subject_id) return [];

    const selectedSubject = props.subjects.find((s) => s.id === form.subject_id);
    if (!selectedSubject || !selectedSubject.topics) return [];

    const eligibleClassIds = new Set(selectedSubject.topics.map((t: any) => t.school_class_id));
    return props.classes.filter((c) => eligibleClassIds.has(c.id));
});

watch(
    () => form.subject_id,
    (newVal) => {
        // Only clear class if subject is removed, as regular classes MUST have a subject
        if (!newVal) {
            form.school_class_id = '';
        }
    },
);

// Clear opposite field when one is selected
watch(
    () => form.school_class_id,
    (val) => {
        if (val) {
            form.prospective_class_id = '';
        }
    },
);

watch(
    () => form.prospective_class_id,
    (val) => {
        if (val) {
            form.school_class_id = '';
        }
    },
);

const submit = () => {
    form.post(store().url, {
        onSuccess: () => {
            isModalOpen.value = false;
            form.reset();
        },
    });
};

// Filters
const filterForm = useForm({
    user_id: props.filters.user_id || '',
    school_class_id: props.filters.school_class_id || '',
    school_id: props.filters.school_id || '',
});

const applyFilters = () => {
    router.get(index().url, filterForm.data(), { preserveState: true });
};

const isDeleteModalOpen = ref(false);
const assignmentToDelete = ref<Assignment | null>(null);

const confirmDelete = (assignment: Assignment) => {
    assignmentToDelete.value = assignment;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (assignmentToDelete.value) {
        router.delete(destroy(assignmentToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                assignmentToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Teaching Loads" />

        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Teaching Loads</h2>
                    <p class="mt-1 text-sm text-gray-500">Assign subjects and classes to examiners for the active session.</p>
                </div>
                <button
                    @click="isModalOpen = true"
                    class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary/90 focus:bg-primary/90 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Assign Load
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-1 gap-4">
                    <div class="flex-1">
                        <CustomSelect
                            v-model="filterForm.school_id"
                            label="Filter by Branch"
                            :options="branches"
                            placeholder="All Branches"
                            size="sm"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="flex-1">
                        <label class="mb-2 block text-sm font-medium text-gray-800">Filter by Examiner</label>
                        <select
                            v-model="filterForm.user_id"
                            @change="applyFilters"
                            class="block w-full rounded-lg border-gray-200 px-4 py-2 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                        >
                            <option value="">All Personnel</option>
                            <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="mb-2 block text-sm font-medium text-gray-800">Filter by Class</label>
                        <select
                            v-model="filterForm.school_class_id"
                            @change="applyFilters"
                            class="block w-full rounded-lg border-gray-200 px-4 py-2 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                        >
                            <option value="">All Classes</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="rounded-lg bg-gray-50 px-4 py-3">
                    <span class="text-xs font-medium text-gray-500">Active Session:</span>
                    <span class="ml-2 text-xs font-semibold text-primary">{{ current_session?.name || 'Not Set' }}</span>
                </div>
            </div>

            <div class="flex flex-col">
              <div class="-m-1.5 overflow-x-auto">
                <div class="inline-block min-w-full p-1.5 align-middle">
                  <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">School Branch</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Examiner</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">Target Audience</th>
                                <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="assignment in assignments.data" :key="assignment.id" class="transition-all hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="assignment.teacher.school_id" class="inline-flex items-center rounded-full bg-primary/10 px-2 py-1 text-xs font-medium text-primary">
                                        {{ (page.props as any).branches[assignment.teacher.school_id]?.name || 'Unknown' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <h4 class="text-sm font-semibold text-gray-800">{{ assignment.teacher.name }}</h4>
                                        <p class="text-xs text-gray-500">
                                            ID: {{ assignment.teacher.id.substring(0, 8) }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                    <span
                                        v-if="assignment.subject"
                                        class="inline-flex items-center gap-x-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-800"
                                    >
                                        {{ assignment.subject.name }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-x-1.5 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-500"
                                    >
                                        Lead Examiner
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div v-if="assignment.school_class">
                                        <span class="text-sm font-medium text-gray-800">{{ assignment.school_class.name }}</span>
                                        <p class="text-xs text-gray-500">Regular Class</p>
                                    </div>
                                    <div v-else-if="assignment.prospective_class">
                                        <span class="text-sm font-medium text-gray-800">{{ assignment.prospective_class.name }}</span>
                                        <p class="text-xs text-primary font-medium">Entrance Batch</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-end whitespace-nowrap">
                                    <button
                                        @click="confirmDelete(assignment)"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-red-50 hover:text-white focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="assignments.data.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-400">No teaching assignments mapped.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                        <p class="text-xs text-gray-500">
                            Page {{ assignments.current_page }} • Results {{ assignments.from }}-{{ assignments.to }} of {{ assignments.total }}
                        </p>
                        <div class="flex gap-x-1">
                            <Link
                                v-for="link in assignments.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg px-3 py-2 text-sm text-gray-800 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                :class="[
                                    link.active ? 'bg-primary text-white hover:bg-primary/90' : '',
                                    !link.url && 'pointer-events-none opacity-50',
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

            <!-- Create Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div @click="isModalOpen = false" class="absolute inset-0 bg-gray-900/50 transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-lg sm:p-10">
                    <h3 class="mb-6 text-xl font-semibold text-gray-900">Assign Teaching Load</h3>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-800">Select Personnel</label>
                            <select
                                v-model="form.user_id"
                                required
                                class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                            >
                                <option value="" disabled>Choose Personnel</option>
                                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                    {{ teacher.name }} — {{ (page.props as any).branches[teacher.school_id || '']?.name || 'Global' }}
                                </option>
                            </select>
                            <div v-if="form.errors.user_id" class="mt-2 text-xs text-red-500">{{ form.errors.user_id }}</div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-800">Subject Area</label>
                            <select
                                v-model="form.subject_id"
                                class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                            >
                                <option value="">None (For Lead Examiners)</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                            </select>
                            <div v-if="form.errors.subject_id" class="mt-2 text-xs text-red-500">{{ form.errors.subject_id }}</div>
                        </div>

                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-800"
                                    >Assign to Regular Class</label
                                >
                                <select
                                    v-model="form.school_class_id"
                                    :disabled="!form.subject_id || form.prospective_class_id !== ''"
                                    class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                >
                                    <option value="">None (Select Batch Instead)</option>
                                    <option v-for="cls in filteredClasses" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                            </div>

                            <div class="relative py-4">
                                <div class="absolute inset-0 flex items-center">
                                    <span class="w-full border-t border-gray-200"></span>
                                </div>
                                <div class="relative flex justify-center text-xs font-medium uppercase">
                                    <span class="bg-white px-2 text-gray-400">OR</span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-800"
                                    >Assign to Entrance Batch</label
                                >
                                <select
                                    v-model="form.prospective_class_id"
                                    :disabled="form.school_class_id !== ''"
                                    class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                >
                                    <option value="">None (Select Class Instead)</option>
                                    <option v-for="batch in batches" :key="batch.id" :value="batch.id">{{ batch.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.errors.school_class_id" class="mt-2 text-xs text-red-500">{{ form.errors.school_class_id }}</div>

                        <div class="flex items-center justify-end gap-x-2 border-t border-gray-200 pt-4">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary/90 focus:bg-primary/90 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                            >
                                Confirm Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Remove Teaching Load?"
            :message="`Are you sure you want to remove the assignment for ${assignmentToDelete?.teacher.name}? They will no longer be able to manage exams for this class.`"
            confirm-label="Remove Load"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
