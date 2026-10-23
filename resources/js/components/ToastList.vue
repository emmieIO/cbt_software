<script setup lang="ts">
import { toastStore } from '@/stores/toast';
import ToastItem from './ToastItem.vue';
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed top-4 right-4 left-4 z-[110] flex flex-col gap-2 sm:left-auto sm:w-90"
            aria-live="polite"
            aria-relevant="additions"
        >
            <TransitionGroup
                enter-active-class="transition duration-150 ease-out motion-reduce:transition-none"
                enter-from-class="-translate-y-1 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <ToastItem
                    v-for="toast in toastStore.items"
                    :key="toast.id"
                    :message="toast.message"
                    :type="toast.type"
                    @close="toastStore.remove(toast.id)"
                />
            </TransitionGroup>
        </div>
    </Teleport>
</template>
