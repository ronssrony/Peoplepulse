<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Head } from '@inertiajs/vue3';
import { Users, TrendingUp, Calendar, Download } from 'lucide-vue-next';
import { computed } from 'vue';

interface DepartmentStat {
    name: string;
    employee_count: number;
    monthly_present_count: number;
    avg_attendance: number;
}

interface Stats {
    total_employees: number;
    monthly_rate: number;
    yearly_rate: number;
    monthly_present_count: number;
    yearly_present_count: number;
}

interface Props {
    stats: Stats;
    department_stats: DepartmentStat[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    { title: 'Reports', href: '/reports' },
];

const exportData = () => {
    // Basic export for now using the main export route.
    // In future this can be customized for reports.
    window.location.href = '/attendance/export';
};

// Start Date for "This Month" context
const currentMonth = new Date().toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
</script>

<template>
    <Head title="Reports" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Reports Dashboard</h1>
                    <p class="text-muted-foreground">Overview for {{ currentMonth }}</p>
                </div>
                <Button @click="exportData">
                    <Download class="mr-2 h-4 w-4" />
                    Export Data
                </Button>
            </div>

            <!-- Main Stats -->
            <div class="grid gap-4 md:grid-cols-3">
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
                        <CardDescription>Monthly Attendance Rate</CardDescription>
                        <TrendingUp class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.monthly_rate }}%</div>
                        <p class="text-xs text-muted-foreground">{{ stats.monthly_present_count }} records this month</p>
                        <div class="mt-2 h-1.5 w-full rounded-full bg-secondary">
                            <div 
                                class="h-1.5 rounded-full bg-green-500 transition-all duration-500" 
                                :style="{ width: `${stats.monthly_rate}%` }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Yearly Attendance Rate</CardDescription>
                        <Calendar class="h-4 w-4 text-blue-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ stats.yearly_rate }}%</div>
                         <p class="text-xs text-muted-foreground">{{ stats.yearly_present_count }} records this year</p>
                        <div class="mt-2 h-1.5 w-full rounded-full bg-secondary">
                            <div 
                                class="h-1.5 rounded-full bg-blue-500 transition-all duration-500" 
                                :style="{ width: `${stats.yearly_rate}%` }"
                            ></div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Department Breakdown -->
            <Card class="col-span-1">
                <CardHeader>
                    <CardTitle>Department Performance</CardTitle>
                    <CardDescription>Average attendance days per employee this month</CardDescription>
                </CardHeader>
                <CardContent>
                     <div class="space-y-6">
                        <div v-for="dept in department_stats" :key="dept.name" class="space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <div class="font-medium">{{ dept.name }}</div>
                                <div class="text-muted-foreground">
                                    {{ dept.avg_attendance }} avg days / {{ dept.employee_count }} employees
                                </div>
                            </div>
                            <!-- Assuming max working days ~22-26/month, let's normalize bar to ~26 -->
                            <div class="h-2 w-full rounded-full bg-secondary overflow-hidden">
                                <div 
                                    class="h-full bg-primary transition-all duration-500" 
                                    :style="{ width: `${Math.min((dept.avg_attendance / 26) * 100, 100)}%` }"
                                ></div>
                            </div>
                        </div>
                         <div v-if="department_stats.length === 0" class="text-center text-muted-foreground py-4">
                            No department data available.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
