<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/Staff/StaffAuthController';

const form = useForm({
    login_id: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(store().url, {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Staff Login" />

    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 p-6">
        <div class="w-full max-w-md bg-white border border-gray-200 rounded-xl shadow-sm p-8 md:p-10">
            <!-- Header -->
            <div class="flex flex-col items-center text-center">
                <Link href="/">
                    <img
                        src="/assets/img/chrisland-school-logo.png"
                        alt="Chrisland School Logo"
                        class="h-16 w-auto object-contain mb-6"
                    />
                </Link>
                <h1 class="text-2xl font-semibold text-gray-800">Staff Portal</h1>
                <p class="mt-1 text-sm text-gray-500">Educators & Academic Admin</p>
            </div>

            <!-- Login Form -->
            <form @submit.prevent="submit" class="mt-8 space-y-5">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username / ID</label>
                        <input
                            id="username"
                            v-model="form.login_id"
                            type="text"
                            required
                            autofocus
                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50"
                            placeholder="Enter Staff ID or Username"
                        />
                        <div v-if="form.errors.login_id" class="mt-1 text-xs text-red-600">{{ form.errors.login_id }}</div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            class="py-3 px-4 block w-full border-gray-200 rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none bg-gray-50"
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
                            class="shrink-0 mt-0.5 border-gray-200 rounded text-primary focus:ring-primary"
                        />
                        <label for="remember" class="ml-3 text-sm text-gray-600">Stay signed in</label>
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none"
                >
                    <span v-if="form.processing" class="animate-spin inline-block size-4 border-[3px] border-current border-t-transparent text-white rounded-full"></span>
                    Sign Into Portal
                </button>
            </form>

            <div class="mt-8 text-center">
                <Link
                    href="/"
                    class="text-xs font-medium text-gray-500 hover:text-primary transition-colors"
                >
                    &larr; Return to main site
                </Link>
            </div>
        </div>
    </div>
</template>
