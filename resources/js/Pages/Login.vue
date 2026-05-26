<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    login_id: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Login" />
    <div class="flex min-h-screen flex-col items-center justify-center bg-primary p-4">
        <div class="w-full max-w-md">
            <div class="mb-8 flex flex-col items-center text-center">
                <img src="/assets/img/chrisland-school-logo.png" alt="Chrisland Schools" class="mb-6 h-24 w-auto object-contain" />
                <h1 class="text-2xl font-bold text-white">Question Bank</h1>
                <p class="mt-1 text-sm text-white/70">Sign in to continue</p>
            </div>

            <div class="rounded-2xl bg-white dark:bg-green-950/60 p-8 shadow-xl">
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label for="login_id" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Username or Email</label>
                        <input
                            id="login_id"
                            v-model="form.login_id"
                            type="text"
                            required
                            autofocus
                            class="mt-1"
                            placeholder="Enter your username"
                        />
                        <div v-if="form.errors.login_id" class="mt-1 text-xs text-red-600">{{ form.errors.login_id }}</div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-200">Password</label>
                        <div class="relative mt-1">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                class="mt-1 pr-11"
                                placeholder="Enter your password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:text-gray-300"
                            >
                                <svg v-if="!showPassword" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        <div v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</div>
                    </div>

                    <div class="flex items-center">
                        <input id="remember" v-model="form.remember" type="checkbox" />
                        <label for="remember" class="ml-2 text-sm text-gray-600 dark:text-gray-300">Remember me</label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center rounded-xl bg-primary px-4 py-3 text-sm font-semibold text-white shadow-lg transition-all hover:bg-primary/90 disabled:opacity-50"
                    >
                        <span v-if="form.processing" class="mr-2 inline-block size-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                        Sign In
                    </button>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-white/50">&copy; {{ new Date().getFullYear() }} Chrisland Schools. All rights reserved.</p>
        </div>
    </div>
</template>
