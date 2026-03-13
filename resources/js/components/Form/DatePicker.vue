<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { onClickOutside } from '@vueuse/core';

const props = withDefaults(defineProps<{
    modelValue: string | null | undefined;
    label?: string;
    placeholder?: string;
    disabled?: boolean;
    error?: string;
    size?: 'sm' | 'md' | 'lg';
}>(), {
    placeholder: 'Select date',
    size: 'md'
});

const emit = defineEmits(['update:modelValue', 'change']);

const isOpen = ref(false);
const container = ref<HTMLElement | null>(null);

// Date state
const now = new Date();
const currentMonth = ref(now.getMonth());
const currentYear = ref(now.getFullYear());
const selectedDate = ref<Date | null>(props.modelValue ? new Date(props.modelValue) : null);

const months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const daysOfWeek = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'];

const daysInMonth = computed(() => {
    return new Date(currentYear.value, currentMonth.value + 1, 0).getDate();
});

const firstDayOfMonth = computed(() => {
    // Convert to Monday start (0=Mo, 6=Su)
    const day = new Date(currentYear.value, currentMonth.value, 1).getDay();
    return day === 0 ? 6 : day - 1;
});

const calendarDays = computed(() => {
    const days = [];
    const prevMonthDays = new Date(currentYear.value, currentMonth.value, 0).getDate();
    
    // Prev padding
    for (let i = firstDayOfMonth.value - 1; i >= 0; i--) {
        days.push({
            day: prevMonthDays - i,
            month: currentMonth.value - 1,
            year: currentYear.value,
            isCurrentMonth: false,
            isDisabled: true
        });
    }
    
    // Current
    for (let i = 1; i <= daysInMonth.value; i++) {
        days.push({
            day: i,
            month: currentMonth.value,
            year: currentYear.value,
            isCurrentMonth: true,
            isDisabled: false
        });
    }
    
    // Next padding
    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        days.push({
            day: i,
            month: currentMonth.value + 1,
            year: currentYear.value,
            isCurrentMonth: false,
            isDisabled: true
        });
    }
    
    return days;
});

const formattedValue = computed(() => {
    if (!props.modelValue) return '';
    const date = new Date(props.modelValue);
    return date.toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
});

const isSelected = (day: number, month: number, year: number) => {
    if (!selectedDate.value) return false;
    const d = new Date(year, (month + 12) % 12, day);
    return selectedDate.value.toDateString() === d.toDateString();
};

const isToday = (day: number, month: number, year: number) => {
    const today = new Date();
    const d = new Date(year, (month + 12) % 12, day);
    return today.toDateString() === d.toDateString();
};

const prevMonthAction = () => {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
};

const nextMonthAction = () => {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
};

const selectDate = (day: any) => {
    if (day.isDisabled) return;
    const newDate = new Date(day.year, day.month, day.day);
    selectedDate.value = newDate;
    
    const y = newDate.getFullYear();
    const m = String(newDate.getMonth() + 1).padStart(2, '0');
    const d = String(newDate.getDate()).padStart(2, '0');
    const val = `${y}-${m}-${d}`;
    
    emit('update:modelValue', val);
    emit('change', val);
    isOpen.value = false;
};

onClickOutside(container, () => {
    isOpen.value = false;
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
    <div ref="container" class="w-full relative">
        <!-- Label -->
        <label v-if="label" :class="[
            'block font-medium mb-2 text-gray-700',
            labelSizeClasses[size]
        ]">
            {{ label }}
        </label>

        <!-- Custom Trigger -->
        <div class="relative">
            <button 
                @click="isOpen = !isOpen"
                type="button" 
                :disabled="disabled"
                :class="[
                    'w-full text-start flex items-center justify-between border rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-primary/20',
                    modelValue ? 'text-gray-800' : 'text-gray-400',
                    error ? 'border-red-500 focus:border-red-500' : 'border-gray-200 hover:border-gray-300 focus:border-primary',
                    disabled ? 'bg-gray-50 opacity-50 cursor-not-allowed' : 'bg-white',
                    sizeClasses[size]
                ]"
            >
                <span class="truncate">{{ modelValue ? formattedValue : placeholder }}</span>
                <svg class="shrink-0 size-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
            </button>
        </div>

        <!-- Custom Popover -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="translate-y-1 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-1 opacity-0 scale-95"
        >
            <div 
                v-if="isOpen" 
                class="absolute z-[1000] mt-2 w-[280px] bg-white shadow-2xl rounded-xl border border-gray-100 p-3"
            >
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-3 pb-2 border-b border-gray-50">
                    <button @click="prevMonthAction" type="button" class="size-8 flex justify-center items-center text-gray-500 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    
                    <div class="flex items-center gap-x-1">
                        <span class="text-sm font-semibold text-gray-800">{{ months[currentMonth] }}</span>
                        <span class="text-gray-300">/</span>
                        <span class="text-sm font-semibold text-gray-800">{{ currentYear }}</span>
                    </div>

                    <button @click="nextMonthAction" type="button" class="size-8 flex justify-center items-center text-gray-500 hover:bg-gray-100 rounded-full transition-colors">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>

                <!-- Weeks -->
                <div class="grid grid-cols-7 mb-1">
                    <span v-for="day in daysOfWeek" :key="day" class="text-[10px] font-bold text-gray-400 uppercase text-center">
                        {{ day }}
                    </span>
                </div>

                <!-- Days Grid -->
                <div class="grid grid-cols-7">
                    <button 
                        v-for="(day, idx) in calendarDays" 
                        :key="idx"
                        @click="selectDate(day)"
                        type="button" 
                        :disabled="day.isDisabled"
                        :class="[
                            'size-9 flex justify-center items-center text-xs font-semibold rounded-full transition-all duration-200',
                            day.isDisabled ? 'text-gray-200 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-100',
                            isSelected(day.day, day.month, day.year) ? 'bg-primary text-white hover:bg-primary shadow-lg shadow-primary/20' : '',
                            isToday(day.day, day.month, day.year) && !isSelected(day.day, day.month, day.year) ? 'border border-primary text-primary' : ''
                        ]"
                    >
                        {{ day.day }}
                    </button>
                </div>
            </div>
        </transition>

        <!-- Error Message -->
        <p v-if="error" class="mt-2 text-xs text-red-600">{{ error }}</p>
    </div>
</template>
