<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue';
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

const form = useForm('post', processPromote().url, {
    from_class_id: '',
    to_class_id: '',
    student_ids: [] as string[],
});

const isConfirmModalOpen = ref(false);

const startPromotion = () => {
    form.setData({
        from_class_id: selectedSourceClassId.value,
        to_class_id: selectedTargetClassId.value,
        student_ids: selectedStudentIds.value,
    });
    isConfirmModalOpen.value = true;
};

const submitPromotion = () => {
    form.submit({
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

        <div class="space-y-10">
            <div class="relative overflow-hidden rounded-xl bg-slate-900 px-10 py-12 text-white shadow-2xl">
                <div class="relative z-10">
                    <div class="mb-4 flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/10 text-lemon-yellow backdrop-blur-xl">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <h1 class="text-3xl font-black">Promotion Control Center</h1>
                    </div>
                    <p class="max-w-2xl text-lg font-medium text-slate-400">
                        Execute top-down promotions to transition students between academic years. Ensure target classes are emptied before promoting
                        into them.
                    </p>
                </div>
                <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-primary/20 blur-3xl"></div>
            </div>

            <div class="grid grid-cols-1 gap-10 lg:grid-cols-12">
                <!-- Class Inventory -->
                <div class="space-y-6 lg:col-span-4">
                    <h3 class="ml-2 flex items-center gap-3 text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                        Class Inventory
                    </h3>

                    <div class="grid gap-4">
                        <div
                            v-for="cls in classes"
                            :key="cls.id"
                            class="flex items-center justify-between rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition-all hover:shadow-md"
                        >
                            <div>
                                <h4 class="text-sm font-black text-slate-800">{{ cls.name }}</h4>
                                <p class="text-[10px] font-bold tracking-widest text-slate-400 uppercase">{{ cls.level }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black text-slate-900">{{ cls.student_count }}</div>
                                <span
                                    class="text-[9px] font-black tracking-tighter uppercase"
                                    :class="cls.is_empty ? 'text-green-500' : 'text-amber-500'"
                                >
                                    {{ cls.is_empty ? 'Ready (Empty)' : 'Occupied' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Panel -->
                <div class="space-y-8 lg:col-span-8">
                    <div class="rounded-xl border border-slate-100 bg-white p-10 shadow-xl">
                        <div class="mb-10 grid grid-cols-1 gap-8 md:grid-cols-2">
                            <!-- Source -->
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Source Class (Current)</label
                                >
                                <select
                                    v-model="selectedSourceClassId"
                                    @change="fetchStudents"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="">Select Class to Promote From</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id" :disabled="cls.is_empty">
                                        {{ cls.name }} ({{ cls.student_count }} students)
                                    </option>
                                </select>
                                <div v-if="form.errors.from_class_id" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.from_class_id }}</div>
                            </div>

                            <!-- Target -->
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Target Class (Destination)</label
                                >
                                <select
                                    v-model="selectedTargetClassId"
                                    class="w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="">Graduate / Alumni Path</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id" :disabled="cls.id === selectedSourceClassId">
                                        Promote to {{ cls.name }} {{ !cls.is_empty ? '(Warning: Occupied)' : '' }}
                                    </option>
                                </select>
                                <div v-if="form.errors.to_class_id" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.to_class_id }}</div>
                            </div>
                        </div>

                        <!-- Student Selection -->
                        <div v-if="selectedSourceClassId" class="space-y-6">
                            <div class="flex items-center justify-between border-b border-slate-50 pb-4">
                                <h4 class="text-sm font-black text-slate-800">
                                    Promoting {{ selectedStudentIds.length }} of {{ students.length }} Students
                                </h4>
                                <button
                                    @click="toggleSelectAll"
                                    class="text-[10px] font-black tracking-widest text-primary uppercase hover:underline"
                                >
                                    {{ selectedStudentIds.length === students.length ? 'Deselect All' : 'Select All' }}
                                </button>
                            </div>

                            <div v-if="isLoadingStudents" class="flex justify-center py-10">
                                <div class="h-8 w-8 animate-spin rounded-full border-4 border-primary/20 border-t-primary"></div>
                            </div>

                            <div v-else class="custom-scrollbar grid max-h-96 grid-cols-1 gap-3 overflow-y-auto pr-2 md:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="student in students"
                                    :key="student.id"
                                    class="relative flex cursor-pointer items-center gap-3 rounded-xl border border-slate-50 p-4 transition-all hover:bg-slate-50"
                                    :class="selectedStudentIds.includes(student.id) ? 'border-primary/20 bg-primary/5' : 'bg-white'"
                                >
                                    <input
                                        type="checkbox"
                                        v-model="selectedStudentIds"
                                        :value="student.id"
                                        class="h-5 w-5 rounded border-slate-200 text-primary focus:ring-primary/20"
                                    />
                                    <div class="overflow-hidden">
                                        <p class="truncate text-xs font-black text-slate-800">{{ student.name }}</p>
                                        <p class="text-[9px] font-bold text-slate-400">{{ student.school_id || 'No ID' }}</p>
                                    </div>
                                </label>
                            </div>
                            <div v-if="form.errors.student_ids" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.student_ids }}</div>

                            <div class="pt-6">
                                <button
                                    @click="startPromotion"
                                    :disabled="selectedStudentIds.length === 0"
                                    class="flex w-full items-center justify-center gap-3 rounded-xl bg-primary py-5 text-sm font-black tracking-[0.2em] text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ selectedTargetClassId ? `Promote to ${targetClass?.name}` : 'Graduate Selected Students' }}
                                </button>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="flex flex-col items-center justify-center py-20 text-center opacity-30">
                            <svg class="mb-4 h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                />
                            </svg>
                            <p class="text-sm font-bold">Select a source class to begin the promotion process.</p>
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
