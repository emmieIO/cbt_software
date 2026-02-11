<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useForm } from 'laravel-precognition-vue';
import { store } from '@/actions/App/Http/Controllers/Staff/StaffController';

const form = useForm('post', store().url, {
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.submit({
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Staff Login" />

    <div class="flex min-h-screen items-center justify-center bg-[#F8F9FB] p-6 font-sans">
        <div class="w-full max-w-md space-y-8 rounded-2xl border border-slate-100 bg-white p-8 shadow-xl md:p-12">
            <!-- Header -->
            <div class="flex flex-col items-center text-center">
                <Link href="/">
                    <img
                        src="/assets/img/chrisland-school-logo.png"
                        alt="Chrisland School Logo"
                        class="h-20 w-auto object-contain transition-transform hover:scale-105"
                    />
                </Link>
                <h1 class="mt-6 text-2xl font-black tracking-tight text-slate-900 uppercase">Staff Portal</h1>
                <p class="mt-2 text-sm font-bold text-slate-400 uppercase tracking-widest">Educators & Academic Admin</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="mt-8 space-y-6">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Username / ID</label>
                        <input
                            id="username"
                            v-model="form.username"
                            @change="form.validate('username')"
                            type="text"
                            required
                            autofocus
                            :class="{'border-red-500': form.invalid('username')}"
                            class="block w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            placeholder="e.g. JS-2025-001"
                        />
                        <div v-if="form.errors.username" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.username }}</div>
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-[10px] font-black tracking-widest text-slate-400 uppercase">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            @change="form.validate('password')"
                            type="password"
                            required
                            :class="{'border-red-500': form.invalid('password')}"
                            class="block w-full rounded-xl border-slate-100 bg-slate-50 px-5 py-4 text-sm font-bold text-slate-700 transition-all focus:border-primary focus:bg-white focus:ring-primary/10"
                            placeholder="••••••••"
                        />
                        <div v-if="form.errors.password" class="mt-1 text-xs font-bold text-red-500">{{ form.errors.password }}</div>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="h-5 w-5 rounded border-slate-300 text-primary transition-all focus:ring-primary/10"
                        />
                        <label for="remember" class="ml-3 text-sm font-bold text-slate-500">Stay signed in</label>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="flex w-full items-center justify-center rounded-xl bg-primary py-5 text-sm font-black tracking-[0.2em] text-white uppercase shadow-lg shadow-primary/20 transition-all hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50"
                >
                    <span v-if="form.processing" class="mr-3 h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                    Sign Into Portal
                </button>
            </form>

            <div class="mt-8 text-center">
                <Link href="/" class="text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:text-primary underline underline-offset-8">
                    &larr; Return to main site
                </Link>
            </div>
        </div>
    </div>
</template>
