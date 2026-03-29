<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/Admin/AdminController';

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
    <Head title="Admin Login" />

    <div class="flex min-h-screen items-center justify-center bg-primary p-6 font-sans">
        <div class="w-full max-w-md space-y-8 rounded-xl border-t-4 border-lemon-yellow bg-white p-8 shadow-2xl md:p-12">
            <!-- Header -->
            <div class="flex flex-col items-center text-center">
                <Link href="/">
                    <img
                        src="/assets/img/chrisland-school-logo.png"
                        alt="Chrisland School Logo"
                        class="h-24 w-auto object-contain transition-transform hover:scale-105"
                    />
                </Link>
                <h1 class="mt-6 text-2xl font-bold text-slate-900">System Administrator</h1>
                <p class="mt-2 text-sm text-slate-600">Enter your credentials to manage the CBT system.</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-semibold text-slate-700">Administrator Username</label>
                        <input
                            id="username"
                            v-model="form.login_id"
                            type="text"
                            required
                            autofocus
                            class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition-all focus:border-primary focus:ring-primary"
                            placeholder="Enter Admin Username"
                        />
                        <div v-if="form.errors.login_id" class="mt-1 text-xs text-red-600">{{ form.errors.login_id }}</div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-slate-900 shadow-sm transition-all focus:border-primary focus:ring-primary"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 transition-colors hover:text-primary"
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
                            class="size-4 rounded border-slate-300 accent-primary text-primary transition-all focus:ring-primary focus:ring-offset-0"
                        />
                        <label for="remember" class="ml-3 text-sm font-bold text-slate-500">Stay signed in</label>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex w-full items-center justify-center rounded-xl bg-primary py-4 text-lg font-bold text-white shadow-lg transition-all hover:scale-[1.02] hover:bg-primary/90 active:scale-[0.98] disabled:opacity-50"
                >
                    <span v-if="form.processing" class="mr-2 animate-spin">
                        <svg class="h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            ></path>
                        </svg>
                    </span>
                    Admin Login
                </button>
            </form>

            <div class="mt-8 text-center text-sm">
                <Link href="/" class="font-medium text-slate-600 underline underline-offset-4 transition-colors hover:text-primary">
                    &larr; Back to portal selection
                </Link>
            </div>
        </div>
    </div>
</template>
