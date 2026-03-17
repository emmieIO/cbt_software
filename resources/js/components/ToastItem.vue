<script setup lang="ts">
defineProps<{
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
}>();

const emit = defineEmits(['close']);
</script>

<template>
    <div
        class="group pointer-events-auto relative flex w-full max-w-md overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-xl transition-all hover:scale-[1.01] sm:min-w-[320px]"
    >
        <div class="flex items-start gap-3.5 w-full">
            <!-- Icon Indicator -->
            <div
                :class="[
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg transition-colors',
                    type === 'success'
                        ? 'bg-emerald-50 text-emerald-600'
                        : type === 'error'
                          ? 'bg-rose-50 text-rose-600'
                          : 'bg-primary/5 text-primary',
                ]"
            >
                <svg v-if="type === 'success'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else-if="type === 'error'" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </div>

            <!-- Text Content -->
            <div class="flex-1 min-w-0">
                <p class="text-[13px] leading-snug font-bold text-slate-800">
                    {{ message }}
                </p>
                <p class="mt-1 text-[10px] font-black tracking-widest uppercase opacity-40"
                    :class="[
                        type === 'success' ? 'text-emerald-700' : type === 'error' ? 'text-rose-700' : 'text-primary'
                    ]"
                >
                    {{ type === 'success' ? 'System Success' : type === 'error' ? 'System Error' : 'System Alert' }}
                </p>
            </div>

            <!-- Actions -->
            <button
                @click="emit('close')"
                class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-slate-300 transition-all hover:bg-slate-50 hover:text-slate-500 active:scale-90"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Progress Indicator -->
        <div class="absolute bottom-0 left-0 h-0.5 w-full bg-slate-50">
            <div
                class="h-full transition-all ease-linear"
                :class="type === 'success' ? 'bg-emerald-500' : type === 'error' ? 'bg-rose-500' : 'bg-primary'"
                :style="{ width: '100%', animation: 'shrink 5s linear forwards' }"
            ></div>
        </div>
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
