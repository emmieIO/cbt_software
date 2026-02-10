<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { store as storeExamAction } from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Assignment {
    id: string;
    subject: { id: string; name: string };
    school_class: { id: string; name: string };
}

const props = defineProps<{
    assignments: Assignment[];
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));

const form = useForm({
    title: '',
    assignment_id: '', // Helper for selection
    subject_id: '',
    school_class_id: '',
    duration: 60,
    type: 'terminal',
    start_time: '',
    end_time: '',
});

const handleAssignmentChange = () => {
    const assignment = props.assignments.find((a) => a.id === form.assignment_id);
    if (assignment) {
        form.subject_id = assignment.subject.id;
        form.school_class_id = assignment.school_class.id;
    }
};

const submit = () => {
    form.post(storeExamAction().url);
};
</script>

<template>
    <component :is="Layout">
        <Head title="Configure New Examination" />

        <div class="mx-auto max-w-3xl space-y-10">
            <div class="text-center">
                <h2 class="text-4xl font-black italic tracking-tight text-slate-900">New Examination</h2>
                <p class="mt-2 text-sm font-bold text-slate-500 uppercase tracking-widest">Stage 1: Base Configuration</p>
            </div>

            <div class="overflow-hidden rounded-[3rem] border border-slate-100 bg-white p-12 shadow-2xl">
                <form @submit.prevent="submit" class="space-y-10">
                    <div class="space-y-6">
                        <div>
                            <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Exam Title</label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                placeholder="e.g. 2025/2026 Second Term Physics Final"
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-6 py-5 text-lg font-black italic text-slate-800 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                >Select Assigned Teaching Load</label
                            >
                            <select
                                v-model="form.assignment_id"
                                @change="handleAssignmentChange"
                                required
                                class="w-full rounded-2xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                            >
                                <option value="" disabled>Choose Subject & Class Combo</option>
                                <option v-for="load in assignments" :key="load.id" :value="load.id">
                                    {{ load.subject.name }} — {{ load.school_class.name }}
                                </option>
                            </select>
                            <p class="mt-2 ml-1 text-[10px] font-bold text-slate-400 italic">
                                Note: You can only create exams for classes assigned to you by the Admin.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Duration (Minutes)</label
                                >
                                <input
                                    v-model="form.duration"
                                    type="number"
                                    required
                                    min="1"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Examination Type</label
                                >
                                <select
                                    v-model="form.type"
                                    required
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                >
                                    <option value="terminal">Terminal Exam</option>
                                    <option value="ca">Continuous Assessment (CA)</option>
                                    <option value="entrance">Entrance Exam</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Schedule Start (Optional)</label
                                >
                                <input
                                    v-model="form.start_time"
                                    type="datetime-local"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                            </div>
                            <div>
                                <label class="mb-3 ml-1 block text-[10px] font-black tracking-widest text-slate-400 uppercase"
                                    >Schedule End (Optional)</label
                                >
                                <input
                                    v-model="form.end_time"
                                    type="datetime-local"
                                    class="w-full rounded-2xl border-slate-100 bg-slate-50 px-6 py-5 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 border-t border-slate-50 pt-8">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex flex-1 items-center justify-center gap-3 rounded-2xl bg-primary py-5 text-sm font-black tracking-[0.2em] text-white uppercase shadow-xl shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                        >
                            Next: Allocate Questions
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </component>
</template>
