<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { ChevronDown, ChevronUp } from 'lucide-vue-next';

interface SubDepartment {
    id: number;
    name: string;
}

interface Department {
    id: number;
    name: string;
    sub_departments: SubDepartment[];
}

interface Employee {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    department_id: number | null;
    sub_department_id: number | null;
    designation: string;
    role: 'user' | 'manager' | 'admin';
    weekend_days: string[];
    managedSubDepartments?: SubDepartment[];
    // Personal Information
    nid_number?: string | null;
    joining_date?: string | null;
    closing_date?: string | null;
    permanent_address?: string | null;
    present_address?: string | null;
    nationality?: string | null;
    fathers_name?: string | null;
    mothers_name?: string | null;
    graduated_institution?: string | null;
}

interface Props {
    employee?: Employee | null;
    departments: Department[];
}

const props = defineProps<Props>();

const isEditMode = computed(() => !!props.employee);
const isPersonalInfoOpen = ref(false);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Management', href: '/employees' },
    { title: isEditMode.value ? 'Edit Employee' : 'Create Employee', href: isEditMode.value ? `/employees/${props.employee?.id}/edit` : '/employees/create' },
]);

// Use separate ref for weekend days to work with native checkboxes
const selectedWeekendDays = ref<string[]>(props.employee?.weekend_days ? [...props.employee.weekend_days] : []);

const form = useForm({
    employee_id: props.employee?.employee_id || '',
    name: props.employee?.name || '',
    email: props.employee?.email || '',
    password: '',
    department_id: props.employee?.department_id || null as number | null,
    sub_department_id: props.employee?.sub_department_id || null as number | null,
    designation: props.employee?.designation || '',
    role: props.employee?.role || 'user' as 'user' | 'manager' | 'admin',
    weekend_days: [] as string[],
    // Personal Information
    nid_number: props.employee?.nid_number || '',
    joining_date: props.employee?.joining_date || '',
    closing_date: props.employee?.closing_date || '',
    permanent_address: props.employee?.permanent_address || '',
    present_address: props.employee?.present_address || '',
    nationality: props.employee?.nationality || '',
    fathers_name: props.employee?.fathers_name || '',
    mothers_name: props.employee?.mothers_name || '',
    graduated_institution: props.employee?.graduated_institution || '',
});

const weekendOptions = [
    { value: 'friday', label: 'Friday' },
    { value: 'saturday', label: 'Saturday' },
    { value: 'sunday', label: 'Sunday' },
];

const selectedDepartment = computed(() => {
    return props.departments.find(d => d.id === form.department_id);
});

const availableSubDepartments = computed(() => {
    return selectedDepartment.value?.sub_departments || [];
});

const submit = () => {
    form.weekend_days = selectedWeekendDays.value;

    if (isEditMode.value) {
        form.put(`/employees/${props.employee!.id}`, {
            preserveScroll: true,
        });
    } else {
        form.post('/employees', {
            preserveScroll: true,
        });
    }
};

// Watch department changes and reset sub_department if not available
watch(() => form.department_id, (newDeptId, oldDeptId) => {
    if (newDeptId !== oldDeptId) {
        const isValid = availableSubDepartments.value.some(sd => sd.id === form.sub_department_id);
        if (!isValid) {
            form.sub_department_id = null;
        }
    }
});
</script>

<template>
    <Head :title="isEditMode ? 'Edit Employee' : 'Create Employee'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">{{ isEditMode ? 'Edit Employee' : 'Create Employee' }}</h1>
                <p class="text-muted-foreground">{{ isEditMode ? 'Update employee information' : 'Add a new employee to the system' }}</p>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-6">
                    <!-- Personal Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Personal Information</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="employee_id">Employee ID *</Label>
                                <Input
                                    id="employee_id"
                                    v-model="form.employee_id"
                                    required
                                    placeholder="EMP001"
                                />
                                <p v-if="form.errors.employee_id" class="text-sm text-destructive">{{ form.errors.employee_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="name">Full Name *</Label>
                                <Input
                                    id="name"
                                    v-model="form.name"
                                    required
                                    placeholder="John Doe"
                                />
                                <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="email">Email <span v-if="!isEditMode">*</span><span v-else>(Read-only)</span></Label>
                                <Input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    :required="!isEditMode"
                                    :disabled="isEditMode"
                                    :class="{ 'opacity-60': isEditMode }"
                                    placeholder="john@example.com"
                                />
                                <p v-if="isEditMode" class="text-xs text-muted-foreground">Email cannot be changed</p>
                                <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="password">Password <span v-if="!isEditMode">*</span><span v-else>(Read-only)</span></Label>
                                <Input
                                    v-if="!isEditMode"
                                    id="password"
                                    type="password"
                                    v-model="form.password"
                                    required
                                    placeholder="********"
                                />
                                <Input
                                    v-else
                                    id="password"
                                    type="password"
                                    value="********"
                                    disabled
                                    class="opacity-60"
                                />
                                <p v-if="isEditMode" class="text-xs text-muted-foreground">Password cannot be changed here</p>
                                <p v-if="form.errors.password" class="text-sm text-destructive">{{ form.errors.password }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Additional Personal Details (Collapsible) -->
                    <Card>
                        <CardHeader 
                            class="cursor-pointer select-none"
                            @click="isPersonalInfoOpen = !isPersonalInfoOpen"
                        >
                            <div class="flex items-center justify-between">
                                <CardTitle>Additional Personal Details</CardTitle>
                                <ChevronDown v-if="!isPersonalInfoOpen" class="h-5 w-5 text-muted-foreground" />
                                <ChevronUp v-else class="h-5 w-5 text-muted-foreground" />
                            </div>
                            <p class="text-sm text-muted-foreground">Optional fields for detailed employee records</p>
                        </CardHeader>
                        <CardContent v-show="isPersonalInfoOpen" class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="nid_number">NID Number</Label>
                                <Input
                                    id="nid_number"
                                    v-model="form.nid_number"
                                    placeholder="National ID Number"
                                />
                                <p v-if="form.errors.nid_number" class="text-sm text-destructive">{{ form.errors.nid_number }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="nationality">Nationality</Label>
                                <Input
                                    id="nationality"
                                    v-model="form.nationality"
                                    placeholder="e.g., Bangladeshi"
                                />
                                <p v-if="form.errors.nationality" class="text-sm text-destructive">{{ form.errors.nationality }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="joining_date">Joining Date</Label>
                                <Input
                                    id="joining_date"
                                    type="date"
                                    v-model="form.joining_date"
                                />
                                <p v-if="form.errors.joining_date" class="text-sm text-destructive">{{ form.errors.joining_date }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="closing_date">Closing Date</Label>
                                <Input
                                    id="closing_date"
                                    type="date"
                                    v-model="form.closing_date"
                                />
                                <p v-if="form.errors.closing_date" class="text-sm text-destructive">{{ form.errors.closing_date }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="fathers_name">Father's Name</Label>
                                <Input
                                    id="fathers_name"
                                    v-model="form.fathers_name"
                                    placeholder="Father's full name"
                                />
                                <p v-if="form.errors.fathers_name" class="text-sm text-destructive">{{ form.errors.fathers_name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="mothers_name">Mother's Name</Label>
                                <Input
                                    id="mothers_name"
                                    v-model="form.mothers_name"
                                    placeholder="Mother's full name"
                                />
                                <p v-if="form.errors.mothers_name" class="text-sm text-destructive">{{ form.errors.mothers_name }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="graduated_institution">Graduated Institution</Label>
                                <Input
                                    id="graduated_institution"
                                    v-model="form.graduated_institution"
                                    placeholder="University/College name"
                                />
                                <p v-if="form.errors.graduated_institution" class="text-sm text-destructive">{{ form.errors.graduated_institution }}</p>
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label for="permanent_address">Permanent Address</Label>
                                <textarea
                                    id="permanent_address"
                                    v-model="form.permanent_address"
                                    rows="2"
                                    class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="Permanent address"
                                ></textarea>
                                <p v-if="form.errors.permanent_address" class="text-sm text-destructive">{{ form.errors.permanent_address }}</p>
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label for="present_address">Present Address</Label>
                                <textarea
                                    id="present_address"
                                    v-model="form.present_address"
                                    rows="2"
                                    class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                    placeholder="Present address"
                                ></textarea>
                                <p v-if="form.errors.present_address" class="text-sm text-destructive">{{ form.errors.present_address }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Job Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Job Details</CardTitle>
                        </CardHeader>
                        <CardContent class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="department_id">Department</Label>
                                <select
                                    id="department_id"
                                    v-model.number="form.department_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option :value="null">No Department</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.department_id" class="text-sm text-destructive">{{ form.errors.department_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="sub_department_id">Sub-Department</Label>
                                <select
                                    id="sub_department_id"
                                    v-model.number="form.sub_department_id"
                                    :disabled="!form.department_id"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50"
                                >
                                    <option :value="null">No Sub-Department</option>
                                    <option v-for="subDept in availableSubDepartments" :key="subDept.id" :value="subDept.id">
                                        {{ subDept.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.sub_department_id" class="text-sm text-destructive">{{ form.errors.sub_department_id }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="designation">Designation *</Label>
                                <Input
                                    id="designation"
                                    v-model="form.designation"
                                    required
                                    placeholder="Software Engineer"
                                />
                                <p v-if="form.errors.designation" class="text-sm text-destructive">{{ form.errors.designation }}</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="role">Role *</Label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                                >
                                    <option value="user">User</option>
                                    <option value="manager">Manager</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <p v-if="form.errors.role" class="text-sm text-destructive">{{ form.errors.role }}</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Weekend Days -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Weekend Days *</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="flex flex-wrap gap-4">
                                <div v-for="option in weekendOptions" :key="option.value" class="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        :id="option.value"
                                        :value="option.value"
                                        v-model="selectedWeekendDays"
                                        class="h-4 w-4 rounded border-gray-300"
                                    />
                                    <Label :for="option.value" class="cursor-pointer">{{ option.label }}</Label>
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-muted-foreground">Select at least one weekend day</p>
                            <p v-if="form.errors.weekend_days" class="mt-2 text-sm text-destructive">{{ form.errors.weekend_days }}</p>
                        </CardContent>
                    </Card>

                    <!-- Manager Info Card -->
                    <Card v-if="form.role === 'manager'">
                        <CardHeader>
                            <CardTitle>Manager Responsibilities</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-2">
                                <ul class="text-sm text-muted-foreground list-disc list-inside space-y-1">
                                    <li v-if="!form.department_id">Please assign a department to this manager.</li>
                                    <li v-else-if="form.sub_department_id">
                                        This manager will manage only the <strong class="text-foreground">{{ availableSubDepartments.find(sd => sd.id === form.sub_department_id)?.name || 'selected sub-department' }}</strong>.
                                    </li>
                                    <li v-else-if="availableSubDepartments.length > 0">
                                        This manager will manage <strong class="text-foreground">all sub-departments</strong> in the <strong class="text-foreground">{{ departments.find(d => d.id === form.department_id)?.name }}</strong> department:
                                        <ul class="ml-6 mt-1">
                                            <li v-for="subDept in availableSubDepartments" :key="subDept.id">• {{ subDept.name }}</li>
                                        </ul>
                                    </li>
                                    <li v-else>
                                        This manager will manage the <strong class="text-foreground">{{ departments.find(d => d.id === form.department_id)?.name }}</strong> department (no sub-departments available).
                                    </li>
                                </ul>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            {{ isEditMode ? 'Update Employee' : 'Create Employee' }}
                        </Button>
                        <Button type="button" variant="outline" as-child>
                            <a href="/employees">Cancel</a>
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
