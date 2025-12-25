<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Attendance, AttendanceFilters, BreadcrumbItem, PaginatedData } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Edit, Clock } from 'lucide-vue-next';
import { ref } from 'vue';

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
    subDepartment?: {
        id: number;
        name: string;
    };
}

interface Props {
    attendances: PaginatedData<Attendance>;
    departments: Department[];
    subDepartments: SubDepartment[];
    employees: Employee[];
    filters: AttendanceFilters;
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

const overrideForm = useForm({
    clock_in: '',
    clock_out: '',
    break_minutes: 60,
    is_late: false,
    reason: '',
});

const applyFilters = () => {
    router.get('/attendance/admin', {
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
        department: '',
        sub_department: '',
        employee: '',
    };
    router.get('/attendance/admin');
};

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
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Admin Dashboard</h1>
                </div>
            </div>

            <!-- Filters -->
            <Card>
                <CardHeader>
                    <CardTitle>Filters</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-6">
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
                            <Label for="department">Department</Label>
                            <select
                                id="department"
                                v-model="localFilters.department"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All Departments</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="sub_department">Sub-Department</Label>
                            <select
                                id="sub_department"
                                v-model="localFilters.sub_department"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All Sub-Departments</option>
                                <option v-for="sub in subDepartments" :key="sub.id" :value="sub.id">
                                    {{ sub.name }}
                                </option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <Label for="employee">Employee</Label>
                            <select
                                id="employee"
                                v-model="localFilters.employee"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                <option value="">All Employees</option>
                                <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                    {{ emp.name }} ({{ emp.employee_id }})
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
                                    <th class="pb-3 text-left font-medium">Employee</th>
                                    <th class="pb-3 text-left font-medium">Department</th>
                                    <th class="pb-3 text-left font-medium">Date</th>
                                    <th class="pb-3 text-left font-medium">Clock In</th>
                                    <th class="pb-3 text-left font-medium">Clock Out</th>
                                    <th class="pb-3 text-left font-medium">Net Hours</th>
                                    <th class="pb-3 text-left font-medium">Status</th>
                                    <th class="pb-3 text-left font-medium">Actions</th>
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
                                    <td class="py-3">
                                        <div v-if="attendance?.user?.department" class="text-sm">{{ attendance.user?.department?.name }}</div>
                                        <div v-if="attendance.user?.sub_department" class="text-xs text-muted-foreground">
                                            {{ attendance.user?.sub_department?.name }}
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
                                    <td class="py-3">
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
