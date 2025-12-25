<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem, PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Edit, Trash2, UserCog } from 'lucide-vue-next';

interface Department {
    id: number;
    name: string;
}

interface SubDepartment {
    id: number;
    name: string;
}

interface Employee {
    id: number;
    employee_id: string;
    name: string;
    email: string;
    department_id: number | null;
    sub_department_id: number | null;
    department?: Department;
    subDepartment?: SubDepartment;
    designation: string;
    role: 'admin' | 'manager' | 'user';
    managedSubDepartments?: SubDepartment[];
    created_at: string;
}

interface Props {
    employees: PaginatedData<Employee>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Employee Management', href: '/employees' },
];

const getRoleBadgeVariant = (role: string) => {
    switch (role) {
        case 'admin':
            return 'destructive';
        case 'manager':
            return 'default';
        default:
            return 'secondary';
    }
};

const deleteEmployee = (employeeId: number) => {
    if (confirm('Are you sure you want to delete this employee?')) {
        router.delete(`/employees/${employeeId}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Head title="Employee Management" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Employee Management</h1>
                    <p class="text-muted-foreground">Manage users, roles, and departments</p>
                </div>
                <Button as-child>
                    <Link href="/employees/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Employee
                    </Link>
                </Button>
            </div>

            <!-- Employee Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserCog class="h-5 w-5" />
                        All Employees
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 text-left font-medium">Employee ID</th>
                                    <th class="pb-3 text-left font-medium">Name</th>
                                    <th class="pb-3 text-left font-medium">Email</th>
                                    <th class="pb-3 text-left font-medium">Department</th>
                                    <th class="pb-3 text-left font-medium">Designation</th>
                                    <th class="pb-3 text-left font-medium">Role</th>
                                    <th class="pb-3 text-left font-medium">Managed Sub-Depts</th>
                                    <th class="pb-3 text-left font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="employee in employees.data"
                                    :key="employee.id"
                                    class="border-b last:border-0 transition-colors hover:bg-muted/50"
                                >
                                    <td class="py-3">
                                        <span class="font-mono text-sm">{{ employee.employee_id }}</span>
                                    </td>
                                    <td class="py-3">
                                        <div class="font-medium">{{ employee.name }}</div>
                                    </td>
                                    <td class="py-3 text-sm text-muted-foreground">
                                        {{ employee.email }}
                                    </td>
                                    <td class="py-3">
                                        <div v-if="employee.department" class="text-sm">
                                            {{ employee.department.name }}
                                        </div>
                                        <div v-if="employee.subDepartment" class="text-xs text-muted-foreground">
                                            {{ employee.subDepartment.name }}
                                        </div>
                                    </td>
                                    <td class="py-3 text-sm">
                                        {{ employee.designation }}
                                    </td>
                                    <td class="py-3">
                                        <Badge :variant="getRoleBadgeVariant(employee.role)" class="text-xs capitalize">
                                            {{ employee.role }}
                                        </Badge>
                                    </td>
                                    <td class="py-3">
                                        <div v-if="employee.role === 'manager' && employee.managedSubDepartments && employee.managedSubDepartments.length > 0" class="text-xs">
                                            <div v-for="subDept in employee.managedSubDepartments" :key="subDept.id" class="text-muted-foreground">
                                                • {{ subDept.name }}
                                            </div>
                                        </div>
                                        <span v-else class="text-xs text-muted-foreground">-</span>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                as-child
                                            >
                                                <Link :href="`/employees/${employee.id}/edit`">
                                                    <Edit class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="deleteEmployee(employee.id)"
                                                class="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="employees.data.length === 0">
                                    <td colspan="8" class="py-8 text-center text-muted-foreground">
                                        No employees found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="employees.data.length > 0" class="mt-4 flex items-center justify-between border-t pt-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ employees.from }} to {{ employees.to }} of {{ employees.total }} results
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in employees.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 text-sm rounded',
                                    link.active ? 'bg-primary text-primary-foreground' : 'bg-muted hover:bg-muted/80',
                                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                                v-html="link.label"
                                :preserve-scroll="true"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
