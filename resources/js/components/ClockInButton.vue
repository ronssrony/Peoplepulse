<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Loader2, LogIn, LogOut } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    status: 'idle' | 'working' | 'clocked_out';
    loading?: boolean;
    disabled?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    status: 'idle',
    loading: false,
    disabled: false,
});

const emit = defineEmits<{
    (e: 'click'): void;
}>();

const variant = computed(() => {
    switch (props.status) {
        case 'working':
            return 'outline'; // For Clock Out
        case 'clocked_out':
            return 'secondary'; // Completed
        default:
            return 'default'; // Clock In
    }
});

const label = computed(() => {
    switch (props.status) {
        case 'working':
            return 'Clock Out';
        case 'clocked_out':
            return 'Day Complete';
        default:
            return 'Clock In';
    }
});

const icon = computed(() => {
    if (props.loading) return Loader2;
    switch (props.status) {
        case 'working':
            return LogOut;
        case 'clocked_out':
            return null;
        default:
            return LogIn;
    }
});

const buttonClass = computed(() => {
    switch (props.status) {
        case 'idle':
            return 'bg-primary text-primary-foreground hover:bg-primary/90 shadow-md hover:shadow-lg active:scale-[0.98] transition-all duration-300 ease-out';
        case 'working':
            return 'border-destructive/20 text-destructive hover:bg-destructive/10 hover:border-destructive/50 transition-all duration-300';
        case 'clocked_out':
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 cursor-default shadow-none';
        default:
            return '';
    }
});
</script>

<template>
    <div class="relative inline-flex">
        <!-- Pulse effect for Idle state -->
        <div
            v-if="status === 'idle' && !loading"
            class="absolute -inset-1 rounded-lg bg-primary/20 opacity-0 transition-opacity duration-500 group-hover:opacity-100 animate-pulse"
        ></div>

        <Button
            :variant="variant"
            :size="status === 'clocked_out' ? 'default' : 'lg'"
            :disabled="disabled || loading || status === 'clocked_out'"
            :class="cn('relative z-10 min-w-[140px] font-medium', buttonClass)"
            @click="emit('click')"
        >
            <component
                :is="icon"
                v-if="icon"
                :class="cn('mr-2 h-5 w-5', loading ? 'animate-spin' : '')"
            />
            <span class="relative">
                {{ label }}
            </span>
        </Button>
    </div>
</template>
