<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    manageQuestions
} from '@/actions/App/Http/Controllers/Staff/ExamController';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Exam {
    id: string;
    title: string;
    subject: { name: string };
    school_class: { name: string };
    status: string;
    duration: number;
    questions: any[];
}

const props = defineProps<{
    exam: Exam;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));
</script>

<template>
    <component :is="Layout">
        <Head :title="exam.title" />

        <div class="space-y-10">
            <div class="relative overflow-hidden rounded-[2.5rem] bg-slate-900 px-10 py-12 text-white shadow-2xl">
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <div class="mb-4 flex items-center gap-4">
                            <span class="rounded-full bg-primary px-3 py-1 text-[9px] font-black tracking-widest uppercase text-slate-900">{{ exam.status }}</span>
                            <h1 class="text-3xl font-black italic">{{ exam.title }}</h1>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Subject:</span>
                                <span class="text-sm font-bold text-lemon-yellow">{{ exam.subject.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Class:</span>
                                <span class="text-sm font-bold text-lemon-yellow">{{ exam.school_class.name }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">Duration:</span>
                                <span class="text-sm font-bold text-lemon-yellow">{{ exam.duration }} Mins</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <Link 
                            :href="manageQuestions(exam.id).url"
                            class="flex items-center gap-3 rounded-2xl bg-primary px-8 py-4 text-xs font-black tracking-widest text-slate-900 uppercase transition-all hover:scale-105 active:scale-95 shadow-xl shadow-primary/20"
                        >
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Manage Questions
                        </Link>
                    </div>
                </div>
                <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-primary/20 blur-3xl"></div>
            </div>

            <div class="space-y-6">
                <div class="flex items-center justify-between ml-2">
                    <h3 class="flex items-center gap-3 text-sm font-black tracking-[0.2em] text-slate-400 uppercase">
                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                        Allocated Questions ({{ exam.questions.length }})
                    </h3>
                </div>

                <div v-if="exam.questions.length > 0" class="grid grid-cols-1 gap-4">
                    <div 
                        v-for="(question, index) in exam.questions" 
                        :key="question.id"
                        class="group rounded-3xl border border-slate-100 bg-white p-6 transition-all hover:border-primary/20 hover:shadow-lg flex items-center justify-between"
                    >
                        <div class="flex items-start gap-6">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-xs font-black text-slate-400">
                                {{ index + 1 }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700 leading-relaxed">{{ question.content }}</p>
                                <div class="mt-2 flex items-center gap-3">
                                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">{{ question.type }}</span>
                                    <div class="h-1 w-1 rounded-full bg-slate-200"></div>
                                    <span class="text-[10px] font-black tracking-widest text-slate-400 uppercase">{{ question.difficulty }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div 
                    v-else
                    class="flex flex-col items-center justify-center rounded-[2.5rem] border-2 border-dashed border-slate-200 bg-slate-50 py-20 text-center"
                >
                    <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-white text-slate-200 shadow-sm">
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-black text-slate-400 uppercase tracking-widest">No Questions Allocated</h4>
                    <p class="mt-2 text-sm font-bold text-slate-400">Click "Manage Questions" to start adding questions to this exam.</p>
                </div>
            </div>
        </div>
    </component>
</template>