<script setup lang="ts">
import type { SelectTriggerProps } from 'reka-ui';
import { SelectIcon, SelectTrigger, SelectValue, useForwardProps } from 'reka-ui';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';

const props = withDefaults(defineProps<SelectTriggerProps & { class?: string; placeholder?: string }>(), {
    class: '',
});

const forwarded = useForwardProps(props);
</script>

<template>
    <SelectTrigger
        v-bind="forwarded"
        :class="cn(
            'flex h-9 w-full items-center justify-between whitespace-nowrap rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1',
            props.class
        )"
    >
        <slot>
            <SelectValue :placeholder="placeholder" />
        </slot>
        <SelectIcon as-child>
            <ChevronDown class="h-4 w-4 opacity-50" />
        </SelectIcon>
    </SelectTrigger>
</template>
