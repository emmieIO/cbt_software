<script setup lang="ts">
import { computed, onMounted, ref, onUnmounted } from 'vue';

interface Option {
    id: string | number;
    name: string;
    [key: string]: any;
}

const props = withDefaults(defineProps<{
    modelValue: string | number | null | undefined;
    options: Option[];
    placeholder?: string;
    label?: string;
    disabled?: boolean;
    error?: string;
    searchable?: boolean;
    clearable?: boolean;
    size?: 'sm' | 'md' | 'lg';
}>(), {
    placeholder: 'Select an option',
    searchable: true,
    clearable: false,
    size: 'md'
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const selectedOption = computed(() => {
    return props.options.find(opt => String(opt.id) === String(props.modelValue));
});

const toggleDropdown = () => {
    if (!props.disabled) {
        isOpen.value = !isOpen.value;
    }
};

const selectOption = (option: Option) => {
    emit('update:modelValue', option.id);
    emit('change', option.id);
    isOpen.value = false;
};

const clearSelection = (e: Event) => {
    e.stopPropagation();
    emit('update:modelValue', null);
    emit('change', null);
    isOpen.value = false;
};

// Handle clicks outside to close dropdown
const handleClickOutside = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

const sizeClasses = {
    sm: 'py-2 px-3 text-xs',
    md: 'py-3 px-4 text-sm',
    lg: 'py-4 px-5 text-base'
};

const labelSizeClasses = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base'
};
</script>

<template>
    <div class="w-full" ref="dropdownRef">
        <!-- Label -->
        <label v-if="label" :class="[
            'block font-medium mb-2 text-gray-700',
            labelSizeClasses[size]
        ]">
            {{ label }}
        </label>

        <!-- Dropdown Container -->
        <div class="relative w-full">
            <button 
                type="button" 
                :disabled="disabled"
                @click="toggleDropdown"
                :class="[
                    'w-full text-start flex items-center justify-between border rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-primary/20',
                    selectedOption ? 'text-gray-800' : 'text-gray-400',
                    error ? 'border-red-500 focus:border-red-500' : 'border-gray-200 hover:border-gray-300 focus:border-primary',
                    disabled ? 'bg-gray-50 opacity-50 cursor-not-allowed' : 'bg-white',
                    sizeClasses[size]
                ]"
            >
                <span class="truncate">{{ selectedOption ? selectedOption.name : placeholder }}</span>
                <div class="flex items-center gap-2">
                    <span v-if="clearable && selectedOption" @click.stop="clearSelection" class="p-1 rounded-md text-gray-300 hover:text-red-500">
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </span>
                    <svg 
                        :class="['shrink-0 size-4 text-gray-400 transition-transform duration-200', isOpen ? 'rotate-180' : '']" 
                        xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                    >
                        <path d="m6 9 6 6 6-6"/>
                    </svg>
                </div>
            </button>

            <!-- Dropdown Menu -->
            <div 
                v-if="isOpen"
                class="absolute left-0 z-[100] w-full min-w-[240px] bg-white shadow-xl rounded-xl p-1 mt-2 border border-gray-100 max-h-72 overflow-y-auto" 
            >
                <!-- Empty State -->
                <div v-if="options.length === 0" class="py-10 text-center">
                    <p class="text-xs font-medium text-gray-400">No options available</p>
                </div>

                <button 
                    v-for="option in options" 
                    :key="option.id"
                    type="button"
                    @click="selectOption(option)"
                    :class="[
                        'w-full flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:outline-none focus:bg-gray-100',
                        String(option.id) === String(modelValue) ? 'bg-gray-50 font-semibold' : ''
                    ]"
                >
                    {{ option.name }}
                    <svg v-if="String(option.id) === String(modelValue)" class="shrink-0 size-3.5 text-primary ms-auto" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Error Message -->
        <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
