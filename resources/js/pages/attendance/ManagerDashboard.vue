<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Attendance, AttendanceFilters, BreadcrumbItem, DepartmentSummary, PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Users, Clock, AlertTriangle, UserCheck, UserX } from 'lucide-vue-next';
import { ref } from 'vue';

interface SubDepartment {
    id: number;
    name: string;
}

interface Props {
    attendances: PaginatedData<Attendance>;
    departmentSummary: DepartmentSummary | null;
    subDepartments: SubDepartment[];
    filters: AttendanceFilters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/attendance' },
    { title: 'Manager Dashboard', href: '/attendance/manager' },
];

const localFilters = ref({
    start_date: props.filters.start_date ?? '',
    end_date: props.filters.end_date ?? '',
    sub_department: props.filters.sub_department ?? '',
});

const applyFilters = () => {
    router.get('/attendance/manager', {
        ...localFilters.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        start_date: '',
        end_date: '',
        sub_department: '',
    };
    router.get('/attendance/manager');
};

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
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${hours}h ${mins}m`;
};
</script>

<template>
    <Head title="Manager Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Manager Dashboard</h1>
                    <p class="text-muted-foreground">Department attendance overview</p>
                </div>
            </div>

            <!-- Today's Summary Cards -->
            <div v-if="departmentSummary" class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Employees</CardDescription>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ departmentSummary.total_employees }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Present Today</CardDescription>
                        <UserCheck class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">{{ departmentSummary.present }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Absent Today</CardDescription>
                        <UserX class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-red-600">{{ departmentSummary.absent }}</div>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Late Today</CardDescription>
                        <AlertTriangle class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-yellow-600">{{ departmentSummary.late }}</div>
                    </CardContent>
                </Card>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-4">
                        <div class="space-y-2">
                            <Label for="start_date">Start Date</Label>
                            <Input
                                id="start_date"
                                type="date"
                                v-model="localFilters.start_date"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="end_date">End Date</Label>
                            <Input
                                id="end_date"
                                type="date"
                                v-model="localFilters.end_date"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="sub_department">Sub-Department</Label>
                            <select
                                id="sub_department"
                                v-model="localFilters.sub_department"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All</option>
                                <option v-for="sub in subDepartments" :key="sub.id" :value="sub.id">
                                    {{ sub.name }}
                                </option>
                            </select>
                        </div>
                        <div class="flex items-end gap-2">
                            <Button @click="applyFilters">Apply</Button>
                            <Button variant="outline" @click="resetFilters">Reset</Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Attendance Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Attendance Records</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="pb-3 text-left font-medium">Employee</th>
                                    <th class="pb-3 text-left font-medium">Date</th>
                                    <th class="pb-3 text-left font-medium">Clock In</th>
                                    <th class="pb-3 text-left font-medium">Clock Out</th>
                                    <th class="pb-3 text-left font-medium">Net Hours</th>
                                    <th class="pb-3 text-left font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="attendance in attendances.data"
                                    :key="attendance.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3">
                                        <div>
                                            <div class="font-medium">{{ attendance.user?.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ attendance.user?.employee_id }}</div>
                                        </div>
                                    </td>
                                    <td class="py-3">{{ formatDate(attendance.date) }}</td>
                                    <td class="py-3">{{ formatTime(attendance.clock_in) }}</td>
                                    <td class="py-3">{{ formatTime(attendance.clock_out) }}</td>
                                    <td class="py-3">{{ formatMinutesToHours(attendance.net_minutes) }}</td>
                                    <td class="py-3">
                                        <Badge v-if="attendance.is_late" variant="destructive" class="text-xs">Late</Badge>
                                        <Badge v-else variant="outline" class="text-xs">On Time</Badge>
                                    </td>
                                </tr>
                                <tr v-if="attendances.data.length === 0">
                                    <td colspan="6" class="py-8 text-center text-muted-foreground">
                                        No attendance records found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="attendances.data.length > 0" class="mt-4 flex items-center justify-between border-t pt-4">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ attendances.from }} to {{ attendances.to }} of {{ attendances.total }} results
                        </div>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in attendances.links"
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
