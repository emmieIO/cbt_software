<script setup lang="ts">
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { 
    show, 
    storeVersion, 
    destroyVersion,
    manageQuestions
} from '@/actions/App/Http/Controllers/Staff/ExamController';
import StaffLayout from '@/layouts/StaffLayout.vue';

interface Version {
    id: string;
    name: string;
    questions: any[];
}

interface Exam {
    id: string;
    title: string;
    subject: { name: string };
    school_class: { name: string };
    status: string;
    duration: number;
    versions: Version[];
}

const props = defineProps<{
    exam: Exam;
}>();

const isVersionModalOpen = ref(false);
const versionForm = useForm({
    name: '',
});

const addVersion = () => {
    versionForm.post(storeVersion(props.exam.id).url, {
        onSuccess: () => {
            isVersionModalOpen.value = false;
            versionForm.reset();
        }
    });
};

const handleDeleteVersion = (versionId: string) => {
    if (confirm('Are you sure you want to remove this paper version?')) {
        router.delete(destroyVersion({ exam: props.exam.id, version: versionId }).url);
    }
};
</script>

<template>
    <StaffLayout>
        <Head :title="exam.title" />

        <div class="space-y-10">
            <div class="relative overflow-hidden rounded-[2.5rem] bg-slate-900 px-10 py-12 text-white shadow-2xl">
                <div class="relative z-10 flex items-center justify-between">
                    <div>
                        <div class="mb-4 flex items-center gap-4">
                            <span class="rounded-full bg-primary px-3 py-1 text-[9px] font-black tracking-widest uppercase">{{ exam.status }}</span>
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
                    <button 
                        @click="isVersionModalOpen = true"
                        class="flex items-center gap-3 rounded-2xl bg-white/10 px-6 py-4 text-xs font-black tracking-widest text-white uppercase backdrop-blur-xl transition-all hover:bg-white/20 active:scale-95"
                    >
                        <svg class="h-5 w-5 text-lemon-yellow" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Paper Type (Version)
                    </button>
                </div>
                <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-primary/20 blur-3xl"></div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
                <!-- Version List -->
                <div class="space-y-6 lg:col-span-12">
                    <h3 class="flex items-center gap-3 text-sm font-black tracking-[0.2em] text-slate-400 uppercase ml-2">
                        <div class="h-2 w-2 rounded-full bg-primary"></div>
                        Exam Paper Types (Versions)
                    </h3>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div 
                            v-for="version in exam.versions" 
                            :key="version.id"
                            class="group rounded-[2.5rem] border-2 border-slate-100 bg-white p-10 transition-all hover:border-primary/20 hover:shadow-2xl"
                        >
                            <div class="mb-8 flex items-center justify-between">
                                <div>
                                    <h4 class="text-2xl font-black text-slate-800 italic">{{ version.name }}</h4>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">{{ version.questions.length }} Questions Allocated</p>
                                </div>
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary text-white shadow-lg shadow-primary/20">
                                    <span class="text-xl font-black italic">{{ version.name.charAt(version.name.length - 1) }}</span>
                                </div>
                            </div>

                            <div class="flex gap-3">
                                <Link 
                                    :href="manageQuestions({ exam: exam.id, version: version.id }).url"
                                    class="flex flex-1 items-center justify-center gap-2 rounded-2xl bg-slate-900 py-4 text-[10px] font-black tracking-widest text-white uppercase transition-all hover:bg-black active:scale-95"
                                >
                                    Select Questions
                                </Link>
                                <button 
                                    @click="handleDeleteVersion(version.id)"
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 transition-all hover:bg-red-500 hover:text-white"
                                >
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Empty Placeholder -->
                        <div 
                            v-if="exam.versions.length === 0"
                            @click="isVersionModalOpen = true"
                            class="group flex cursor-pointer flex-col items-center justify-center rounded-[2.5rem] border-2 border-dashed border-slate-200 bg-slate-50 py-20 transition-all hover:border-primary hover:bg-white"
                        >
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-white text-slate-300 shadow-sm transition-all group-hover:bg-primary group-hover:text-white">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <p class="text-sm font-black italic tracking-widest text-slate-400 uppercase">Click to define Paper Type A</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Version Modal -->
        <div v-if="isVersionModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="isVersionModalOpen = false" class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
            <div class="animate-in zoom-in-95 relative w-full max-w-md overflow-hidden rounded-[2.5rem] bg-white p-10 shadow-2xl">
                <h3 class="mb-8 text-2xl font-black text-slate-900">Add Paper Version</h3>
                <form @submit.prevent="addVersion" class="space-y-6">
                    <div>
                        <label class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase ml-1">Version Name</label>
                        <input
                            v-model="versionForm.name"
                            type="text"
                            required
                            placeholder="e.g. Paper Type A"
                            class="w-full rounded-2xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary"
                        />
                    </div>
                    <div class="flex gap-3 pt-4 border-t border-slate-50">
                        <button type="button" @click="isVersionModalOpen = false" class="flex-1 rounded-2xl border border-slate-100 py-4 text-sm font-black tracking-widest text-slate-400 uppercase">Cancel</button>
                        <button type="submit" :disabled="versionForm.processing" class="flex-1 rounded-2xl bg-primary py-4 text-sm font-black tracking-widest text-white uppercase shadow-lg shadow-primary/20">Add Version</button>
                    </div>
                </form>
            </div>
        </div>
    </StaffLayout>
</template>
