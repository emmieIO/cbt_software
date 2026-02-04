<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/Staff/StaffController';

const form = useForm({
    login_id: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(store().action, {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Staff Login" />

    <div class="flex min-h-screen items-center justify-center bg-primary p-6 font-sans">
        <div class="w-full max-w-md space-y-8 rounded-2xl bg-white p-8 shadow-2xl md:p-12">
            <!-- Header -->
            <div class="flex flex-col items-center text-center">
                <Link href="/">
                    <img
                        src="/assets/img/chrisland-school-logo.png"
                        alt="Chrisland School Logo"
                        class="h-24 w-auto object-contain transition-transform hover:scale-105"
                    />
                </Link>
                <h1 class="mt-6 text-2xl font-bold text-slate-900">Staff Portal</h1>
                <p class="mt-2 text-sm text-slate-600">Access administrative tools and exam management.</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="space-y-4">
                    <div>
                        <label for="staff_id" class="block text-sm font-semibold text-slate-700">Staff ID / Email</label>
                        <input
                            id="staff_id"
                            v-model="form.login_id"
                            type="text"
                            required
                            autofocus
                            class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition-all focus:border-primary focus:ring-primary"
                            placeholder="STAFF/2026/001"
                        />
                        <div v-if="form.errors.login_id" class="mt-1 text-xs text-red-600">{{ form.errors.login_id }}</div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="mt-1 block w-full rounded-xl border-slate-200 bg-slate-50 px-4 py-3 text-slate-900 shadow-sm transition-all focus:border-primary focus:ring-primary"
                            placeholder="••••••••"
                        />
                        <div v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 text-primary transition-all focus:ring-primary"
                        />
                        <label for="remember" class="ml-2 block text-sm text-slate-600">Remember me</label>
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
                    Staff Login
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
