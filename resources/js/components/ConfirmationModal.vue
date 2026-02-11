<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        show?: boolean;
        title?: string;
        message?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        variant?: 'danger' | 'primary' | 'warning';
        loading?: boolean;
    }>(),
    {
        show: false,
        title: 'Confirm Action',
        message: 'Are you sure you want to proceed?',
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        variant: 'danger',
        loading: false,
    },
);

const emit = defineEmits(['close', 'confirm']);

const close = () => {
    if (!props.loading) {
        emit('close');
    }
};

const confirm = () => {
    emit('confirm');
};

// Handle Escape key
const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', handleEscape));
onUnmounted(() => document.removeEventListener('keydown', handleEscape));

// Prevent scrolling when modal is open
watch(
    () => props.show,
    (value) => {
        if (value) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    },
);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="close"></div>

                <!-- Modal Panel -->
                <Transition
                    appear
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div
                        v-if="show"
                        class="relative w-full max-w-md overflow-hidden rounded-xl bg-white p-8 shadow-2xl shadow-slate-900/20 md:p-10"
                    >
                        <!-- Icon Header -->
                        <div class="mb-8 flex flex-col items-center text-center">
                            <div
                                :class="[
                                    'mb-6 flex h-20 w-20 items-center justify-center rounded-xl shadow-lg transition-transform hover:scale-110',
                                    variant === 'danger' ? 'bg-red-50 text-red-500 shadow-red-500/20' : 'bg-primary/5 text-primary shadow-primary/20',
                                ]"
                            >
                                <svg v-if="variant === 'danger'" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                <svg v-else class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-black tracking-tight text-slate-900">{{ title }}</h3>
                            <p class="mt-3 text-sm leading-relaxed font-medium text-slate-500">
                                {{ message }}
                            </p>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex flex-col gap-3">
                            <button
                                @click="confirm"
                                :disabled="loading"
                                :class="[
                                    'flex h-14 w-full items-center justify-center rounded-xl text-sm font-black tracking-widest text-white uppercase shadow-lg transition-all active:scale-95 disabled:opacity-50',
                                    variant === 'danger'
                                        ? 'bg-red-600 shadow-red-600/20 hover:bg-red-700'
                                        : 'bg-primary shadow-primary/20 hover:bg-primary/90',
                                ]"
                            >
                                <span v-if="loading" class="mr-2 h-4 w-4 animate-spin rounded-lg-full border-2 border-white/30 border-t-white"></span>
                                {{ confirmLabel }}
                            </button>
                            <button
                                @click="close"
                                :disabled="loading"
                                class="flex h-14 w-full items-center justify-center rounded-xl bg-slate-50 text-sm font-black tracking-widest text-slate-400 uppercase transition-all hover:bg-slate-100 hover:text-slate-600 active:scale-95 disabled:opacity-50"
                            >
                                {{ cancelLabel }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
