<script setup lang="ts">
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { promote as processPromote, students as fetchStudentsAction } from '@/actions/App/Http/Controllers/Admin/PromotionController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface ClassSummary {
    id: string;
    name: string;
    level: string;
    student_count: number;
    is_empty: boolean;
}

interface Student {
    id: string;
    name: string;
    school_id: string | null;
}

const props = defineProps<{
    classes: ClassSummary[];
    current_session: any | null;
}>();

const selectedSourceClassId = ref('');
const selectedTargetClassId = ref('');
const students = ref<Student[]>([]);
const selectedStudentIds = ref<string[]>([]);
const isLoadingStudents = ref(false);

const sourceClass = computed(() => props.classes.find((c) => c.id === selectedSourceClassId.value));
const targetClass = computed(() => props.classes.find((c) => c.id === selectedTargetClassId.value));

const fetchStudents = async () => {
    if (!selectedSourceClassId.value) return;

    isLoadingStudents.value = true;
    selectedStudentIds.value = [];

    try {
        const response = await fetch(fetchStudentsAction(selectedSourceClassId.value).url);
        students.value = await response.json();
        // Select all by default
        selectedStudentIds.value = students.value.map((s) => s.id);
    } catch (error) {
        console.error('Failed to fetch students:', error);
    } finally {
        isLoadingStudents.value = false;
    }
};

const toggleSelectAll = () => {
    if (selectedStudentIds.value.length === students.value.length) {
        selectedStudentIds.value = [];
    } else {
        selectedStudentIds.value = students.value.map((s) => s.id);
    }
};

const form = useForm({
    from_class_id: '',
    to_class_id: '',
    student_ids: [] as string[],
});

const isConfirmModalOpen = ref(false);

const startPromotion = () => {
    form.from_class_id = selectedSourceClassId.value;
    form.to_class_id = selectedTargetClassId.value;
    form.student_ids = selectedStudentIds.value;

    isConfirmModalOpen.value = true;
};

const submitPromotion = () => {
    form.post(processPromote().url, {
        onSuccess: () => {
            isConfirmModalOpen.value = false;
            selectedSourceClassId.value = '';
            selectedTargetClassId.value = '';
            students.value = [];
        },
    });
};
</script>

<template>
    <AdminLayout>
        <Head title="Student Promotion Wizard" />

        <div class="space-y-6">
            <div v-if="!current_session" class="rounded-xl border border-red-200 bg-red-50 p-4">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="ms-3">
                        <h3 class="text-sm font-semibold text-red-800">Action Required: No Active Session</h3>
                        <div class="mt-1 text-sm text-red-700">
                            Promotions require an active academic session to record enrollment history. 
                            Please set a current session in <Link href="/admin/school-setup/sessions" class="underline font-medium hover:text-red-700 transition-colors">Settings > Academic Sessions</Link> before continuing.
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-xl bg-gray-900 px-8 py-10 text-white shadow-lg">
                <div class="relative z-10">
                    <div class="mb-4 flex items-center gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/10 text-primary backdrop-blur-xl">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold">Promotion Control Center</h1>
                    </div>
                    <p class="max-w-2xl text-base text-gray-400">
                        Execute promotions to transition students between academic years. Ensure target classes are emptied before promoting
                        into them.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Class Inventory -->
                <div class="space-y-4 lg:col-span-4">
                    <h3 class="flex items-center gap-x-2 text-sm font-semibold text-gray-800">
                        <span class="h-2 w-2 rounded-full bg-primary"></span>
                        Class Inventory
                    </h3>

                    <div class="grid gap-3">
                        <div
                            v-for="cls in classes"
                            :key="cls.id"
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all hover:shadow-md"
                        >
                            <div>
                                <h4 class="text-sm font-semibold text-gray-800">{{ cls.name }}</h4>
                                <p class="text-xs font-medium text-gray-500">{{ cls.level }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-bold text-gray-900">{{ cls.student_count }}</div>
                                <span
                                    class="text-xs font-medium"
                                    :class="cls.is_empty ? 'text-emerald-500' : 'text-amber-500'"
                                >
                                    {{ cls.is_empty ? 'Ready (Empty)' : 'Occupied' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Panel -->
                <div class="space-y-6 lg:col-span-8">
                    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                            <!-- Source -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-800"
                                    >Source Class (Current)</label
                                >
                                <select
                                    v-model="selectedSourceClassId"
                                    @change="fetchStudents"
                                    class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                >
                                    <option value="">Select Class to Promote From</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id" :disabled="cls.is_empty">
                                        {{ cls.name }} ({{ cls.student_count }} students)
                                    </option>
                                </select>
                                <div v-if="form.errors.from_class_id" class="mt-2 text-xs text-red-500">
                                    {{ form.errors.from_class_id }}
                                </div>
                            </div>

                            <!-- Target -->
                            <div>
                                <label class="mb-2 block text-sm font-medium text-gray-800"
                                    >Target Class (Destination)</label
                                >
                                <select
                                    v-model="selectedTargetClassId"
                                    class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                >
                                    <option value="">Graduate / Alumni Path</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id" :disabled="cls.id === selectedSourceClassId">
                                        Promote to {{ cls.name }} {{ !cls.is_empty ? '(Warning: Occupied)' : '' }}
                                    </option>
                                </select>
                                <div v-if="form.errors.to_class_id" class="mt-2 text-xs text-red-500">{{ form.errors.to_class_id }}</div>
                            </div>
                        </div>

                        <!-- Student Selection -->
                        <div v-if="selectedSourceClassId" class="space-y-6">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                                <h4 class="text-sm font-semibold text-gray-800">
                                    Promoting {{ selectedStudentIds.length }} of {{ students.length }} Students
                                </h4>
                                <button
                                    @click="toggleSelectAll"
                                    class="text-xs font-semibold text-primary hover:text-primary/80"
                                >
                                    {{ selectedStudentIds.length === students.length ? 'Deselect All' : 'Select All' }}
                                </button>
                            </div>

                            <div v-if="isLoadingStudents" class="flex justify-center py-10">
                                <div class="h-6 w-6 animate-spin rounded-full border-2 border-primary/20 border-t-primary"></div>
                            </div>

                            <div v-else class="custom-scrollbar grid max-h-96 grid-cols-1 gap-3 overflow-y-auto pr-2 md:grid-cols-2">
                                <label
                                    v-for="student in students"
                                    :key="student.id"
                                    class="relative flex cursor-pointer items-center gap-3 rounded-xl border border-gray-100 p-4 transition-all hover:bg-gray-50"
                                    :class="selectedStudentIds.includes(student.id) ? 'border-primary bg-primary/5' : 'bg-white'"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="selectedStudentIds"
                                        :value="student.id"
                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                    <div class="overflow-hidden">
                                        <p class="truncate text-sm font-medium text-gray-800">{{ student.name }}</p>
                                        <p class="text-xs text-gray-500">{{ student.school_id || 'No ID' }}</p>
                                    </div>
                                </label>
                            </div>
                            <div v-if="form.errors.student_ids" class="mt-2 text-xs text-red-500">{{ form.errors.student_ids }}</div>

                            <div class="pt-6">
                                <button
                                    @click="startPromotion"
                                    :disabled="selectedStudentIds.length === 0 || !current_session"
                                    class="inline-flex w-full items-center justify-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-3 text-sm font-semibold text-white hover:bg-primary/90 focus:bg-primary/90 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                >
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ selectedTargetClassId ? `Promote to ${targetClass?.name}` : 'Graduate Selected Students' }}
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="flex flex-col items-center justify-center py-20 text-center text-gray-400">
                            <svg class="mb-4 h-12 w-12 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.5"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                />
                            </svg>
                            <p class="text-sm font-medium">Select a source class to begin the promotion process.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isConfirmModalOpen"
            title="Confirm Bulk Action?"
            :message="
                selectedTargetClassId
                    ? `You are about to promote ${selectedStudentIds.length} students from ${sourceClass?.name} to ${targetClass?.name}. Continue?`
                    : `You are about to graduate ${selectedStudentIds.length} students from ${sourceClass?.name}. This will deactivate their accounts. Continue?`
            "
            :confirm-label="selectedTargetClassId ? 'Process Promotion' : 'Confirm Graduation'"
            :variant="selectedTargetClassId ? 'primary' : 'warning'"
            @close="isConfirmModalOpen = false"
            @confirm="submitPromotion"
        />
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
</style>
