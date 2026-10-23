<script setup lang="ts">
defineProps<{
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
}>();

const emit = defineEmits(['close']);
</script>

<template>
    <div
        class="pointer-events-auto flex w-full items-start gap-2.5 rounded-md border border-gray-200 bg-white px-3 py-3 shadow-md dark:border-gray-700 dark:bg-gray-900"
    >
        <svg
            aria-hidden="true"
            class="mt-0.5 size-4 shrink-0"
            :class="{
                'text-emerald-600 dark:text-emerald-400': type === 'success',
                'text-red-600 dark:text-red-400': type === 'error',
                'text-yellow-600 dark:text-yellow-400': type === 'warning',
                'text-blue-600 dark:text-blue-400': type === 'info',
            }"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            stroke-width="2"
        >
            <path v-if="type === 'success'" stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            <path v-else-if="type === 'error'" stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p class="min-w-0 flex-1 text-sm leading-5 font-medium break-words text-gray-800 dark:text-gray-100">{{ message }}</p>
        <button
            type="button"
            aria-label="Dismiss notification"
            class="-m-1 flex size-7 shrink-0 cursor-pointer items-center justify-center rounded text-gray-400 hover:bg-gray-100 hover:text-gray-700 focus-visible:ring-2 focus-visible:ring-primary dark:hover:bg-gray-800 dark:hover:text-gray-200"
            @click="emit('close')"
        >
            <svg aria-hidden="true" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>
