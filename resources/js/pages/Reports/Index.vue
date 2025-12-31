<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Users, Download, Clock, PieChart, Activity, Award,
    AlertCircle, FileText
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface DailyTrend {
    date: string;
    day: string;
    present: number;
    late: number;
}

interface DepartmentDistribution {
    name: string;
    total: number;
    present: number;
    absent: number;
    late: number;
    on_time: number;
}

interface StatusBreakdown {
    on_time: number;
    late: number;
    total_records: number;
}

interface HoursByDay {
    day: string;
    hours: number;
}

interface EmployeeRecord {
    user_id: number;
    on_time_count?: number;
    late_count?: number;
    user: {
        id: number;
        name: string;
        employee_id: string;
    };
}

interface Stats {
    total_employees: number;
    monthly_present: number;
    monthly_late: number;
    avg_working_hours: number;
}

interface Charts {
    daily_trend: DailyTrend[];
    department_distribution: DepartmentDistribution[];
    status_breakdown: StatusBreakdown;
    hours_by_day: HoursByDay[];
}

interface Lists {
    top_performers: EmployeeRecord[];
    needs_attention: EmployeeRecord[];
}

interface Filters {
    month: number;
    year: number;
}

interface Props {
    stats: Stats;
    charts: Charts;
    lists: Lists;
    filters: Filters;
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Reports', href: '/attendance/reports' },
];

// Month/Year filter state
const selectedMonth = ref(String(props.filters.month));
const selectedYear = ref(String(props.filters.year));

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

const currentYear = new Date().getFullYear();
const years = Array.from({ length: 5 }, (_, i) => ({
    value: String(currentYear - i),
    label: String(currentYear - i),
}));

const applyFilters = () => {
    router.get('/attendance/reports', {
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

const exportData = (type: 'csv' | 'xlsx') => {
    window.location.href = `/attendance/reports/export?type=${type}&month=${selectedMonth.value}&year=${selectedYear.value}`;
};

const selectedMonthLabel = computed(() => {
    const month = months.find(m => m.value === selectedMonth.value);
    return month ? `${month.label} ${selectedYear.value}` : '';
});

// Computed values for charts
const maxDailyValue = computed(() => {
    return Math.max(...props.charts.daily_trend.map(d => d.present), 1);
});

const maxHoursValue = computed(() => {
    return Math.max(...props.charts.hours_by_day.map(h => h.hours), 8);
});

const onTimePercentage = computed(() => {
    const total = props.charts.status_breakdown.total_records;
    if (total === 0) return 0;
    return Math.round((props.charts.status_breakdown.on_time / total) * 100);
});

const latePercentage = computed(() => {
    const total = props.charts.status_breakdown.total_records;
    if (total === 0) return 0;
    return Math.round((props.charts.status_breakdown.late / total) * 100);
});
</script>

<template>
    <Head title="Reports Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">Reports Dashboard</h1>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                    <Select v-model="selectedMonth">
                        <SelectTrigger class="w-[140px]">
                            <SelectValue placeholder="Select month" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="m in months" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="selectedYear">
                        <SelectTrigger class="w-[100px]">
                            <SelectValue placeholder="Select year" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="y in years" :key="y.value" :value="y.value">
                                {{ y.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Link href="/attendance/employee-report">
                        <Button variant="outline">
                            <FileText class="mr-2 h-4 w-4" />
                            Employee Reports
                        </Button>
                    </Link>
<!--                    <DropdownMenu>-->
<!--                        <DropdownMenuTrigger as-child>-->
<!--                            <Button>-->
<!--                                <Download class="mr-2 h-4 w-4" />-->
<!--                                Export Data-->
<!--                            </Button>-->
<!--                        </DropdownMenuTrigger>-->
<!--                        <DropdownMenuContent>-->
<!--                            <DropdownMenuItem @click="exportData('csv')">Export as CSV</DropdownMenuItem>-->
<!--                            <DropdownMenuItem @click="exportData('xlsx')">Export as XLSX</DropdownMenuItem>-->
<!--                        </DropdownMenuContent>-->
<!--                    </DropdownMenu>-->
                </div>
            </div>

            <!-- Main Charts Row -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Daily Trend for Selected Month -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Activity class="h-5 w-5" />
                            Daily Attendance
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2 max-h-[400px] overflow-y-auto">
                            <div
                                v-for="item in charts.daily_trend"
                                :key="item.date"
                                class="flex items-center gap-3 group"
                                :title="`${item.date}: ${item.present} present (${item.present - item.late} on time, ${item.late} late)`"
                            >
                                <div class="w-20 text-xs text-muted-foreground">
                                    {{ item.day }} {{ item.date.split(' ')[1] }}
                                </div>
                                <div class="flex-1 flex gap-0.5 h-6 items-center">
                                    <div
                                        class="bg-green-500 rounded-l transition-all duration-300 h-full"
                                        :style="{ width: `${maxDailyValue > 0 ? ((item.present - item.late) / maxDailyValue) * 100 : 0}%` }"
                                        :title="`On Time: ${item.present - item.late}`"
                                    ></div>
                                    <div
                                        class="bg-yellow-500 transition-all duration-300 h-full"
                                        :class="item.late > 0 ? 'rounded-r' : ''"
                                        :style="{ width: `${maxDailyValue > 0 ? (item.late / maxDailyValue) * 100 : 0}%` }"
                                        :title="`Late: ${item.late}`"
                                    ></div>
                                </div>
                                <div class="w-24 text-xs text-right">
                                    <span class="text-green-600 font-medium">{{ item.present - item.late }}</span>
                                    <span class="text-muted-foreground"> / </span>
                                    <span class="text-yellow-600">{{ item.late }}</span>
                                    <span class="text-muted-foreground ml-1">({{ item.present }})</span>
                                </div>
                            </div>
                            <div v-if="charts.daily_trend.length === 0" class="text-center text-muted-foreground py-8">
                                No attendance data for this period.
                            </div>
                        </div>
                        <div class="flex gap-6 text-xs pt-4 border-t mt-4 justify-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded"></div>
                                <span>On Time</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                                <span>Late</span>
                            </div>
                            <div class="flex items-center gap-2 text-muted-foreground">
                                <span>Format: On Time / Late (Total)</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Attendance Status Pie/Donut Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PieChart class="h-5 w-5" />
                            Monthly Status
                        </CardTitle>
                        <CardDescription>On-time vs Late for {{ selectedMonthLabel }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex flex-col items-center">
                            <!-- SVG Donut Chart -->
                            <div class="relative w-40 h-40">
                                <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90">
                                    <!-- Background circle -->
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="12"
                                        class="text-secondary"
                                    />
                                    <!-- On-time segment -->
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="#22c55e"
                                        stroke-width="12"
                                        :stroke-dasharray="`${onTimePercentage * 2.51} 251`"
                                        stroke-linecap="round"
                                        class="transition-all duration-700"
                                    />
                                    <!-- Late segment -->
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="#eab308"
                                        stroke-width="12"
                                        :stroke-dasharray="`${latePercentage * 2.51} 251`"
                                        :stroke-dashoffset="`${-onTimePercentage * 2.51}`"
                                        stroke-linecap="round"
                                        class="transition-all duration-700"
                                    />
                                </svg>
                                <!-- Center text -->
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-2xl font-bold">{{ onTimePercentage }}%</span>
                                    <span class="text-xs text-muted-foreground">On Time</span>
                                </div>
                            </div>

                            <div class="flex gap-6 mt-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                    <span>{{ charts.status_breakdown.on_time }} On Time</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                    <span>{{ charts.status_breakdown.late }} Late</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Working Hours by Day of Week -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Avg Hours by Day - {{ selectedMonthLabel }}
                    </CardTitle>
                    <CardDescription>Average working hours per weekday for the selected month</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-end justify-between gap-2 h-40">
                        <div
                            v-for="item in charts.hours_by_day"
                            :key="item.day"
                            class="flex-1 flex flex-col items-center gap-2"
                        >
                            <div
                                class="w-full max-w-10 bg-primary rounded-t transition-all duration-500"
                                :style="{ height: `${maxHoursValue > 0 ? (item.hours / maxHoursValue) * 100 : 0}%` }"
                            ></div>
                            <span class="text-xs text-muted-foreground">{{ item.day }}</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t text-center">
                        <span class="text-sm text-muted-foreground">
                            Monthly Average: <strong>{{ stats.avg_working_hours }}h</strong>
                        </span>
                    </div>
                </CardContent>
            </Card>

            <!-- Department Distribution -->
            <Card v-if="charts.department_distribution.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Users class="h-5 w-5" />
                        Department-wise Status - {{ selectedMonthLabel }}
                    </CardTitle>
                    <CardDescription>Attendance by department for the selected month</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div
                            v-for="(dept, index) in charts.department_distribution"
                            :key="dept.name"
                            class="p-4 rounded-lg border bg-card"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium">{{ dept.name }}</h4>
                                <span class="text-sm text-muted-foreground">{{ dept.total }} employees</span>
                            </div>

                            <!-- Mini stacked bar -->
                            <div class="h-3 rounded-full overflow-hidden flex bg-secondary mb-3">
                                <div
                                    class="bg-green-500 transition-all duration-500"
                                    :style="{ width: `${dept.total > 0 ? (dept.on_time / dept.total) * 100 : 0}%` }"
                                ></div>
                                <div
                                    class="bg-yellow-500 transition-all duration-500"
                                    :style="{ width: `${dept.total > 0 ? (dept.late / dept.total) * 100 : 0}%` }"
                                ></div>
                            </div>

                            <div class="flex justify-between text-xs">
                                <span class="text-green-600">{{ dept.present }} present</span>
                                <span class="text-yellow-600">{{ dept.late }} late</span>
                                <span class="text-red-600">{{ dept.absent }} absent</span>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Top Performers and Needs Attention -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Top Performers -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Award class="h-5 w-5 text-green-500" />
                            Top Performers - {{ selectedMonthLabel }}
                        </CardTitle>
                        <CardDescription>Most consistent attendance for the selected month</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(employee, index) in lists.top_performers"
                                :key="employee.user_id"
                                class="flex items-center justify-between p-3 rounded-lg bg-green-50 dark:bg-green-950/30"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 font-semibold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ employee.user?.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ employee.user?.employee_id }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-bold text-green-600">{{ employee.on_time_count }}</span>
                                    <p class="text-xs text-muted-foreground">on-time days</p>
                                </div>
                            </div>
                            <div v-if="lists.top_performers.length === 0" class="text-center text-muted-foreground py-8">
                                No data available yet.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Needs Attention -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <AlertCircle class="h-5 w-5 text-yellow-500" />
                            Needs Attention - {{ selectedMonthLabel }}
                        </CardTitle>
                        <CardDescription>Most late arrivals for the selected month</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="(employee, index) in lists.needs_attention"
                                :key="employee.user_id"
                                class="flex items-center justify-between p-3 rounded-lg bg-yellow-50 dark:bg-yellow-950/30"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100 dark:bg-yellow-900 text-yellow-700 dark:text-yellow-300 font-semibold text-sm">
                                        {{ index + 1 }}
                                    </div>
                                    <div>
                                        <p class="font-medium">{{ employee.user?.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ employee.user?.employee_id }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-lg font-bold text-yellow-600">{{ employee.late_count }}</span>
                                    <p class="text-xs text-muted-foreground">late days</p>
                                </div>
                            </div>
                            <div v-if="lists.needs_attention.length === 0" class="text-center text-muted-foreground py-8">
                                No late arrivals for this period! 🎉
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Monthly Summary Stats -->
            <div class="grid gap-4 md:grid-cols-2">
                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>{{ selectedMonthLabel }} - Total Attendance</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold">{{ stats.monthly_present }}</span>
                            <span class="text-sm text-muted-foreground">attendance records</span>
                        </div>
                        <p class="text-xs text-green-600 mt-1">{{ stats.monthly_present - stats.monthly_late }} on-time arrivals</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="pb-2">
                        <CardDescription>{{ selectedMonthLabel }} - Late Arrivals</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold text-yellow-600">{{ stats.monthly_late }}</span>
                            <span class="text-sm text-muted-foreground">late arrivals</span>
                        </div>
                        <p class="text-xs text-muted-foreground mt-1">Avg working hours: {{ stats.avg_working_hours }}h</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
