<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import {
    Clock,
    Download, FileText
} from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Employee {
    id: number;
    name: string;
    employee_id: string;
}

interface EmployeeSummary {
    id: number;
    employee_id: string;
    name: string;
    department_name: string | null;
    sub_department_name: string | null;
    designation: string | null;
    attendance_count: number;
    late_count: number;
    on_time_count: number;
    total_hours: number;
    avg_hours_per_day: number;
}

interface Props {
    employees: Employee[];
    employeeSummaries: EmployeeSummary[];
    filters: {
        month: number;
        year: number;
    };
    availableYears: number[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/attendance' },
    { title: 'Employee Report', href: '/attendance/employee-report' },
];

const months = [
    { value: 1, label: 'January' },
    { value: 2, label: 'February' },
    { value: 3, label: 'March' },
    { value: 4, label: 'April' },
    { value: 5, label: 'May' },
    { value: 6, label: 'June' },
    { value: 7, label: 'July' },
    { value: 8, label: 'August' },
    { value: 9, label: 'September' },
    { value: 10, label: 'October' },
    { value: 11, label: 'November' },
    { value: 12, label: 'December' },
];

const selectedMonth = ref(String(props.filters.month));
const selectedYear = ref(String(props.filters.year));

const applyFilters = () => {
    const params: Record<string, string> = {
        month: selectedMonth.value,
        year: selectedYear.value,
    };
    router.get('/attendance/employee-report', params, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedMonth, selectedYear], () => applyFilters());

const selectEmployee = (employeeId: number) => {
    router.visit(`/attendance/employee-report/${employeeId}?month=${selectedMonth.value}&year=${selectedYear.value}`);
};

const exportReport = (type: 'csv' | 'xlsx') => {
    const params = new URLSearchParams();
    params.append('month', selectedMonth.value);
    params.append('year', selectedYear.value);
    params.append('type', type);

    window.location.href = `/attendance/employee-report/export?${params.toString()}`;
};

const selectedMonthName = computed(() => {
    const month = months.find(m => m.value === Number(selectedMonth.value));
    return month?.label || '';
});
</script>

<template>
    <Head title="Employee Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <h1 class="text-2xl font-bold">Employee Report</h1>
                <div class="flex flex-wrap gap-2 items-center">
                    <Select v-model="selectedMonth" @update:model-value="applyFilters">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="Select month" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="month in months"
                                :key="month.value"
                                :value="String(month.value)"
                            >
                                {{ month.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedYear" @update:model-value="applyFilters">
                        <SelectTrigger class="w-[100px]">
                            <SelectValue placeholder="Select year" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="year in availableYears"
                                :key="year"
                                :value="String(year)"
                            >
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button>
                                <Download class="mr-2 h-4 w-4" />
                                Export
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem @click="exportReport('csv')">
                                <FileText class="mr-2 h-4 w-4" />
                                Export as CSV
                            </DropdownMenuItem>
                            <DropdownMenuItem @click="exportReport('xlsx')">
                                <FileText class="mr-2 h-4 w-4" />
                                Export as XLSX
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Employee List View -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Employee Attendance
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 text-left font-medium">Employee</th>
                                    <th class="pb-3 text-left font-medium">Department</th>
                                    <th class="pb-3 text-center font-medium">Attendance</th>
                                    <th class="pb-3 text-center font-medium">On Time</th>
                                    <th class="pb-3 text-center font-medium">Late</th>
                                    <th class="pb-3 text-center font-medium">Total Hours</th>
                                    <th class="pb-3 text-center font-medium">Avg/Day</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="employee in employeeSummaries"
                                    :key="employee.id"
                                    class="border-b last:border-0 cursor-pointer hover:bg-muted/50 transition-colors"
                                    @click="selectEmployee(employee.id)"
                                >
                                    <td class="py-3">
                                        <div>
                                            <div class="font-medium">{{ employee.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ employee.employee_id }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div v-if="employee.department_name" class="text-sm">{{ employee.department_name }}</div>
                                        <div v-if="employee.sub_department_name" class="text-xs text-muted-foreground">
                                            {{ employee.sub_department_name }}
                                        </div>
                                    </td>
                                    <td class="py-3 text-center">
                                        <Badge variant="outline">{{ employee.attendance_count }}</Badge>
                                    </td>
                                    <td class="py-3 text-center">
                                        <Badge variant="outline" class="bg-green-50 text-green-700 border-green-200">
                                            {{ employee.on_time_count }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 text-center">
                                        <Badge v-if="employee.late_count > 0" variant="destructive">
                                            {{ employee.late_count }}
                                        </Badge>
                                        <Badge v-else variant="outline" class="text-muted-foreground">0</Badge>
                                    </td>
                                    <td class="py-3 text-center font-medium">
                                        {{ employee.total_hours.toFixed(1) }}h
                                    </td>
                                    <td class="py-3 text-center text-muted-foreground">
                                        {{ employee.avg_hours_per_day.toFixed(1) }}h
                                    </td>
                                </tr>
                                <tr v-if="employeeSummaries.length === 0">
                                    <td colspan="7" class="py-8 text-center text-muted-foreground">
                                        No employees found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
