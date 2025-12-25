<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { Attendance, AttendanceAuditLog, BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Clock, History } from 'lucide-vue-next';

interface AttendanceWithLogs extends Attendance {
    audit_logs: AttendanceAuditLog[];
}

interface Props {
    attendance: AttendanceWithLogs;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Attendance', href: '/attendance' },
    { title: 'Details', href: `/attendance/${props.attendance.id}` },
];

const formatTime = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const formatDateTime = (dateString: string) => {
    return new Date(dateString).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
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
    <Head title="Attendance Details" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Attendance Details</h1>
                    <p class="text-muted-foreground">
                        {{ attendance.user?.name }} - {{ new Date(attendance.date).toLocaleDateString() }}
                    </p>
                </div>
            </div>

            <!-- Attendance Info -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Attendance Record
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Clock In</p>
                            <p class="text-lg font-medium">{{ formatTime(attendance.clock_in) }}</p>
                            <p v-if="attendance.clock_in_ip" class="text-xs text-muted-foreground">
                                IP: {{ attendance.clock_in_ip }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Clock Out</p>
                            <p class="text-lg font-medium">{{ formatTime(attendance.clock_out) }}</p>
                            <p v-if="attendance.clock_out_ip" class="text-xs text-muted-foreground">
                                IP: {{ attendance.clock_out_ip }}
                            </p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Net Hours</p>
                            <p class="text-lg font-medium">{{ formatMinutesToHours(attendance.net_minutes) }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm text-muted-foreground">Status</p>
                            <Badge v-if="attendance.is_late" variant="destructive">Late</Badge>
                            <Badge v-else variant="outline">On Time</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Audit Logs -->
            <Card v-if="attendance.audit_logs && attendance.audit_logs.length > 0">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <History class="h-5 w-5" />
                        Audit History
                    </CardTitle>
                    <CardDescription>Record of all changes made to this attendance</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div
                            v-for="log in attendance.audit_logs"
                            :key="log.id"
                            class="rounded-lg border p-4"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-medium">{{ log.field_changed }}</p>
                                    <p class="text-sm text-muted-foreground">
                                        Changed from <strong>{{ log.old_value || 'empty' }}</strong> to <strong>{{ log.new_value }}</strong>
                                    </p>
                                    <p class="mt-2 text-sm">
                                        <strong>Reason:</strong> {{ log.reason }}
                                    </p>
                                </div>
                                <div class="text-right text-sm text-muted-foreground">
                                    <p>{{ log.changed_by_user?.name }}</p>
                                    <p>{{ formatDateTime(log.created_at) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card v-else>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <History class="h-5 w-5" />
                        Audit History
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p class="text-muted-foreground">No changes have been made to this record.</p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
