<script setup lang="ts">
import { onMounted } from 'vue';

defineProps<{
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
}>();

const emit = defineEmits(['close']);

onMounted(() => {
    // Auto-close handled by store, but we could add progress bar here
});
</script>

<template>
    <div
        class="rounded-1.5rem pointer-events-auto relative flex w-full max-w-sm overflow-hidden border border-slate-100 bg-white p-4 shadow-2xl shadow-slate-900/10 transition-all hover:scale-[1.02]"
    >
        <div class="flex items-center gap-4">
            <!-- Icon -->
            <div
                :class="[
                    'flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl shadow-lg',
                    type === 'success'
                        ? 'bg-green-500 text-white shadow-green-500/20'
                        : type === 'error'
                          ? 'bg-red-500 text-white shadow-red-500/20'
                          : 'bg-primary text-white shadow-primary/20',
                ]"
            >
                <svg v-if="type === 'success'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else-if="type === 'error'" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <svg v-else class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="3"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </div>

            <!-- Content -->
            <div class="flex-1 pr-4">
                <h4 class="text-xs font-black tracking-widest text-slate-400 uppercase">
                    {{ type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Notification' }}
                </h4>
                <p class="mt-0.5 text-sm leading-tight font-bold text-slate-700">
                    {{ message }}
                </p>
            </div>

            <!-- Close Button -->
            <button
                @click="emit('close')"
                class="flex h-8 w-8 items-center justify-center rounded-xl text-slate-300 transition-colors hover:bg-slate-50 hover:text-slate-500"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Progress Bar (Visual only) -->
        <div
            class="duration-5000ms absolute bottom-0 left-0 h-1 transition-all ease-linear"
            :class="type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-primary'"
            :style="{ width: '100%', animation: 'shrink 5s linear forwards' }"
        ></div>
    </div>
</template>

<style scoped>
@keyframes shrink {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}
</style>
