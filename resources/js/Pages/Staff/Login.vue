<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Staff/StaffAuthController';

const form = useForm({
    login_id: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(store().url, {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Staff Login" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 p-6">
        <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-sm md:p-10">
            <!-- Header -->
            <div class="flex flex-col items-center text-center">
                <Link href="/">
                    <img src="/assets/img/chrisland-school-logo.png" alt="Chrisland School Logo" class="mb-6 h-16 w-auto object-contain" />
                </Link>
                <h1 class="text-2xl font-semibold text-gray-800">Staff Portal</h1>
                <p class="mt-1 text-sm text-gray-500">Educators & Academic Admin</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="mt-8 space-y-5">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="mb-2 block text-sm font-medium text-gray-700">Username / ID</label>
                        <input
                            id="username"
                            v-model="form.login_id"
                            type="text"
                            required
                            autofocus
                            class="block w-full rounded-lg border-gray-200 bg-gray-50 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                            placeholder="Enter Staff ID or Username"
                        />
                        <div v-if="form.errors.login_id" class="mt-1 text-xs text-red-600">{{ form.errors.login_id }}</div>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-gray-700">Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                class="block w-full rounded-lg border-gray-200 bg-gray-50 px-4 py-3 pr-11 text-sm focus:border-primary focus:ring-primary disabled:pointer-events-none disabled:opacity-50"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition-colors hover:text-primary"
                            >
                                <svg v-if="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                    />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"
                                    />
                                </svg>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="mt-0.5 size-4 shrink-0 rounded border-gray-200 accent-primary text-primary transition-all focus:ring-primary focus:ring-offset-0"
                        />
                        <label for="remember" class="ml-3 text-sm text-gray-600">Stay signed in</label>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-3 text-sm font-semibold text-white hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
                >
                    <span
                        v-if="form.processing"
                        class="inline-block size-4 animate-spin rounded-full border-[3px] border-current border-t-transparent text-white"
                    ></span>
                    Sign Into Portal
                </button>
            </form>

            <div class="mt-8 text-center">
                <Link href="/" class="text-xs font-medium text-gray-500 transition-colors hover:text-primary"> &larr; Return to main site </Link>
            </div>
        </div>
    </div>
</template>
