<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Users, TrendingUp, Clock, AlertTriangle, UserCheck, 
    BarChart3, PieChart, FileText, ArrowRight 
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface MonthlyTrend {
    month: string;
    present: number;
    late: number;
}

interface WeeklyData {
    week: string;
    present: number;
}

interface TopLateEmployee {
    user_id: number;
    late_count: number;
    user: {
        id: number;
        name: string;
        employee_id: string;
    };
}

interface AvgHoursData {
    name: string;
    hours: number;
}

interface SubDeptStat {
    name: string;
    present: number;
    total: number;
    percentage: number;
}

interface Stats {
    total_employees: number;
    monthly_present: number;
    monthly_late: number;
    monthly_on_time: number;
}

interface Charts {
    monthly_trend: MonthlyTrend[];
    weekly_data: WeeklyData[];
    top_late_employees: TopLateEmployee[];
    avg_hours_data: AvgHoursData[];
    sub_dept_stats: SubDeptStat[];
}

interface Props {
    employees: User[];
    stats: Stats;
    charts: Charts;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/attendance' },
    { title: 'Manager Analytics', href: '/attendance/manager/analytics' },
];

// Calculate max values for chart scaling
const maxMonthlyValue = computed(() => {
    return Math.max(...props.charts.monthly_trend.map(m => m.present), 1);
});

const maxWeeklyValue = computed(() => {
    return Math.max(...props.charts.weekly_data.map(w => w.present), 1);
});

const maxAvgHours = computed(() => {
    return Math.max(...props.charts.avg_hours_data.map(d => d.hours), 8);
});

const attendanceRate = computed(() => {
    const total = props.stats.monthly_present;
    if (total === 0) return 0;
    return Math.round((props.stats.monthly_on_time / total) * 100);
});
</script>

<template>
    <Head title="Manager Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Analytics Dashboard</h1>
                    <p class="text-muted-foreground">Team performance insights and trends</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/attendance/manager">
                        <Button variant="outline">
                            <Clock class="mr-2 h-4 w-4" />
                            Attendance Records
                        </Button>
                    </Link>
                    <Link href="/attendance/employee-report">
                        <Button>
                            <FileText class="mr-2 h-4 w-4" />
                            Employee Reports
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Team Members</CardDescription>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.total_employees }}</div>
                        <p class="text-xs text-muted-foreground">Under your supervision</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Monthly Attendance</CardDescription>
                        <UserCheck class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">{{ stats.monthly_present }}</div>
                        <p class="text-xs text-muted-foreground">Records this month</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>On-Time Rate</CardDescription>
                        <TrendingUp class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-600">{{ attendanceRate }}%</div>
                        <div class="mt-2 h-1.5 w-full rounded-full bg-secondary">
                            <div 
                                class="h-1.5 rounded-full bg-blue-500 transition-all duration-500" 
                                :style="{ width: `${attendanceRate}%` }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Late This Month</CardDescription>
                        <AlertTriangle class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-yellow-600">{{ stats.monthly_late }}</div>
                        <p class="text-xs text-muted-foreground">Late arrivals</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Monthly Trend Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            6-Month Attendance Trend
                        </CardTitle>
                        <CardDescription>Monthly attendance and late arrival patterns</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="item in charts.monthly_trend" :key="item.month" class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium">{{ item.month }}</span>
                                    <span class="text-muted-foreground">
                                        {{ item.present }} present / {{ item.late }} late
                                    </span>
                                </div>
                                <div class="flex gap-1 h-6">
                                    <div 
                                        class="bg-green-500 rounded-l transition-all duration-300"
                                        :style="{ width: `${(item.present / maxMonthlyValue) * 100}%` }"
                                    ></div>
                                    <div 
                                        class="bg-yellow-500 rounded-r transition-all duration-300"
                                        :style="{ width: `${(item.late / maxMonthlyValue) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div class="flex gap-4 text-xs pt-2 border-t">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-green-500 rounded"></div>
                                    <span>Present</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                                    <span>Late</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Weekly Data Chart -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <PieChart class="h-5 w-5" />
                            Weekly Attendance (This Month)
                        </CardTitle>
                        <CardDescription>Week-by-week attendance breakdown</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="item in charts.weekly_data" :key="item.week" class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium">{{ item.week }}</span>
                                    <span class="text-muted-foreground">{{ item.present }} records</span>
                                </div>
                                <div class="h-4 bg-secondary rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-primary transition-all duration-300"
                                        :style="{ width: `${(item.present / maxWeeklyValue) * 100}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="charts.weekly_data.length === 0" class="text-center text-muted-foreground py-8">
                                No weekly data available yet.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Sub-Department Stats -->
                <Card>
                    <CardHeader>
                        <CardTitle>Sub-Department Today's Status</CardTitle>
                        <CardDescription>Attendance by sub-department</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="dept in charts.sub_dept_stats" :key="dept.name" class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium">{{ dept.name }}</span>
                                    <span class="text-muted-foreground">
                                        {{ dept.present }}/{{ dept.total }} ({{ dept.percentage }}%)
                                    </span>
                                </div>
                                <div class="h-3 bg-secondary rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-emerald-500 transition-all duration-300"
                                        :style="{ width: `${dept.percentage}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="charts.sub_dept_stats.length === 0" class="text-center text-muted-foreground py-8">
                                No sub-department data available.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Late Employees -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5 text-yellow-500" />
                            Top Late Arrivals (This Month)
                        </CardTitle>
                        <CardDescription>Employees with most late arrivals</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div 
                                v-for="(employee, index) in charts.top_late_employees" 
                                :key="employee.user_id"
                                class="flex items-center justify-between p-3 rounded-lg bg-muted/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-yellow-100 text-yellow-700 font-semibold text-sm">
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
                            <div v-if="charts.top_late_employees.length === 0" class="text-center text-muted-foreground py-8">
                                No late arrivals this month! 🎉
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Average Hours Chart -->
            <Card>
                <CardHeader>
                    <CardTitle>Average Working Hours (This Month)</CardTitle>
                    <CardDescription>Average net working hours per team member</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                        <div 
                            v-for="employee in charts.avg_hours_data.slice(0, 12)" 
                            :key="employee.name"
                            class="flex items-center gap-3 p-3 rounded-lg border"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="font-medium truncate">{{ employee.name }}</p>
                                <div class="mt-1 h-2 bg-secondary rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-primary transition-all duration-300"
                                        :style="{ width: `${Math.min((employee.hours / maxAvgHours) * 100, 100)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold">{{ employee.hours }}</span>
                                <p class="text-xs text-muted-foreground">hrs/day</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="charts.avg_hours_data.length === 0" class="text-center text-muted-foreground py-8">
                        No working hours data available.
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
