<script setup lang="ts">
import { toastStore } from '@/stores/toast';
import ToastItem from './ToastItem.vue';
</script>

<template>
    <Teleport to="body">
        <div
            class="pointer-events-none fixed top-0 right-0 z-[110] flex w-full flex-col items-center gap-3 p-4 sm:items-end sm:p-6 md:max-w-md lg:max-w-lg"
        >
            <TransitionGroup
                enter-active-class="transition duration-500 ease-out"
                enter-from-class="translate-y-[-2rem] opacity-0 scale-95"
                enter-to-class="translate-y-0 opacity-100 scale-100"
                leave-active-class="transition duration-300 ease-in absolute"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
                move-class="transition duration-500 ease-in-out"
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
