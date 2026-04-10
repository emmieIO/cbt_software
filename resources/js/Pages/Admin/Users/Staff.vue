<script setup lang="ts">
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { index, destroy } from '@/actions/App/Http/Controllers/Admin/StaffController';
import ConfirmationModal from '@/components/ConfirmationModal.vue';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { PaginatedData } from '@/types/academics';

interface StaffUser {
    id: string;
    name: string;
    email: string;
    username: string;
    school_id: string | null;
    roles: Array<{ name: string }>;
    schools?: Array<{ id: string; name: string }>;
}

const props = defineProps<{
    staff: PaginatedData<StaffUser>;
    branches: any[];
    usersWithoutRolesCount: number;
    filters: {
        search?: string;
        school_id?: string;
    };
}>();

const isDeleteModalOpen = ref(false);
const staffToDelete = ref<StaffUser | null>(null);

const confirmDelete = (user: StaffUser) => {
    staffToDelete.value = user;
    isDeleteModalOpen.value = true;
};

const handleDelete = () => {
    if (staffToDelete.value) {
        router.delete(destroy(staffToDelete.value.id).url, {
            onSuccess: () => {
                isDeleteModalOpen.value = false;
                staffToDelete.value = null;
            },
        });
    }
};

// Search & Filter
const search = ref(props.filters.search || '');
const schoolFilter = ref(props.filters.school_id || '');

const applyFilters = () => {
    router.get(
        index().url,
        {
            search: search.value,
            school_id: schoolFilter.value,
        },
        { preserveState: true },
    );
};

const clearFilters = () => {
    search.value = '';
    schoolFilter.value = '';
    applyFilters();
};
</script>

<template>
    <AdminLayout>
        <Head title="Staff Management" />

        <div class="space-y-6 sm:space-y-10 pb-24">
            <!-- Breadcrumbs -->
            <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-800">Personnel</span>
            </nav>

            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800">Staff Directory</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ staff.total }} academic & administrative staff records</p>
                </div>
                <div class="flex items-center gap-x-2">
                    <Link
                        href="/admin/users/staff/create"
                        class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                    >
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Personnel
                    </Link>
                </div>
            </div>

            <div
                v-if="usersWithoutRolesCount > 0"
                class="rounded-xl border border-amber-200 bg-amber-50 p-6 shadow-sm"
            >
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold tracking-wider text-amber-900 uppercase">Archived Access Records</h2>
                        <p class="mt-2 text-sm leading-6 text-amber-800">
                            {{ usersWithoutRolesCount }} user{{ usersWithoutRolesCount === 1 ? '' : 's' }} currently have no assigned role. They remain safe in the database, and you can reassign them from the dedicated recovery queue without crowding this directory.
                        </p>
                    </div>
                    <span class="inline-flex items-center rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-semibold tracking-wider text-amber-900 uppercase">
                        Needs Reassignment
                    </span>
                </div>
                <div class="mt-4">
                    <Link
                        href="/admin/users/access-recovery"
                        class="inline-flex items-center rounded-lg border border-amber-300 bg-white px-4 py-2 text-sm font-medium text-amber-900 hover:bg-amber-100"
                    >
                        Open Access Recovery
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-12 md:items-end">
                    <div class="md:col-span-6">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Search Directory</label>
                        <div class="relative">
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Search by name, email, or Staff ID..."
                                class="block w-full rounded-lg border-gray-200 py-3 pl-10 text-sm focus:border-primary focus:ring-primary"
                            />
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <CustomSelect
                            v-model="schoolFilter"
                            label="Filter by Branch"
                            :options="branches"
                            placeholder="All Branches"
                            size="md"
                            @change="applyFilters"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <button
                            @click="clearFilters"
                            class="inline-flex w-full items-center justify-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-3 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="flex flex-col">
                <div class="-m-1.5 overflow-x-auto">
                    <div class="inline-block min-w-full p-1.5 align-middle">
                        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">Staff Member</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">Authorization Role</th>
                                        <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">Institutional ID</th>
                                        <th scope="col" class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="user in staff.data" :key="user.id" class="transition-colors hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-x-3">
                                                <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-xs font-black text-primary">
                                                    {{ user.name.substring(0, 2).toUpperCase() }}
                                                </div>
                                                <div>
                                                    <span class="block text-sm font-semibold text-gray-800">{{ user.name }}</span>
                                                    <span class="block text-xs text-gray-500">{{ user.email }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                <span
                                                    v-for="role in user.roles"
                                                    :key="role.name"
                                                    class="inline-flex items-center rounded-md bg-slate-900 px-2 py-1 text-[10px] font-black tracking-widest text-white uppercase shadow-sm"
                                                >
                                                    {{ role.name.replace('_', ' ') }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-600">
                                            <span class="bg-slate-100 px-2 py-1 rounded text-[10px] font-black tracking-widest uppercase">
                                                {{ user.username }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-end">
                                            <div class="flex items-center justify-end gap-x-2">
                                                <Link
                                                    :href="`/admin/users/staff/${user.id}/edit`"
                                                    class="text-gray-500 transition-colors hover:text-primary focus:outline-none"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </Link>
                                                <button
                                                    @click="confirmDelete(user)"
                                                    class="text-gray-500 transition-colors hover:text-red-500 focus:outline-none"
                                                >
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="staff.data.length === 0">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                            <p class="text-sm">No personnel records found</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            <div v-if="staff.total > staff.per_page" class="grid gap-3 border-t border-gray-200 px-6 py-4 md:flex md:items-center md:justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">
                                        Showing <span class="font-semibold text-gray-800">{{ staff.from }}</span> to
                                        <span class="font-semibold text-gray-800">{{ staff.to }}</span> of
                                        <span class="font-semibold text-gray-800">{{ staff.total }}</span>
                                    </p>
                                </div>

                                <div class="inline-flex gap-x-2">
                                    <Link
                                        v-for="link in staff.links"
                                        :key="link.label"
                                        :href="link.url || '#'"
                                        class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                                        :class="[link.active ? 'bg-gray-100' : '', !link.url && 'pointer-events-none opacity-50']"
                                    >
                                        <span v-html="link.label" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="isDeleteModalOpen"
            title="Delete Personnel Record?"
            :message="`Are you sure you want to permanently delete the account for ${staffToDelete?.name}? This action cannot be undone.`"
            confirm-label="Delete Permanent"
            variant="danger"
            @close="isDeleteModalOpen = false"
            @confirm="handleDelete"
        />
    </AdminLayout>
</template>
