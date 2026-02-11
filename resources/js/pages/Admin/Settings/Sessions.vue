<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue';
import { ref } from 'vue';
import {
    store as storeAction,
    update as updateAction,
    setCurrent as setCurrentAction,
    destroy as destroyAction,
} from '@/actions/App/Http/Controllers/Admin/AcademicSessionController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface AcademicSession {
    id: string;
    name: string;
    is_current: boolean;
    start_date: string;
    end_date: string;
}

defineProps<{
    sessions: AcademicSession[];
}>();

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

const form = useForm('post', storeAction().url, {
    name: '',
    start_date: '',
    end_date: '',
    is_current: false,
});

const openCreateModal = () => {
    isEditing.value = false;
    editingId.value = null;
    form.reset();
    isModalOpen.value = true;
};

const openEditModal = (session: AcademicSession) => {
    isEditing.value = true;
    editingId.value = session.id;
    form.setData({
        name: session.name,
        start_date: session.start_date,
        end_date: session.end_date,
        is_current: session.is_current,
    });
    isModalOpen.value = true;
};

const submit = () => {
    if (isEditing.value && editingId.value) {
        form.submit({
            method: 'put',
            url: updateAction(editingId.value).url,
            onSuccess: () => {
                isModalOpen.value = false;
                form.reset();
            },
        });
    } else {
        form.submit({
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
                    <h2 class="text-3xl font-black tracking-tight text-slate-900">Academic Sessions</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">Define academic years and set the current active session.</p>
                </div>
                <button
                    @click="openCreateModal"
                    class="flex h-12 items-center gap-3 rounded-xl bg-primary px-6 text-sm font-black tracking-wider text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    New Session
                </button>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div
                    v-for="session in sessions"
                    :key="session.id"
                    class="group relative overflow-hidden rounded-xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:shadow-xl hover:shadow-primary/5"
                    :class="{ 'border-primary/20 ring-2 ring-primary': session.is_current }"
                >
                    <div class="relative z-10">
                        <div class="mb-6 flex items-start justify-between">
                            <div>
                                <div class="flex items-center gap-3">
                                    <h3 class="text-2xl font-black tracking-tight text-slate-800">{{ session.name }}</h3>
                                    <span
                                        v-if="session.is_current"
                                        class="animate-pulse rounded-full bg-primary px-3 py-1 text-[9px] font-black tracking-widest text-white uppercase"
                                        >Current Session</span
                                    >
                                </div>
                                <p class="mt-1 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                    {{ formatDate(session.start_date) }} — {{ formatDate(session.end_date) }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    v-if="!session.is_current"
                                    @click="setAsCurrent(session)"
                                    class="flex h-10 items-center gap-2 rounded-xl bg-slate-50 px-4 text-[10px] font-black text-slate-500 uppercase transition-all hover:bg-primary hover:text-white"
                                >
                                    Set Current
                                </button>
                                <button
                                    @click="openEditModal(session)"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-slate-900 hover:text-white"
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
                                    v-if="!session.is_current"
                                    @click="confirmDelete(session)"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-xl bg-slate-50 p-4">
                                <span class="mb-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Start Date</span>
                                <span class="text-sm font-bold text-slate-700">{{ formatDate(session.start_date) }}</span>
                            </div>
                            <div class="rounded-xl bg-slate-50 p-4">
                                <span class="mb-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">End Date</span>
                                <span class="text-sm font-bold text-slate-700">{{ formatDate(session.end_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="isModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
                <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-xl bg-white p-10 shadow-2xl">
                    <h3 class="mb-8 text-2xl font-black text-slate-900">{{ isEditing ? 'Edit Session' : 'Define New Session' }}</h3>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Session Name</label>
                            <input
                                v-model="form.name"
                                @change="form.validate('name')"
                                type="text"
                                required
                                placeholder="e.g. 2026/2027"
                                :class="{'border-red-500': form.invalid('name')}"
                                class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                            <div v-if="form.errors.name" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.name }}</div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Start Date</label>
                                <input
                                    v-model="form.start_date"
                                    @change="form.validate('start_date')"
                                    type="date"
                                    required
                                    :class="{'border-red-500': form.invalid('start_date')}"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.start_date" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.start_date }}</div>
                            </div>
                            <div>
                                <label class="mb-2 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">End Date</label>
                                <input
                                    v-model="form.end_date"
                                    @change="form.validate('end_date')"
                                    type="date"
                                    required
                                    :class="{'border-red-500': form.invalid('end_date')}"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                                <div v-if="form.errors.end_date" class="mt-2 text-xs font-bold text-red-500">{{ form.errors.end_date }}</div>
                            </div>
                        </div>

                        <label class="ml-1 flex cursor-pointer items-center gap-3">
                            <input
                                type="checkbox"
                                v-model="form.is_current"
                                class="h-5 w-5 rounded border-slate-200 text-primary focus:ring-primary/20"
                            />
                            <span class="text-xs font-bold text-slate-600">Set as current active session</span>
                        </label>

                        <div class="flex gap-3 border-t border-slate-50 pt-4">
                            <button
                                type="button"
                                @click="isModalOpen = false"
                                class="flex-1 rounded-xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
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
