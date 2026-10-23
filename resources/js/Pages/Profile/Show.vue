<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { update } from '@/routes/profile';

type Profile = {
    name: string;
    username: string;
    email: string;
    role: string;
    permissions: string[];
    created_at: string | null;
};

const props = defineProps<{ profile: Profile }>();

const form = useForm({
    name: props.profile.name,
    username: props.profile.username,
    email: props.profile.email,
    current_password: '',
    password: '',
    password_confirmation: '',
});

const initials = computed(() =>
    props.profile.name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);

const submit = () => {
    form.patch(update.url(), {
        preserveScroll: true,
        onSuccess: () => form.reset('current_password', 'password', 'password_confirmation'),
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Profile" />

        <div class="mx-auto max-w-6xl space-y-6">
            <header>
                <p class="text-xs font-semibold tracking-wide text-primary uppercase">Account</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-950">Your profile</h1>
                <p class="mt-1 text-sm text-gray-500">Manage your personal details and account password.</p>
            </header>

            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="grid lg:grid-cols-[17rem_minmax(0,1fr)]">
                    <aside class="border-b border-gray-200 bg-gray-50/80 p-6 lg:border-r lg:border-b-0">
                        <div class="flex items-center gap-4 lg:block">
                            <div
                                class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-primary text-xl font-bold text-white shadow-sm"
                            >
                                {{ initials }}
                            </div>
                            <div class="min-w-0 lg:mt-4">
                                <h2 class="truncate text-base font-bold text-gray-950">{{ profile.name }}</h2>
                                <p class="mt-1 truncate text-sm text-gray-500">@{{ profile.username }}</p>
                            </div>
                        </div>

                        <dl class="mt-6 space-y-4 border-t border-gray-200 pt-5 text-sm">
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-gray-400 uppercase">Account role</dt>
                                <dd class="mt-1 font-semibold text-gray-800 capitalize">{{ profile.role }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-gray-400 uppercase">Member since</dt>
                                <dd class="mt-1 font-medium text-gray-700">{{ profile.created_at || 'Not available' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold tracking-wide text-gray-400 uppercase">Permissions</dt>
                                <dd class="mt-2 flex flex-wrap gap-1.5">
                                    <span
                                        v-if="profile.role === 'admin'"
                                        class="rounded-md bg-primary/10 px-2 py-1 text-xs font-semibold text-primary"
                                    >
                                        Full access
                                    </span>
                                    <span
                                        v-for="permission in profile.permissions"
                                        v-else
                                        :key="permission"
                                        class="rounded-md bg-gray-200 px-2 py-1 text-xs font-medium text-gray-700"
                                    >
                                        {{ permission.replace('.', ' ') }}
                                    </span>
                                    <span v-if="profile.role !== 'admin' && profile.permissions.length === 0" class="text-xs text-gray-500">
                                        No additional permissions
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </aside>

                    <form class="p-5 sm:p-7" @submit.prevent="submit">
                        <section>
                            <div class="border-b border-gray-200 pb-4">
                                <h2 class="text-base font-bold text-gray-950">Personal information</h2>
                                <p class="mt-1 text-sm text-gray-500">These details identify you throughout the application.</p>
                            </div>

                            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="profile-name" class="block text-sm font-semibold text-gray-700">Full name</label>
                                    <input id="profile-name" v-model="form.name" type="text" autocomplete="name" class="mt-1.5" />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
                                </div>
                                <div>
                                    <label for="profile-username" class="block text-sm font-semibold text-gray-700">Username</label>
                                    <input id="profile-username" v-model="form.username" type="text" autocomplete="username" class="mt-1.5" />
                                    <p v-if="form.errors.username" class="mt-1 text-xs text-red-600">{{ form.errors.username }}</p>
                                </div>
                                <div>
                                    <label for="profile-email" class="block text-sm font-semibold text-gray-700">Email address</label>
                                    <input id="profile-email" v-model="form.email" type="email" autocomplete="email" class="mt-1.5" />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="mt-8">
                            <div class="border-b border-gray-200 pb-4">
                                <h2 class="text-base font-bold text-gray-950">Change password</h2>
                                <p class="mt-1 text-sm text-gray-500">Leave these fields empty to keep your current password.</p>
                            </div>

                            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="current-password" class="block text-sm font-semibold text-gray-700">Current password</label>
                                    <input
                                        id="current-password"
                                        v-model="form.current_password"
                                        type="password"
                                        autocomplete="current-password"
                                        class="mt-1.5"
                                    />
                                    <p v-if="form.errors.current_password" class="mt-1 text-xs text-red-600">{{ form.errors.current_password }}</p>
                                </div>
                                <div>
                                    <label for="new-password" class="block text-sm font-semibold text-gray-700">New password</label>
                                    <input id="new-password" v-model="form.password" type="password" autocomplete="new-password" class="mt-1.5" />
                                    <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                                </div>
                                <div>
                                    <label for="password-confirmation" class="block text-sm font-semibold text-gray-700">Confirm password</label>
                                    <input
                                        id="password-confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        class="mt-1.5"
                                    />
                                </div>
                            </div>
                        </section>

                        <div class="mt-7 flex justify-end border-t border-gray-200 pt-5">
                            <button type="submit" class="btn-primary justify-center" :disabled="form.processing || !form.isDirty">
                                <span
                                    v-if="form.processing"
                                    class="size-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                ></span>
                                {{ form.processing ? 'Saving...' : 'Save changes' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
