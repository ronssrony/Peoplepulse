<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Attendance, AttendanceFilters, BreadcrumbItem, PaginatedData } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { Users, Clock, AlertTriangle, UserCheck, UserX, Download, X } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import DateRangePicker from '@/components/ui/date-range-picker/DateRangePicker.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { User } from '@/types';

interface SubDepartment {
    id: number;
    name: string;
}
interface DepartmentSummary {
    total_employees: number;
    present: number;
    absent: number;
    late: number;
    all_list: User[];
    present_list: User[];
    absent_list: User[];
    late_list: User[];
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
    const filters = { ...localFilters.value };
    if (filters.sub_department === 'all_sub_departments') filters.sub_department = '';

    router.get('/attendance/manager', filters, {
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
    applyFilters();
};

const setToday = () => {
    const today = new Date().toISOString().split('T')[0];
    localFilters.value.start_date = today;
    localFilters.value.end_date = today;
    applyFilters();
};

const exportData = (type: 'csv' | 'xlsx' = 'csv') => {
    const params = new URLSearchParams();
    if (localFilters.value.start_date) params.append('start_date', localFilters.value.start_date);
    if (localFilters.value.end_date) params.append('end_date', localFilters.value.end_date);
    if (localFilters.value.sub_department && localFilters.value.sub_department !== 'all_sub_departments') params.append('sub_department', localFilters.value.sub_department);
    params.append('type', type);
    
    window.location.href = `/attendance/export?${params.toString()}`;
};

// Modal State
const isTeamModalOpen = ref(false);
const teamModalTitle = ref('');
const teamModalList = ref<User[]>([]);

const openTeamModal = (type: 'all' | 'present' | 'absent' | 'late') => {
    if (!props.departmentSummary) return;

    if (type === 'all') {
        teamModalTitle.value = 'All Employees';
        teamModalList.value = props.departmentSummary.all_list;
    } else if (type === 'present') {
        teamModalTitle.value = 'Present';
        teamModalList.value = props.departmentSummary.present_list;
    } else if (type === 'absent') {
        teamModalTitle.value = 'Absent';
        teamModalList.value = props.departmentSummary.absent_list;
    } else if (type === 'late') {
        teamModalTitle.value = 'Late';
        teamModalList.value = props.departmentSummary.late_list;
    }
    isTeamModalOpen.value = true;
};

watch(() => localFilters.value.sub_department, () => applyFilters());

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

const hasActiveFilters = computed(() => {
    return localFilters.value.start_date || 
           localFilters.value.end_date || 
           (localFilters.value.sub_department && localFilters.value.sub_department !== 'all_sub_departments');
});
</script>

<template>
    <Head title="Manager Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header with Filters -->
            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-bold">Manager Dashboard</h1>
                <div class="flex flex-wrap gap-2 items-center">
                    <DateRangePicker 
                        :start-date="localFilters.start_date"
                        :end-date="localFilters.end_date"
                        @update:start-date="(v) => localFilters.start_date = v"
                        @update:end-date="(v) => localFilters.end_date = v"
                        @apply="applyFilters"
                    />
                    <Select v-model="localFilters.sub_department">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="All Sub-Departments" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all_sub_departments">All Sub-Departments</SelectItem>
                            <SelectItem v-for="sub in subDepartments" :key="sub.id" :value="String(sub.id)">
                                {{ sub.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Button variant="secondary" @click="setToday">Today</Button>
                    <Button variant="outline" size="icon" @click="resetFilters" v-if="hasActiveFilters" title="Reset Filters">
                        <X class="h-4 w-4" />
                    </Button>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="default">
                                <Download class="mr-2 h-4 w-4" /> Export
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                            <DropdownMenuItem @click="exportData('csv')">Export as CSV</DropdownMenuItem>
                            <DropdownMenuItem @click="exportData('xlsx')">Export as XLSX</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <!-- Today's Summary Cards -->
            <div v-if="departmentSummary" class="grid gap-4 md:grid-cols-4">
                <Card 
                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                    @click="openTeamModal('all')"
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Employees</CardDescription>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ departmentSummary.total_employees }}</div>
                    </CardContent>
                </Card>
                <Card 
                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                    @click="openTeamModal('present')"
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Present Today</CardDescription>
                        <UserCheck class="h-4 w-4 text-green-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-green-600">{{ departmentSummary.present }}</div>
                    </CardContent>
                </Card>
                <Card 
                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                    @click="openTeamModal('absent')"
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Absent Today</CardDescription>
                        <UserX class="h-4 w-4 text-red-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-red-600">{{ departmentSummary.absent }}</div>
                    </CardContent>
                </Card>
                <Card 
                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                    @click="openTeamModal('late')"
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Late Today</CardDescription>
                        <AlertTriangle class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold text-yellow-600">{{ departmentSummary.late }}</div>
                    </CardContent>
                </Card>
            </div>
            
            <!-- Team Status Details Modal -->
            <Dialog v-model:open="isTeamModalOpen">
                <DialogContent class="max-h-[80vh] overflow-y-auto w-full max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{{ teamModalTitle }}</DialogTitle>
                        <DialogDescription>
                            Listing employees who are {{ teamModalTitle.toLowerCase() }} today.
                        </DialogDescription>
                    </DialogHeader>
                    
                    <div class="space-y-4">
                        <div v-if="teamModalList.length === 0" class="text-center text-muted-foreground py-8">
                            No employees found in this category.
                        </div>
                        
                        <div v-else class="grid gap-2">
                             <div 
                                v-for="employee in teamModalList" 
                                :key="employee.id"
                                class="flex items-center justify-between p-3 rounded-lg border bg-card text-card-foreground"
                             >
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ employee.name }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ employee.designation || 'Employee' }} 
                                        <span v-if="employee.sub_department">({{ employee.sub_department.name }})</span>
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ employee.employee_id }}
                                </div>
                             </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

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
