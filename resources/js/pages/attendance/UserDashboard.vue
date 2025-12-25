<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Attendance, BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

interface Props {
    attendances: Attendance[];
    filters: {
        month: number;
        year: number;
    };
    availableYears: number[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'My Attendance', href: '/attendance' },
];

const selectedMonth = ref(props.filters.month.toString());
const selectedYear = ref(props.filters.year.toString());

const months = [
    { value: '1', label: 'January' },
    { value: '2', label: 'February' },
    { value: '3', label: 'March' },
    { value: '4', label: 'April' },
    { value: '5', label: 'May' },
    { value: '6', label: 'June' },
    { value: '7', label: 'July' },
    { value: '8', label: 'August' },
    { value: '9', label: 'September' },
    { value: '10', label: 'October' },
    { value: '11', label: 'November' },
    { value: '12', label: 'December' },
];

const selectedMonthLabel = computed(() => {
    const month = months.find(m => m.value === selectedMonth.value);
    return month?.label || '';
});

const applyFilters = () => {
    router.get('/attendance', {
        month: selectedMonth.value,
        year: selectedYear.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

watch([selectedMonth, selectedYear], () => {
    applyFilters();
});

const formatTime = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
};

const formatMinutesToHours = (minutes: number | null) => {
    if (minutes === null) return '-';
    if (minutes === 0) return '0h';
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours === 0) return `${mins}m`;
    return mins > 0 ? `${hours}h ${mins}m` : `${hours}h`;
};

const getStatusInfo = (attendance: Attendance) => {
    switch (attendance.status) {
        case 'present':
            return {
                label: 'Present',
                variant: 'default' as const,
                class: 'bg-green-600 hover:bg-green-700'
            };
        case 'absent':
            return {
                label: 'Absent',
                variant: 'destructive' as const,
                class: ''
            };
        case 'weekend':
            return {
                label: 'Weekend',
                variant: 'secondary' as const,
                class: 'bg-blue-600 hover:bg-blue-700 text-white'
            };
        case 'sick_leave':
            return {
                label: 'Sick Leave (SL)',
                variant: 'outline' as const,
                class: 'bg-yellow-100 text-yellow-800 border-yellow-300 hover:bg-yellow-200'
            };
        case 'casual_leave':
            return {
                label: 'Casual Leave (CL)',
                variant: 'outline' as const,
                class: 'bg-purple-100 text-purple-800 border-purple-300 hover:bg-purple-200'
            };
        default:
            return {
                label: 'Unknown',
                variant: 'outline' as const,
                class: ''
            };
    }
};
</script>

<template>
    <Head title="My Attendance" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header with Filters -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">My Attendance</h1>
                </div>
                <div class="flex items-center gap-2">
                    <Select v-model="selectedMonth">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue :placeholder="selectedMonthLabel" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="month in months" :key="month.value" :value="month.value">
                                {{ month.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedYear">
                        <SelectTrigger class="w-[100px]">
                            <SelectValue :placeholder="selectedYear" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="year in availableYears" :key="year" :value="year.toString()">
                                {{ year }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Attendance Table -->
            <Card>
                <CardHeader>
                    <CardTitle>{{ selectedMonthLabel }} {{ selectedYear }} Attendance</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 pr-4 text-left font-medium">Date</th>
                                    <th class="pb-3 px-4 text-left font-medium">Clock In</th>
                                    <th class="pb-3 px-4 text-left font-medium">Clock Out</th>
                                    <th class="pb-3 px-4 text-left font-medium">Status</th>
                                    <th class="pb-3 px-4 text-left font-medium">Late Time</th>
                                    <th class="pb-3 px-4 text-left font-medium">Early Exit</th>
                                    <th class="pb-3 px-4 text-left font-medium">Net Hours</th>
                                    <th class="pb-3 pl-4 text-left font-medium">Late Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="attendance in attendances"
                                    :key="attendance.id"
                                    class="border-b last:border-0 transition-colors hover:bg-muted/50"
                                >
                                    <td class="py-3 pr-4 font-medium">{{ formatDate(attendance.date) }}</td>
                                    <td class="py-3 px-4">
                                        <span :class="attendance.is_late ? 'text-red-600 font-medium' : ''">
                                            {{ formatTime(attendance.clock_in) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span :class="attendance.early_exit_minutes > 0 ? 'text-orange-600 font-medium' : ''">
                                            {{ formatTime(attendance.clock_out) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <Badge
                                            :variant="getStatusInfo(attendance).variant"
                                            :class="getStatusInfo(attendance).class"
                                            class="text-xs font-semibold px-2.5 py-1"
                                        >
                                            {{ getStatusInfo(attendance).label }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span v-if="attendance.late_minutes > 0" class="text-red-600 font-medium">
                                            {{ formatMinutesToHours(attendance.late_minutes) }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span v-if="attendance.early_exit_minutes > 0" class="text-orange-600 font-medium">
                                            {{ formatMinutesToHours(attendance.early_exit_minutes) }}
                                        </span>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                    <td class="py-3 px-4 font-medium">{{ formatMinutesToHours(attendance.net_minutes) }}</td>
                                    <td class="py-3 pl-4">
                                        <Badge
                                            v-if="attendance.clock_in"
                                            :variant="attendance.is_late ? 'destructive' : 'default'"
                                            :class="attendance.is_late ? 'bg-red-600 hover:bg-red-700' : 'bg-green-600 hover:bg-green-700'"
                                            class="text-xs font-semibold px-2.5 py-1"
                                        >
                                            {{ attendance.is_late ? 'Yes' : 'No' }}
                                        </Badge>
                                        <span v-else class="text-muted-foreground">-</span>
                                    </td>
                                </tr>
                                <tr v-if="attendances.length === 0">
                                    <td colspan="8" class="py-8 text-center text-muted-foreground">
                                        No attendance records found for {{ selectedMonthLabel }} {{ selectedYear }}.
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
