<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SubDepartment {
    id: number;
    name: string;
}

interface Department {
    id: number;
    name: string;
    subDepartments: SubDepartment[];
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
}

interface Props {
    employee: Employee;
    departments: Department[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Management', href: '/employees' },
    { title: 'Edit Employee', href: `/employees/${props.employee.id}/edit` },
];

const managedSubDeptIds = props.employee.managedSubDepartments?.map(sd => sd.id) || [];

const form = useForm({
    employee_id: props.employee.employee_id,
    name: props.employee.name,
    department_id: props.employee.department_id,
    sub_department_id: props.employee.sub_department_id,
    designation: props.employee.designation,
    role: props.employee.role,
    weekend_days: [...props.employee.weekend_days],
    managed_sub_departments: [...managedSubDeptIds],
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
    return selectedDepartment.value?.subDepartments || [];
});

const toggleWeekendDay = (day: string) => {
    const index = form.weekend_days.indexOf(day);
    if (index > -1) {
        form.weekend_days.splice(index, 1);
    } else {
        form.weekend_days.push(day);
    }
};

const toggleManagedSubDept = (subDeptId: number) => {
    const index = form.managed_sub_departments.indexOf(subDeptId);
    if (index > -1) {
        form.managed_sub_departments.splice(index, 1);
    } else {
        form.managed_sub_departments.push(subDeptId);
    }
};

const submit = () => {
    form.put(`/employees/${props.employee.id}`, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit Employee" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold">Edit Employee</h1>
                <p class="text-muted-foreground">Update employee information</p>
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
                                <Label for="email">Email (Read-only)</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    :value="employee.email"
                                    disabled
                                    class="opacity-60"
                                />
                                <p class="text-xs text-muted-foreground">Email cannot be changed</p>
                            </div>
                            <div class="space-y-2">
                                <Label for="password">Password (Read-only)</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value="********"
                                    disabled
                                    class="opacity-60"
                                />
                                <p class="text-xs text-muted-foreground">Password cannot be changed here</p>
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
                                    <Checkbox
                                        :id="option.value"
                                        :checked="form.weekend_days.includes(option.value)"
                                        @update:checked="toggleWeekendDay(option.value)"
                                    />
                                    <Label :for="option.value" class="cursor-pointer">{{ option.label }}</Label>
                                </div>
                            </div>
                            <p v-if="form.errors.weekend_days" class="mt-2 text-sm text-destructive">{{ form.errors.weekend_days }}</p>
                        </CardContent>
                    </Card>

                    <!-- Manager Sub-Departments (only show for managers) -->
                    <Card v-if="form.role === 'manager'">
                        <CardHeader>
                            <CardTitle>Managed Sub-Departments</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div class="grid gap-3 md:grid-cols-3">
                                <div
                                    v-for="dept in departments"
                                    :key="dept.id"
                                    class="space-y-2"
                                >
                                    <div class="font-medium text-sm">{{ dept.name }}</div>
                                    <div v-for="subDept in dept.subDepartments" :key="subDept.id" class="flex items-center space-x-2 ml-4">
                                        <Checkbox
                                            :id="`managed-${subDept.id}`"
                                            :checked="form.managed_sub_departments.includes(subDept.id)"
                                            @update:checked="toggleManagedSubDept(subDept.id)"
                                        />
                                        <Label :for="`managed-${subDept.id}`" class="cursor-pointer text-sm">{{ subDept.name }}</Label>
                                    </div>
                                </div>
                            </div>
                            <p v-if="form.errors.managed_sub_departments" class="mt-2 text-sm text-destructive">{{ form.errors.managed_sub_departments }}</p>
                        </CardContent>
                    </Card>

                    <!-- Actions -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            Update Employee
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
