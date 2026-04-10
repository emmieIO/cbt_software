<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { reactive } from 'vue';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface RecoveryUser {
    id: string;
    name: string;
    email: string | null;
    username: string | null;
    status: string | null;
    suggested_category: string;
    school: { id: string; name: string } | null;
    school_class: { id: string; name: string } | null;
}

interface RecoveryRole {
    id: number;
    name: string;
    category: string;
}

const props = defineProps<{
    users: PaginatedData<RecoveryUser>;
    roles: RecoveryRole[];
    branches: Array<{ id: string; name: string }>;
    filters: {
        search?: string;
        school_id?: string;
    };
}>();

const filterForm = reactive({
    search: props.filters.search || '',
    school_id: props.filters.school_id || '',
});

const pendingRoles = reactive<Record<string, string>>(
    Object.fromEntries(
        props.users.data.map((user) => [
            user.id,
            props.roles.find((role) => role.category === user.suggested_category)?.name || '',
        ]),
    ),
);

const processingUsers = reactive<Record<string, boolean>>({});

const applyFilters = debounce(() => {
    router.get('/admin/users/access-recovery', filterForm, {
        preserveState: true,
        replace: true,
    });
}, 300);

const clearFilters = () => {
    filterForm.search = '';
    filterForm.school_id = '';
    router.get('/admin/users/access-recovery', {}, { preserveState: true, replace: true });
};

const roleOptionsFor = (user: RecoveryUser) => {
    return props.roles
        .filter((role) => role.category === user.suggested_category)
        .map((role) => ({
            id: role.name,
            name: role.name.replace('_', ' '),
            badge: role.category,
        }));
};

const reinstateUser = (user: RecoveryUser) => {
    const role = pendingRoles[user.id];
    if (!role) return;

    processingUsers[user.id] = true;
    router.patch(
        `/admin/users/access-recovery/${user.id}`,
        { role },
        {
            preserveScroll: true,
            onFinish: () => {
                processingUsers[user.id] = false;
            },
        },
    );
};
</script>

<template>
    <AdminLayout>
        <Head title="Access Recovery" />

        <div class="space-y-6 sm:space-y-10 pb-24">
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Access Recovery</span>
            </nav>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Access Recovery</h1>
                    <p class="mt-1 text-sm text-gray-500">Reassign roles to staff or students who still exist in the database but no longer have platform access.</p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                    {{ users.total }} user{{ users.total === 1 ? '' : 's' }} currently need reassignment
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-7">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search Recovery Queue</label>
                        <input
                            v-model="filterForm.search"
                            @input="applyFilters"
                            type="text"
                            placeholder="Search by name, username, or email..."
                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary"
                        />
                    </div>

                    <div class="md:col-span-3">
                        <CustomSelect
                            v-model="filterForm.school_id"
                            label="Branch"
                            :options="branches"
                            placeholder="All Branches"
                            size="md"
                            @change="applyFilters"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <button
                            @click="clearFilters"
                            class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid gap-4">
                <div
                    v-for="user in users.data"
                    :key="user.id"
                    class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                        <div class="space-y-3">
                            <div>
                                <p class="text-base font-semibold text-gray-800">{{ user.name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ user.email || 'No email address' }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2 text-[11px] font-semibold tracking-wider uppercase">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-700">
                                    {{ user.username || 'No username' }}
                                </span>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-blue-700">
                                    Suggested {{ user.suggested_category }}
                                </span>
                                <span v-if="user.status" class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700">
                                    {{ user.status }}
                                </span>
                            </div>

                            <p class="text-sm text-gray-500">
                                {{ user.school?.name || 'No branch assigned' }}<span v-if="user.school_class"> • {{ user.school_class.name }}</span>
                            </p>
                        </div>

                        <div class="grid gap-3 xl:min-w-[360px] xl:grid-cols-[minmax(0,1fr)_auto]">
                            <CustomSelect
                                v-model="pendingRoles[user.id]"
                                :options="roleOptionsFor(user)"
                                label="Reassign Role"
                                placeholder="Choose role"
                                size="md"
                            />
                            <button
                                @click="reinstateUser(user)"
                                :disabled="!pendingRoles[user.id] || processingUsers[user.id]"
                                class="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white hover:bg-primary/90 disabled:pointer-events-none disabled:opacity-50"
                            >
                                {{ processingUsers[user.id] ? 'Saving...' : 'Reinstate Access' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    v-if="users.data.length === 0"
                    class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-6 py-12 text-center text-sm text-gray-500"
                >
                    No role-less users found for the current filter.
                </div>
            </div>

            <div v-if="users.total > users.per_page" class="inline-flex gap-x-2">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    :class="[link.active ? 'border-transparent bg-primary text-white hover:bg-primary' : '', !link.url && 'pointer-events-none opacity-50']"
                >
                    <span v-html="link.label" />
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>
