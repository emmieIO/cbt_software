<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    store as storeAction,
    update as updateAction,
    setCurrent as setCurrentAction,
    destroy as destroyAction,
} from '@/actions/App/Http/Controllers/Admin/AcademicSessionController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import DatePicker from '@/components/Form/DatePicker.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface AcademicSession {
    id: string;
    name: string;
    term: string;
    is_current: boolean;
    start_date: string;
    end_date: string;
}

const props = defineProps<{
    sessions: AcademicSession[];
    terms: Array<{ value: string; label: string }>;
}>();

const getTermLabel = (value: string) => {
    return props.terms.find((t) => t.value === value)?.label || value;
};

const formatDate = (dateString: string) => {
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(new Date(dateString));
};

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref<string | null>(null);

const form = useForm({
    name: '',
    term: 'first',
    start_date: '',
    end_date: '',
    is_current: false,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    isModalOpen.value = true;

    setTimeout(() => {
        // @ts-expect-error: HSStaticMethods is globally defined by Preline
        if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
    }, 100);
};

const openEditModal = (session: AcademicSession) => {
    isEditing.value = true;
    editingId.value = session.id;

    form.name = session.name;
    form.term = session.term;
    form.start_date = session.start_date;
    form.end_date = session.end_date;
    form.is_current = session.is_current;

    isModalOpen.value = true;

    setTimeout(() => {
        // @ts-expect-error: HSStaticMethods is globally defined by Preline
        if (window.HSStaticMethods) window.HSStaticMethods.autoInit();
    }, 100);
};


const submit = () => {
    if (isEditing.value && editingId.value) {
        form.put(updateAction(editingId.value).url, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.post(storeAction().url, {
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    }
};

const setAsCurrent = (session: AcademicSession) => {
    router.patch(setCurrentAction(session.id).url);
};

const isDeleteModalOpen = ref(false);
const sessionToDelete = ref<AcademicSession | null>(null);

const confirmDelete = (session: AcademicSession) => {
    sessionToDelete.value = session;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (sessionToDelete.value) {
        router.delete(destroyAction(sessionToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                sessionToDelete.value = null;
            },
        });
    }
};
</script>

<template>
    <AdminLayout>
        <Head title="Academic Session Management" />

        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Academic Sessions</h2>
                    <p class="mt-1 text-sm text-gray-500">Define academic years and set the current active session.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-3 text-sm font-medium text-white hover:bg-primary/90 focus:bg-primary/90 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Session
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    v-for="session in sessions"
                    :key="session.id"
                    class="group relative overflow-hidden rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition-all hover:shadow-md"
                    :class="{ 'border-primary ring-1 ring-primary': session.is_current }"
                >
                    <div class="relative z-10">
                        <div class="mb-6 flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h3 class="text-xl font-semibold text-gray-800">{{ session.name }}</h3>
                                    <span class="rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600">
                                        {{ getTermLabel(session.term) }}
                                    </span>
                                    <span
                                        v-if="session.is_current"
                                        class="inline-flex items-center gap-x-1.5 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary"
                                    >
                                        <span class="relative flex h-2 w-2">
                                          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75"></span>
                                          <span class="relative inline-flex h-2 w-2 rounded-full bg-primary"></span>
                                        </span>
                                        Current Session
                                    </span>
                                </div>
                                <p class="mt-1 text-xs font-medium text-gray-400">
                                    {{ formatDate(session.start_date) }} — {{ formatDate(session.end_date) }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    v-if="!session.is_current"
                                    @click="setAsCurrent(session)"
                                    class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    Set Current
                                </button>
                                <button
                                    @click="openEditModal(session)"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50 focus:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                        />
                                    </svg>
                                </button>
                                <button
                                    v-if="!session.is_current"
                                    @click="confirmDelete(session)"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-red-50 hover:text-white focus:outline-none disabled:pointer-events-none disabled:opacity-50"
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
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg bg-gray-50 p-4">
                                <span class="mb-1 block text-xs font-medium text-gray-400">Start Date</span>
                                <span class="text-sm font-semibold text-gray-700">{{ formatDate(session.start_date) }}</span>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4">
                                <span class="mb-1 block text-xs font-medium text-gray-400">End Date</span>
                                <span class="text-sm font-semibold text-gray-700">{{ formatDate(session.end_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div @click="isModalOpen = false" class="absolute inset-0 bg-gray-900/50 transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-lg">
                    <h3 class="mb-6 text-xl font-semibold text-gray-900">{{ isEditing ? 'Edit Session' : 'Define New Session' }}</h3>

                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-800">Session Name</label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    placeholder="Enter Session Name (e.g. 2026/2027)"
                                    class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                />
                                <div v-if="form.errors.name" class="mt-2 text-xs text-red-500">{{ form.errors.name }}</div>
                            </div>
                            
                            <CustomSelect
                                v-model="form.term"
                                label="Academic Term"
                                :options="terms.map(t => ({ id: t.value, name: t.label }))"
                                placeholder="Select Term"
                                :error="form.errors.term"
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <DatePicker
                                v-model="form.start_date"
                                label="Start Date"
                                placeholder="Select Start"
                                :error="form.errors.start_date"
                            />
                            <DatePicker
                                v-model="form.end_date"
                                label="End Date"
                                placeholder="Select End"
                                :error="form.errors.end_date"
                            />
                        </div>

                        <label class="flex cursor-pointer items-center gap-3">
                            <input
                                type="checkbox"
                                v-model="form.is_current"
                                class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary disabled:pointer-events-none"
                            />
                            <span class="text-sm text-gray-600">Set as current active session</span>
                        </label>

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
                                {{ isEditing ? 'Update Session' : 'Create Session' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Session?"
            :message="`Are you sure you want to delete the ${sessionToDelete?.name} academic session? This cannot be undone.`"
            confirm-label="Delete Session"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
