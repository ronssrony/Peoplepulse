<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Attendance, AttendanceFilters, BreadcrumbItem, PaginatedData } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Clock, Download, X, Users, AlertTriangle, UserCheck, UserX } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DateRangePicker from '@/components/ui/date-range-picker/DateRangePicker.vue';
import type { User } from '@/types';

interface Department {
    id: number;
    name: string;
}

interface SubDepartment {
    id: number;
    name: string;
    department_id: number;
    department?: {
        id: number;
        name: string;
    };
}

interface Employee {
    id: number;
    name: string;
    employee_id: string;
    department_id: number | null;
    sub_department_id: number | null;
    department?: {
        id: number;
        name: string;
    };
    sub_department?: {
        id: number;
        name: string;
    };
}

interface CompanySummary {
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
    departments: Department[];
    subDepartments: SubDepartment[];
    employees: Employee[];
    filters: AttendanceFilters;
    companySummary: CompanySummary | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/attendance' },
    { title: 'Admin Dashboard', href: '/attendance/admin' },
];

const localFilters = ref({
    start_date: props.filters.start_date ?? '',
    end_date: props.filters.end_date ?? '',
    department: props.filters.department ?? '',
    sub_department: props.filters.sub_department ?? '',
    employee: props.filters.employee ?? '',
});

const showOverrideModal = ref(false);
const selectedAttendance = ref<Attendance | null>(null);
const isTeamModalOpen = ref(false);
const teamModalTitle = ref('');
const teamModalList = ref<User[]>([]);

const overrideForm = useForm({
    clock_in: '',
    clock_out: '',
    break_minutes: 60,
    is_late: false,
    reason: '',
});

const applyFilters = () => {
    const filters = { ...localFilters.value };
    
    // Sanitize filters: convert 'all_*' or null/undefined to empty string
    if (filters.sub_department === 'all_sub_departments') filters.sub_department = '';
    if (filters.employee === 'all_employees') filters.employee = '';

    router.get('/attendance/admin', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    localFilters.value = {
        start_date: '',
        end_date: '',
        department: '',
        sub_department: '',
        employee: '',
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
    if (localFilters.value.department && localFilters.value.department !== 'all_departments') params.append('department', localFilters.value.department);
    if (localFilters.value.sub_department && localFilters.value.sub_department !== 'all_sub_departments') params.append('sub_department', localFilters.value.sub_department);
    if (localFilters.value.employee && localFilters.value.employee !== 'all_employees') params.append('employee', localFilters.value.employee);
    params.append('type', type);

    window.location.href = `/attendance/export?${params.toString()}`;
};

// Immediate filter application for selects
watch(() => localFilters.value.sub_department, () => applyFilters());
watch(() => localFilters.value.employee, () => applyFilters());

const openOverrideModal = (attendance: Attendance) => {
    selectedAttendance.value = attendance;
    overrideForm.clock_in = attendance.clock_in ? new Date(attendance.clock_in).toISOString().slice(0, 16) : '';
    overrideForm.clock_out = attendance.clock_out ? new Date(attendance.clock_out).toISOString().slice(0, 16) : '';
    overrideForm.break_minutes = attendance.break_minutes;
    overrideForm.is_late = attendance.is_late;
    overrideForm.reason = '';
    showOverrideModal.value = true;
};

const submitOverride = () => {
    if (!selectedAttendance.value) return;

    overrideForm.patch(`/attendance/${selectedAttendance.value.id}/override`, {
        preserveScroll: true,
        onSuccess: () => {
            showOverrideModal.value = false;
            selectedAttendance.value = null;
            overrideForm.reset();
        },
    });
};

const openTeamModal = (type: 'all' | 'present' | 'absent' | 'late') => {
    if (!props.companySummary) return;

    if (type === 'all') {
        teamModalTitle.value = 'All Employees';
        teamModalList.value = props.companySummary.all_list;
    } else if (type === 'present') {
        teamModalTitle.value = 'Present';
        teamModalList.value = props.companySummary.present_list;
    } else if (type === 'absent') {
        teamModalTitle.value = 'Absent';
        teamModalList.value = props.companySummary.absent_list;
    } else if (type === 'late') {
        teamModalTitle.value = 'Late';
        teamModalList.value = props.companySummary.late_list;
    }
    isTeamModalOpen.value = true;
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

const hasActiveFilters = computed(() => {
    return localFilters.value.start_date || 
           localFilters.value.end_date || 
           (localFilters.value.sub_department && localFilters.value.sub_department !== 'all_sub_departments') ||
           (localFilters.value.employee && localFilters.value.employee !== 'all_employees');
});
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header with Filters -->
            <div class="flex flex-col gap-4">
                <h1 class="text-2xl font-bold">Admin Dashboard</h1>
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
                    <Select v-model="localFilters.employee">
                        <SelectTrigger class="w-[180px]">
                            <SelectValue placeholder="All Employees" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all_employees">All Employees</SelectItem>
                            <SelectItem v-for="emp in employees" :key="emp.id" :value="String(emp.id)">
                                {{ emp.name }} ({{ emp.employee_id }})
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
            <div v-if="companySummary" class="grid gap-4 md:grid-cols-4">
                <Card 
                    class="cursor-pointer hover:bg-muted/50 transition-colors"
                    @click="openTeamModal('all')"
                >
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Employees</CardDescription>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-3xl font-bold">{{ companySummary.total_employees }}</div>
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
                        <div class="text-3xl font-bold text-green-600">{{ companySummary.present }}</div>
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
                        <div class="text-3xl font-bold text-red-600">{{ companySummary.absent }}</div>
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
                        <div class="text-3xl font-bold text-yellow-600">{{ companySummary.late }}</div>
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
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        All Attendance Records
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="px-4 pb-3 text-left font-medium">Employee</th>
                                    <th class="px-4 pb-3 text-left font-medium">Department</th>
                                    <th class="px-4 pb-3 text-left font-medium">Date</th>
                                    <th class="px-4 pb-3 text-left font-medium">Clock In</th>
                                    <th class="px-4 pb-3 text-left font-medium">Clock Out</th>
                                    <th class="px-4 pb-3 text-left font-medium">Net Hours</th>
                                    <th class="px-4 pb-3 text-left font-medium">Status</th>
                                    <th class="px-4 pb-3 text-left font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="attendance in attendances.data"
                                    :key="attendance.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <div class="font-medium">{{ attendance.user?.name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ attendance.user?.employee_id }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="attendance?.user?.department" class="text-sm">{{ attendance.user?.department?.name }}</div>
                                        <div v-if="attendance.user?.sub_department" class="text-xs text-muted-foreground">
                                            {{ attendance.user?.sub_department?.name }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">{{ formatDate(attendance.date) }}</td>
                                    <td class="px-4 py-3">{{ formatTime(attendance.clock_in) }}</td>
                                    <td class="px-4 py-3">{{ formatTime(attendance.clock_out) }}</td>
                                    <td class="px-4 py-3">{{ formatMinutesToHours(attendance.net_minutes) }}</td>
                                    <td class="px-4 py-3">
                                        <Badge v-if="attendance.is_late" variant="destructive" class="text-xs">Late</Badge>
                                        <Badge v-else variant="outline" class="text-xs">On Time</Badge>
                                    </td>
                                    <td class="px-4 py-3">
                                        <!-- <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openOverrideModal(attendance)"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button> -->
                                    </td>
                                </tr>
                                <tr v-if="attendances.data.length === 0">
                                    <td colspan="8" class="py-8 text-center text-muted-foreground">
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

        <!-- Override Modal -->
        <Dialog v-model:open="showOverrideModal">
            <DialogContent class="sm:max-w-[425px]">
                <DialogHeader>
                    <DialogTitle>Override Attendance</DialogTitle>
                    <DialogDescription>
                        Modify attendance record for {{ selectedAttendance?.user?.name }}
                    </DialogDescription>
                </DialogHeader>
                <form @submit.prevent="submitOverride" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="override_clock_in">Clock In</Label>
                        <Input
                            id="override_clock_in"
                            type="datetime-local"
                            v-model="overrideForm.clock_in"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="override_clock_out">Clock Out</Label>
                        <Input
                            id="override_clock_out"
                            type="datetime-local"
                            v-model="overrideForm.clock_out"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="override_break">Break (minutes)</Label>
                        <Input
                            id="override_break"
                            type="number"
                            v-model="overrideForm.break_minutes"
                            min="0"
                            max="480"
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <input
                            id="override_is_late"
                            type="checkbox"
                            v-model="overrideForm.is_late"
                            class="h-4 w-4 rounded border-gray-300"
                        />
                        <Label for="override_is_late">Mark as Late</Label>
                    </div>
                    <div class="space-y-2">
                        <Label for="override_reason">Reason (required)</Label>
                        <textarea
                            id="override_reason"
                            v-model="overrideForm.reason"
                            rows="3"
                            required
                            minlength="10"
                            class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            placeholder="Provide a detailed reason for this override..."
                        ></textarea>
                        <p v-if="overrideForm.errors.reason" class="text-sm text-red-500">
                            {{ overrideForm.errors.reason }}
                        </p>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showOverrideModal = false">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="overrideForm.processing">
                            Save Changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
