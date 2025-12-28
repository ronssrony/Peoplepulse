<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Calendar as CalendarIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Calendar } from '@/components/ui/calendar';
import { cn } from '@/lib/utils';
import { Label } from '@/components/ui/label';

const props = defineProps<{
    startDate: string;
    endDate: string;
    className?: string;
}>();

const emit = defineEmits<{
    (e: 'update:startDate', value: string): void;
    (e: 'update:endDate', value: string): void;
    (e: 'apply'): void;
}>();

// Internal state to hold values before applying
const localStartDate = ref(props.startDate);
const localEndDate = ref(props.endDate);
const isOpen = ref(false);

const formatDateForDisplay = (start: string, end: string) => {
    if (!start && !end) return 'Pick a date range';
    if (start && !end) return formatDate(start);
    if (!start && end) return formatDate(end);
    return `${formatDate(start)} - ${formatDate(end)}`;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

const handleApply = () => {
    emit('update:startDate', localStartDate.value);
    emit('update:endDate', localEndDate.value);
    emit('apply');
    isOpen.value = false;
};

// Sync local state when props change (if closed)
watch(() => [props.startDate, props.endDate], ([newStart, newEnd]) => {
    if (!isOpen.value) {
        localStartDate.value = newStart;
        localEndDate.value = newEnd;
    }
});
</script>

<template>
    <div :class="cn('grid gap-2', props.className)">
        <Popover v-model:open="isOpen">
            <PopoverTrigger as-child>
                <Button
                    id="date"
                    variant="outline"
                    :class="cn(
                        'w-full justify-start text-left font-normal',
                        !startDate && !endDate && 'text-muted-foreground'
                    )"
                >
                    <CalendarIcon class="mr-2 h-4 w-4" />
                    {{ formatDateForDisplay(startDate, endDate) }}
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-auto p-0" align="start">
                <div class="flex flex-col sm:flex-row divide-y sm:divide-y-0 sm:divide-x">
                    <div class="p-3 space-y-3">
                        <Label class="text-xs font-semibold">Start Date</Label>
                        <Calendar v-model="localStartDate" class="rounded-md border shadow-none" />
                    </div>
                    <div class="p-3 space-y-3">
                        <Label class="text-xs font-semibold">End Date</Label>
                        <Calendar v-model="localEndDate" class="rounded-md border shadow-none" />
                    </div>
                </div>
                <div class="p-3 border-t bg-muted/50 flex justify-end">
                     <Button size="sm" @click="handleApply">Apply Range</Button>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>
