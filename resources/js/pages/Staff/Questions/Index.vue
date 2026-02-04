<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import StaffLayout from '@/layouts/StaffLayout.vue';
import { create } from '@/actions/App/Http/Controllers/Staff/StaffController';

const props = defineProps<{
    questions: {
        data: any[];
        links: any[];
    };
}>();

const page = usePage();
const flash = computed(() => page.props.flash as any);
</script>

<template>
    <StaffLayout>
        <Head title="Question Repository" />

        <div class="p-8">
            <!-- Success Alert -->
            <div 
                v-if="flash.success" 
                class="mb-8 p-4 bg-green-50 border border-green-200 text-green-700 rounded-xl flex items-center justify-between shadow-sm"
            >
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                    <span class="font-bold">{{ flash.success }}</span>
                </div>
                <button @click="flash.success = null" class="text-green-500 hover:text-green-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="w-full space-y-8">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold text-slate-900">Question Bank</h2>
                        <p class="text-slate-500 mt-1">Review and manage the existing question repository.</p>
                    </div>
                    <Link 
                        :href="create().url"
                        class="flex items-center px-6 py-3 rounded-xl font-bold bg-primary text-white shadow-lg hover:bg-primary/90 hover:scale-105 active:scale-95 transition-all"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                        Add New Question
                    </Link>
                </div>

                <!-- Repository Table -->
                <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">Available Questions</h3>
                        </div>
                        <div class="flex gap-4">
                            <span class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm font-bold">
                                Total Questions: {{ questions.data.length }}
                            </span>
                        </div>
                    </div>

                    <div class="overflow-hidden border border-slate-100 rounded-2xl">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Question</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Subject / Topic</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Class</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider">Difficulty</th>
                                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="question in questions.data" :key="question.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-slate-900 line-clamp-2">{{ question.content }}</p>
                                        <p class="text-xs text-slate-400 mt-1">Options: {{ question.options.length }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-700">{{ question.topic.subject.name }}</span>
                                            <span class="text-xs text-slate-500">{{ question.topic.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ question.school_class.name }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span 
                                            :class="[
                                                'px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                                                question.difficulty === 'easy' ? 'bg-green-100 text-green-700' :
                                                question.difficulty === 'medium' ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700'
                                            ]"
                                        >
                                            {{ question.difficulty }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right space-x-2">
                                        <button class="text-primary hover:text-primary/80 font-bold text-sm">Edit</button>
                                        <button class="text-slate-400 hover:text-red-500 font-bold text-sm">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="questions.data.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 italic text-lg">
                                        No questions found in the repository yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8 flex justify-center gap-2">
                        <Link
                            v-for="link in questions.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-4 py-2 rounded-lg text-sm font-medium transition-all"
                            :class="[
                                link.active ? 'bg-primary text-white shadow-md' : 'bg-white border border-slate-200 text-slate-600 hover:border-slate-300',
                                !link.url ? 'opacity-50 cursor-not-allowed' : ''
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </StaffLayout>
</template>