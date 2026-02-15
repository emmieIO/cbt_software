<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface Exam {
    id: string;
    title: string;
    subject: { name: string };
    school_class?: { name: string };
    prospective_class?: { name: string };
    attempts_count: number;
    type: string;
}

defineProps<{
    exams: PaginatedData<Exam>;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props.auth.user as any).roles.includes('admin'));
const Layout = computed(() => (isAdmin.value ? AdminLayout : StaffLayout));
</script>

<template>
    <component :is="Layout">
        <Head title="Examination Results" />

        <div class="space-y-10">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight italic">Results & Analytics</h1>
                <p class="mt-1 text-sm font-bold text-slate-400 uppercase tracking-widest">Select an examination to review student performance and security logs.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                <div 
                    v-for="exam in exams.data" 
                    :key="exam.id"
                    class="group relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-8 shadow-sm transition-all hover:border-primary/20 hover:shadow-2xl"
                >
                    <div class="relative z-10 flex flex-col h-full">
                        <div class="mb-6">
                            <span class="rounded-xl bg-primary/5 px-2 py-1 text-[9px] font-black text-primary uppercase tracking-widest">{{ exam.subject.name }}</span>
                            <h3 class="mt-3 text-xl font-black text-slate-800 line-clamp-2 leading-tight group-hover:text-primary transition-colors">{{ exam.title }}</h3>
                            <p class="mt-2 text-[10px] font-bold text-slate-400 uppercase tracking-tighter">
                                {{ exam.type === 'entrance' ? exam.prospective_class?.name : exam.school_class?.name }}
                            </p>
                        </div>

                        <div class="mt-auto pt-6 border-t border-slate-50 flex items-center justify-between">
                            <div class="flex flex-col">
                                <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ exam.attempts_count }}</span>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Submissions</span>
                            </div>
                            
                            <Link 
                                :href="`/staff/exams/${exam.id}/results`"
                                class="rounded-xl bg-slate-900 px-6 py-3 text-[10px] font-black tracking-widest text-white uppercase shadow-lg transition-all hover:bg-black active:scale-95"
                            >
                                View Results &rarr;
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="exams.data.length === 0" class="col-span-full py-24 text-center opacity-30">
                    <p class="text-lg font-bold uppercase tracking-widest">No Examinations Found</p>
                </div>
            </div>
        </div>
    </component>
</template>
