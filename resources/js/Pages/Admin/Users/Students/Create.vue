<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import CustomSelect from '@/components/Form/CustomSelect.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

const props = defineProps<{
    classes: any[];
    branches: any[];
    roles: any[];
}>();

const form = useForm({
    name: '',
    email: '',
    school_id: '',
    school_class_id: '',
    role: props.roles.length > 0 ? props.roles[0].name : '',
});

const submit = () => {
    form.post('/admin/users/students');
};
</script>

<template>
    <AdminLayout>
        <Head title="Enroll Candidate" />

        <div class="mx-auto max-w-7xl pb-24">
            <div class="space-y-6 sm:space-y-10">
                <!-- Breadcrumbs -->
                <nav class="flex items-center gap-2 text-xs font-medium text-gray-500">
                    <Link href="/admin/dashboard" class="transition-colors hover:text-primary">Dashboard</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <Link href="/admin/users/students" class="transition-colors hover:text-primary">Candidate Registry</Link>
                    <svg class="size-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span class="text-gray-800 font-medium">New Enrollment</span>
                </nav>

                <!-- Page Header -->
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-gray-800">Enroll New Candidate</h1>
                        <p class="mt-1 text-sm text-gray-500">Register a new student and assign them to a school branch</p>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <Link
                            href="/admin/users/students"
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
                            Complete Enrollment
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Left Column: Personal Data -->
                    <div class="space-y-6 lg:col-span-2">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">1</span>
                                <h2 class="text-lg font-semibold text-gray-800">Personal Information</h2>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-gray-700">Full Legal Name</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Enter full name"
                                        class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                    />
                                    <p v-if="form.errors.name" class="mt-2 text-xs text-red-600">{{ form.errors.name }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Email Address</label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            required
                                            placeholder="candidate@chrisland.org"
                                            class="block w-full rounded-lg border-gray-200 px-4 py-3 text-sm focus:border-primary focus:ring-primary disabled:opacity-50"
                                        />
                                        <p v-if="form.errors.email" class="mt-2 text-xs text-red-600">{{ form.errors.email }}</p>
                                    </div>

                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-gray-700">Admission Number</label>
                                        <div
                                            class="block w-full rounded-lg border border-dashed border-gray-300 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-500"
                                        >
                                            Auto-generated on save (e.g. CHS/{{ new Date().getFullYear() }}/001)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Institutional Placement -->
                    <div class="space-y-6">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="mb-6 flex items-center gap-x-3">
                                <span class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">2</span>
                                <h2 class="text-lg font-semibold text-gray-800">Placement</h2>
                            </div>

                            <div class="space-y-6">
                                <CustomSelect
                                    v-model="form.school_id"
                                    label="School Branch"
                                    :options="branches"
                                    placeholder="Select Location"
                                    size="md"
                                    :error="form.errors.school_id"
                                />

                                <CustomSelect
                                    v-model="form.school_class_id"
                                    label="Academic Class"
                                    :options="classes"
                                    placeholder="Choose Level"
                                    size="md"
                                    :error="form.errors.school_class_id"
                                />

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

                        <!-- Info Card -->
                        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                            <div class="flex gap-3">
                                <svg class="size-5 shrink-0 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-xs leading-relaxed text-blue-800">
                                    Candidates enrolled here will immediately appear in the registry and be eligible for examination assignments within their school branch.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
