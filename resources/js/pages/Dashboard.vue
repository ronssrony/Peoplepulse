<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { type BreadcrumbItem, type Attendance, type AttendanceStats } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Clock, LogIn, LogOut, AlertTriangle, CheckCircle, CalendarDays, Timer, TrendingUp } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    todayAttendance: Attendance | null;
    stats: AttendanceStats;
    isWeekend: boolean;
    officeStartTime: string;
    currentTime: string;
}

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => page.props.auth.user);

const flash = computed(() => page.props.flash as { success?: string; error?: string });

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const isProcessing = ref(false);

const canClockIn = computed(() => {
    if (!props.todayAttendance) return true;
    return !props.todayAttendance.clock_in;
});

const canClockOut = computed(() => {
    if (!props.todayAttendance) return false;
    return props.todayAttendance.clock_in && !props.todayAttendance.clock_out;
});

const currentStatus = computed(() => {
    if (props.isWeekend) return 'weekend';
    if (!props.todayAttendance || !props.todayAttendance.clock_in) return 'not_clocked_in';
    if (props.todayAttendance.clock_out) return 'clocked_out';
    return 'working';
});

const formatTime = (dateString: string | null) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleTimeString('en-US', {
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

const clockIn = () => {
    isProcessing.value = true;
    router.post('/attendance/clock-in', {}, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};

const clockOut = () => {
    isProcessing.value = true;
    router.post('/attendance/clock-out', {}, {
        preserveScroll: true,
        onFinish: () => {
            isProcessing.value = false;
        },
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <!-- Flash Messages -->
            <Alert v-if="flash.success" class="border-green-500 bg-green-50 text-green-700">
                <CheckCircle class="h-4 w-4" />
                <AlertDescription>{{ flash.success }}</AlertDescription>
            </Alert>
            <Alert v-if="flash.error" variant="destructive">
                <AlertTriangle class="h-4 w-4" />
                <AlertDescription>{{ flash.error }}</AlertDescription>
            </Alert>

            <!-- Welcome Message -->
            <div>
                <h1 class="text-2xl font-bold">Welcome back, {{ user.name }}!</h1>
                <p class="text-muted-foreground">
                    {{ user.designation }}
                    <span v-if="user.sub_department"> / {{ user.sub_department }}</span>
                </p>
            </div>

            <!-- Today's Status Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Clock class="h-5 w-5" />
                        Today's Status
                    </CardTitle>
                    <CardDescription>
                        {{ new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <!-- Status -->
                        <div class="flex flex-col gap-2">
                            <span class="text-sm text-muted-foreground">Status</span>
                            <div>
                                <Badge v-if="currentStatus === 'weekend'" variant="secondary">Weekend</Badge>
                                <Badge v-else-if="currentStatus === 'not_clocked_in'" variant="destructive">Not Clocked In</Badge>
                                <Badge v-else-if="currentStatus === 'working'" variant="default" class="bg-green-600">Working</Badge>
                                <Badge v-else variant="outline">Day Complete</Badge>
                            </div>
                        </div>

                        <!-- Clock In Time -->
                        <div class="flex flex-col gap-2">
                            <span class="text-sm text-muted-foreground">Clock In</span>
                            <span class="text-lg font-semibold">
                                {{ formatTime(todayAttendance?.clock_in ?? null) }}
                            </span>
                        </div>

                        <!-- Clock Out Time -->
                        <div class="flex flex-col gap-2">
                            <span class="text-sm text-muted-foreground">Clock Out</span>
                            <span class="text-lg font-semibold">
                                {{ formatTime(todayAttendance?.clock_out ?? null) }}
                            </span>
                        </div>

                        <!-- Late Indicator -->
                        <div class="flex flex-col gap-2">
                            <span class="text-sm text-muted-foreground">Late</span>
                            <div class="flex items-center gap-2">
                                <AlertTriangle v-if="todayAttendance?.is_late" class="h-5 w-5 text-yellow-500" />
                                <CheckCircle v-else-if="todayAttendance?.clock_in" class="h-5 w-5 text-green-500" />

                                <span>{{ todayAttendance?.is_late ? 'Yes' : (todayAttendance?.clock_in ? 'No' : '-') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Clock In/Out Buttons -->
                    <div class="mt-6 flex flex-wrap items-center gap-4">
                        <Button
                            @click="clockIn"
                            :disabled="isProcessing || !canClockIn"
                            class="flex items-center gap-2"
                            size="lg"
                        >
                            <LogIn class="h-4 w-4" />
                            Clock In
                        </Button>
                        <Button
                            @click="clockOut"
                            :disabled="isProcessing || !canClockOut"
                            variant="outline"
                            class="flex items-center gap-2"
                            size="lg"
                        >
                            <LogOut class="h-4 w-4" />
                            Clock Out
                        </Button>
                        <div v-if="isWeekend" class="rounded-lg bg-blue-50 px-4 py-2 text-blue-700 dark:bg-blue-950 dark:text-blue-300">
                            📅 Note: Today is your weekend day
                        </div>
                        <div v-if="currentStatus === 'clocked_out'" class="rounded-lg bg-green-50 px-4 py-2 text-green-700 dark:bg-green-950 dark:text-green-300">
                            ✅ You've completed your workday!
                        </div>
                    </div>

                    <!-- Working Hours -->
                    <div v-if="todayAttendance?.net_minutes" class="mt-4 rounded-lg bg-muted p-4">
                        <div class="grid gap-4 md:grid-cols-3">
                            <div>
                                <span class="text-sm text-muted-foreground">Gross Hours</span>
                                <p class="text-lg font-semibold">{{ formatMinutesToHours(todayAttendance.gross_minutes) }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Break</span>
                                <p class="text-lg font-semibold">{{ formatMinutesToHours(todayAttendance.break_minutes) }}</p>
                            </div>
                            <div>
                                <span class="text-sm text-muted-foreground">Net Hours</span>
                                <p class="text-lg font-semibold">{{ formatMinutesToHours(todayAttendance.net_minutes) }}</p>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Monthly Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Days</CardDescription>
                        <CalendarDays class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <CardTitle class="text-3xl">{{ stats.total_days }}</CardTitle>
                        <p class="text-xs text-muted-foreground">This month</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Late Days</CardDescription>
                        <AlertTriangle class="h-4 w-4 text-yellow-500" />
                    </CardHeader>
                    <CardContent>
                        <CardTitle class="text-3xl text-yellow-600">{{ stats.late_days }}</CardTitle>
                        <p class="text-xs text-muted-foreground">This month</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Total Hours</CardDescription>
                        <Timer class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <CardTitle class="text-3xl">{{ stats.total_net_hours }}h</CardTitle>
                        <p class="text-xs text-muted-foreground">This month</p>
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <CardDescription>Avg Hours/Day</CardDescription>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <CardTitle class="text-3xl">{{ stats.average_net_hours }}h</CardTitle>
                        <p class="text-xs text-muted-foreground">This month</p>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
