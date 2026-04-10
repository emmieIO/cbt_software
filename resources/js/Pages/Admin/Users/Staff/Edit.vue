<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps<{
    staff: any;
    branches: any[];
    roles: any[];
}>();

const form = useForm({
    name: props.staff.name,
    email: props.staff.email,
    school_ids: props.staff.schools.map((s: any) => s.id) as string[],
    primary_school_id: props.staff.schools.find((s: any) => s.pivot.is_primary)?.id || '',
    role: props.staff.roles.length > 0 ? props.staff.roles[0].name : 'examiner',
});

const toggleBranch = (id: string) => {
    const index = form.school_ids.indexOf(id);
    if (index === -1) {
        form.school_ids.push(id);
        if (!form.primary_school_id) form.primary_school_id = id;
    } else {
        form.school_ids.splice(index, 1);
        if (form.primary_school_id === id) {
            form.primary_school_id = form.school_ids.length > 0 ? form.school_ids[0] : '';
        }
    }
};

const submit = () => {
    form.put(`/admin/users/staff/${props.staff.id}`);
};
</script>

<template>
    <AdminLayout>
        <Head title="Modify Personnel" />

        <div class="mx-auto max-w-7xl pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link href="/admin/users/staff" class="transition-colors hover:text-primary">Staff Directory</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-800 font-medium">Edit Profile</span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Edit Personnel Details</h1>
                        <p class="mt-1 text-sm text-gray-500">Ref #{{ staff.id.substring(0, 8) }} • Update institutional profile and access scope</p>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <Link
                            href="/admin/users/staff"
                            class="inline-flex items-center gap-x-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-none"
                        >
                            Cancel
                        </Link>
                        <button
                            @click="submit"
                            :disabled="form.processing"
                            class="hover:bg-primary-hover inline-flex items-center gap-x-2 rounded-lg border border-transparent bg-primary px-4 py-2.5 text-sm font-semibold text-white focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                            <span v-if="form.processing" class="loading loading-spinner loading-xs"></span>
                            Save Modifications
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left Column: Identity Data -->
                    <div class="space-y-6 lg:col-span-2">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">1</span>
                                <h2 class="text-lg font-semibold text-gray-800">Personal Identity</h2>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Full Name</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="e.g. Dr. Jane Smith"
                                        class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    />
                                    <p v-if="form.errors.name" class="mt-2 text-xs text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Work Email</label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            required
                                            placeholder="j.smith@chrisland.org"
                                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                        />
                                        <p v-if="form.errors.email" class="mt-2 text-xs text-red-600">{{ form.errors.email }}</p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Portal Username</label>
                                        <div class="rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3">
                                            <p class="text-sm font-semibold text-gray-700">{{ staff.username }}</p>
                                            <p class="mt-1 text-xs text-gray-500">System-generated identifier. This value is locked after creation.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 02. Institutional Scope -->
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">2</span>
                                <h2 class="text-lg font-semibold text-gray-800">Branch Assignments</h2>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div
                                    v-for="branch in branches"
                                    :key="branch.id"
                                    @click="toggleBranch(branch.id)"
                                    class="group flex cursor-pointer items-center justify-between rounded-xl border-2 p-4 transition-all"
                                    :class="
                                        form.school_ids.includes(branch.id)
                                            ? 'border-primary bg-primary/5'
                                            : 'border-gray-100 bg-gray-50 hover:border-gray-200'
                                    "
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex size-5 items-center justify-center rounded border transition-all"
                                            :class="
                                                form.school_ids.includes(branch.id)
                                                    ? 'border-primary bg-primary text-white'
                                                    : 'border-gray-300 bg-white'
                                            "
                                        >
                                            <svg v-if="form.school_ids.includes(branch.id)" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700">{{ branch.name }}</span>
                                    </div>
                                    <span v-if="form.primary_school_id === branch.id" class="rounded bg-primary px-1.5 py-0.5 text-[8px] font-black tracking-widest text-white uppercase">Primary</span>
                                </div>
                            </div>
                            <p v-if="form.errors.school_ids" class="mt-4 text-xs text-red-600">{{ form.errors.school_ids }}</p>

                            <div v-if="form.school_ids.length > 1" class="mt-8">
                                <CustomSelect
                                    v-model="form.primary_school_id"
                                    label="Designated Primary Branch"
                                    :options="branches.filter(b => form.school_ids.includes(b.id))"
                                    placeholder="Select Primary"
                                    size="md"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Role Assignment -->
                    <div class="space-y-6">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">3</span>
                                <h2 class="text-lg font-semibold text-gray-800">Authorization</h2>
                            </div>

                            <div class="space-y-3">
                                <label class="block text-sm font-medium text-gray-700">Access Role</label>
                                <div class="grid grid-cols-1 gap-2">
                                    <button
                                        v-for="role in roles"
                                        :key="role.id"
                                        type="button"
                                        @click="form.role = role.name"
                                        class="flex items-center justify-between rounded-lg border px-4 py-3 text-sm font-medium transition-all focus:outline-none"
                                        :class="
                                            form.role === role.name
                                                ? 'border-transparent bg-slate-900 text-white shadow-lg'
                                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'
                                        "
                                    >
                                        <span class="capitalize">{{ role.name.replace('_', ' ') }}</span>
                                        <div v-if="form.role === role.name" class="size-2 rounded-full bg-lemon-yellow"></div>
                                    </button>
                                </div>
                                <p v-if="form.errors.role" class="mt-2 text-xs text-red-600">{{ form.errors.role }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
