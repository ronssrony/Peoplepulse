<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';
import { Head, router, Link } from '@inertiajs/vue3';
import { 
    User as UserIcon, Calendar, Clock, 
    Download, FileText, ArrowLeft
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
    department_name: string | null;
    sub_department_name: string | null;
    designation: string | null;
}

interface AttendanceRecord {
    id: number;
    date: string;
    clock_in: string | null;
    clock_out: string | null;
    is_late: boolean;
    net_minutes: number | null;
    gross_minutes: number | null;
    break_minutes: number;
    status: string;
    late_minutes: number | null;
}

interface Summary {
    totalAttendance: number;
    lateCount: number;
    onTimeCount: number;
    totalHours: number;
    avgHoursPerDay: number;
}

interface Props {
    employee: Employee;
    attendances: AttendanceRecord[];
    summary: Summary;
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
    { title: props.employee.name, href: '#' },
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
    router.get(`/attendance/employee-report/${props.employee.id}`, {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedMonth, selectedYear], () => applyFilters());

const exportReport = (type: 'csv' | 'xlsx') => {
    const params = new URLSearchParams();
    params.append('month', selectedMonth.value);
    params.append('year', selectedYear.value);
    params.append('type', type);
    
    window.location.href = `/attendance/employee-report/${props.employee.id}/export?${params.toString()}`;
};

const selectedMonthName = computed(() => {
    const month = months.find(m => m.value === Number(selectedMonth.value));
    return month?.label || '';
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};

const formatTime = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatMinutesToHours = (minutes: number | null) => {
    if (minutes === null) return '-';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
};
</script>

<template>
    <Head :title="`${employee.name} - Attendance Report`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link href="/attendance/employee-report">
                        <Button variant="ghost" size="icon">
                            <ArrowLeft class="h-4 w-4" />
                        </Button>
                    </Link>
                    <h1 class="text-2xl font-bold">Employee Attendance Detail</h1>
                </div>
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

            <!-- Employee Info Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-center gap-4">
                        <div>
                            <CardTitle class="flex items-center gap-2">
                                <UserIcon class="h-5 w-5" />
                                {{ employee.name }}
                            </CardTitle>
                            <CardDescription>
                                {{ employee.employee_id }}
                                <span v-if="employee.designation"> · {{ employee.designation }}</span>
                                <span v-if="employee.department_name"> · {{ employee.department_name }}</span>
                                <span v-if="employee.sub_department_name"> ({{ employee.sub_department_name }})</span>
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-5">
                        <div class="text-center p-3 bg-muted rounded-lg">
                            <div class="text-2xl font-bold">{{ summary.totalAttendance }}</div>
                            <div class="text-xs text-muted-foreground">Total Days</div>
                        </div>
                        <div class="text-center p-3 bg-green-50 dark:bg-green-950/30 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ summary.onTimeCount }}</div>
                            <div class="text-xs text-muted-foreground">On Time</div>
                        </div>
                        <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-950/30 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ summary.lateCount }}</div>
                            <div class="text-xs text-muted-foreground">Late</div>
                        </div>
                        <div class="text-center p-3 bg-blue-50 dark:bg-blue-950/30 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ summary.totalHours.toFixed(1) }}h</div>
                            <div class="text-xs text-muted-foreground">Total Hours</div>
                        </div>
                        <div class="text-center p-3 bg-muted rounded-lg">
                            <div class="text-2xl font-bold">{{ summary.avgHoursPerDay.toFixed(1) }}h</div>
                            <div class="text-xs text-muted-foreground">Avg/Day</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Attendance Records Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Calendar class="h-5 w-5" />
                        Attendance Records - {{ selectedMonthName }} {{ selectedYear }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 text-left font-medium">Date</th>
                                    <th class="pb-3 text-left font-medium">Clock In</th>
                                    <th class="pb-3 text-left font-medium">Clock Out</th>
                                    <th class="pb-3 text-left font-medium">Net Hours</th>
                                    <th class="pb-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="record in attendances"
                                    :key="record.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 font-medium">{{ formatDate(record.date) }}</td>
                                    <td class="py-3">{{ formatTime(record.clock_in) }}</td>
                                    <td class="py-3">{{ formatTime(record.clock_out) }}</td>
                                    <td class="py-3">{{ formatMinutesToHours(record.net_minutes) }}</td>
                                    <td class="py-3">
                                        <Badge v-if="record.is_late" variant="destructive">Late</Badge>
                                        <Badge v-else variant="outline" class="bg-green-50 text-green-700 border-green-200">On Time</Badge>
                                    </td>
                                </tr>
                                <tr v-if="attendances.length === 0">
                                    <td colspan="5" class="py-8 text-center text-muted-foreground">
                                        No attendance records found for this period.
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
