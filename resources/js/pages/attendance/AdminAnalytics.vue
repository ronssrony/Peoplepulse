<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { 
    Users, TrendingUp, Clock, AlertTriangle, UserCheck, 
    BarChart3, Building2, FileText, Calendar 
} from 'lucide-vue-next';
import { computed } from 'vue';

interface MonthlyTrend {
    month: string;
    present: number;
    late: number;
}

interface DepartmentStat {
    name: string;
    present: number;
    late: number;
    absent: number;
    total: number;
    percentage: number;
}

interface TopLateEmployee {
    user_id: number;
    late_count: number;
    user: {
        id: number;
        name: string;
        employee_id: string;
        department_id: number;
    };
}

interface AvgHoursByDept {
    name: string;
    hours: number;
}

interface Stats {
    total_employees: number;
    monthly_present: number;
    monthly_late: number;
    monthly_on_time: number;
    yearly_present: number;
    yearly_late: number;
}

interface Charts {
    monthly_trend: MonthlyTrend[];
    department_stats: DepartmentStat[];
    top_late_employees: TopLateEmployee[];
    avg_hours_by_dept: AvgHoursByDept[];
}

interface Props {
    employees: User[];
    stats: Stats;
    charts: Charts;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/attendance' },
    { title: 'Admin Analytics', href: '/attendance/admin/analytics' },
];

const maxMonthlyValue = computed(() => {
    return Math.max(...props.charts.monthly_trend.map(m => m.present + m.late), 1);
});

const maxDeptHours = computed(() => {
    return Math.max(...props.charts.avg_hours_by_dept.map(d => d.hours), 8);
});

const onTimeRate = computed(() => {
    const total = props.stats.monthly_present;
    if (total === 0) return 0;
    return Math.round((props.stats.monthly_on_time / total) * 100);
});

const yearlyOnTimeRate = computed(() => {
    const total = props.stats.yearly_present;
    if (total === 0) return 0;
    return Math.round(((total - props.stats.yearly_late) / total) * 100);
});

const totalDeptEmployees = computed(() => {
    return props.charts.department_stats.reduce((sum, dept) => sum + dept.total, 0);
});
</script>

<template>
    <Head title="Admin Analytics" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Admin Analytics Dashboard</h1>
                    <p class="text-muted-foreground">Company-wide attendance insights and metrics</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/attendance/admin">
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
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Employees</CardDescription>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.total_employees }}</div>
                        <p class="text-xs text-muted-foreground">Active in system</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Monthly Attendance</CardDescription>
                        <UserCheck class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">{{ stats.monthly_present }}</div>
                        <p class="text-xs text-muted-foreground">{{ stats.monthly_late }} late arrivals</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Monthly On-Time Rate</CardDescription>
                        <TrendingUp class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-blue-600">{{ onTimeRate }}%</div>
                        <div class="mt-2 h-1.5 w-full rounded-full bg-secondary">
                            <div 
                                class="h-1.5 rounded-full bg-blue-500 transition-all duration-500" 
                                :style="{ width: `${onTimeRate}%` }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Yearly Performance</CardDescription>
                        <Calendar class="h-4 w-4 text-purple-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-purple-600">{{ yearlyOnTimeRate }}%</div>
                        <p class="text-xs text-muted-foreground">{{ stats.yearly_present }} records YTD</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- 12-Month Trend Chart -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <BarChart3 class="h-5 w-5" />
                            12-Month Attendance Trend
                        </CardTitle>
                        <CardDescription>Monthly attendance patterns across the year</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-end justify-between gap-2 h-48">
                            <div 
                                v-for="item in charts.monthly_trend" 
                                :key="item.month"
                                class="flex-1 flex flex-col items-center gap-1"
                            >
                                <div class="w-full flex flex-col items-center gap-0.5" style="height: 160px;">
                                    <div 
                                        class="w-full max-w-8 bg-green-500 rounded-t transition-all duration-300"
                                        :style="{ height: `${(item.present / maxMonthlyValue) * 100}%` }"
                                        :title="`Present: ${item.present}`"
                                    ></div>
                                    <div 
                                        class="w-full max-w-8 bg-yellow-500 rounded-b transition-all duration-300"
                                        :style="{ height: `${(item.late / maxMonthlyValue) * 100}%` }"
                                        :title="`Late: ${item.late}`"
                                    ></div>
                                </div>
                                <span class="text-xs text-muted-foreground">{{ item.month }}</span>
                            </div>
                        </div>
                        <div class="flex gap-6 text-xs pt-4 border-t mt-4 justify-center">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded"></div>
                                <span>Present</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded"></div>
                                <span>Late</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Department Stats -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Building2 class="h-5 w-5" />
                            Department Today's Status
                        </CardTitle>
                        <CardDescription>Real-time department attendance</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="dept in charts.department_stats" :key="dept.name" class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium">{{ dept.name }}</span>
                                    <span class="text-muted-foreground">
                                        {{ dept.present }}/{{ dept.total }} 
                                        <span class="text-yellow-600" v-if="dept.late > 0">({{ dept.late }} late)</span>
                                    </span>
                                </div>
                                <div class="h-3 bg-secondary rounded-full overflow-hidden flex">
                                    <div 
                                        class="bg-green-500 transition-all duration-300"
                                        :style="{ width: `${dept.total > 0 ? ((dept.present - dept.late) / dept.total) * 100 : 0}%` }"
                                    ></div>
                                    <div 
                                        class="bg-yellow-500 transition-all duration-300"
                                        :style="{ width: `${dept.total > 0 ? (dept.late / dept.total) * 100 : 0}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="charts.department_stats.length === 0" class="text-center text-muted-foreground py-8">
                                No department data available.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Average Hours by Department -->
                <Card>
                    <CardHeader>
                        <CardTitle>Avg Working Hours by Department</CardTitle>
                        <CardDescription>Average net working hours this month</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-4">
                            <div v-for="dept in charts.avg_hours_by_dept" :key="dept.name" class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="font-medium">{{ dept.name }}</span>
                                    <span class="font-semibold">{{ dept.hours }}h</span>
                                </div>
                                <div class="h-3 bg-secondary rounded-full overflow-hidden">
                                    <div 
                                        class="h-full bg-primary transition-all duration-300"
                                        :style="{ width: `${Math.min((dept.hours / maxDeptHours) * 100, 100)}%` }"
                                    ></div>
                                </div>
                            </div>
                            <div v-if="charts.avg_hours_by_dept.length === 0" class="text-center text-muted-foreground py-8">
                                No working hours data available.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Top Late Employees -->
                <Card class="lg:col-span-2">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <AlertTriangle class="h-5 w-5 text-yellow-500" />
                            Top 10 Late Arrivals (This Month)
                        </CardTitle>
                        <CardDescription>Employees requiring attention for punctuality</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div 
                                v-for="(employee, index) in charts.top_late_employees" 
                                :key="employee.user_id"
                                class="flex items-center justify-between p-3 rounded-lg bg-muted/50"
                            >
                                <div class="flex items-center gap-3">
                                    <div 
                                        :class="[
                                            'flex h-8 w-8 items-center justify-center rounded-full font-semibold text-sm',
                                            index < 3 ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'
                                        ]"
                                    >
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
                            <div v-if="charts.top_late_employees.length === 0" class="col-span-2 text-center text-muted-foreground py-8">
                                No late arrivals this month! Great job everyone! 🎉
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
