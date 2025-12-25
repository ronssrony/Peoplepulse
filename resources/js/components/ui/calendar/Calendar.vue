<script setup lang="ts">
import { cn } from "@/lib/utils";
import { ChevronLeft, ChevronRight } from "lucide-vue-next";
import { computed, ref } from "vue";

const props = defineProps<{
    modelValue: string | null;
    class?: string;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", value: string): void;
}>();

const currentDate = ref(new Date());
const selectedDate = computed(() =>
    props.modelValue ? new Date(props.modelValue) : null
);

const daysInMonth = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    return new Date(year, month + 1, 0).getDate();
});

const firstDayOfMonth = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    return new Date(year, month, 1).getDay();
});

const monthName = computed(() => {
    return currentDate.value.toLocaleString("default", { month: "long" });
});

const year = computed(() => {
    return currentDate.value.getFullYear();
});

const prevMonth = () => {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() - 1,
        1
    );
};

const nextMonth = () => {
    currentDate.value = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth() + 1,
        1
    );
};

const selectDate = (day: number) => {
    const date = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth(),
        day
    );
    // Format as YYYY-MM-DD for consistency with input type="date"
    const formatted = date.toLocaleDateString('en-CA');
    emit("update:modelValue", formatted);
};

const isSelected = (day: number) => {
    if (!selectedDate.value) return false;
    const date = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth(),
        day
    );
    return date.toDateString() === selectedDate.value.toDateString();
};

const isToday = (day: number) => {
    const today = new Date();
    const date = new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth(),
        day
    );
    return date.toDateString() === today.toDateString();
};
</script>

<template>
    <div :class="cn('p-3', props.class)">
        <div class="flex items-center justify-between space-x-4 pb-4">
            <button
                @click="prevMonth"
                type="button"
                class="hover:bg-accent hover:text-accent-foreground inline-flex h-7 w-7 items-center justify-center whitespace-nowrap rounded-md p-0 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-transparent shadow-sm opacity-50 hover:opacity-100"
            >
                <ChevronLeft class="h-4 w-4" />
            </button>
            <div class="text-sm font-medium">
                {{ monthName }} {{ year }}
            </div>
            <button
                @click="nextMonth"
                type="button"
                class="hover:bg-accent hover:text-accent-foreground inline-flex h-7 w-7 items-center justify-center whitespace-nowrap rounded-md p-0 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-transparent shadow-sm opacity-50 hover:opacity-100"
            >
                <ChevronRight class="h-4 w-4" />
            </button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-xs">
            <div class="text-muted-foreground w-8 font-normal rounded-md py-1">Su</div>
            <div class="text-muted-foreground w-8 font-normal rounded-md py-1">Mo</div>
            <div class="text-muted-foreground w-8 font-normal rounded-md py-1">Tu</div>
            <div class="text-muted-foreground w-8 font-normal rounded-md py-1">We</div>
            <div class="text-muted-foreground w-8 font-normal rounded-md py-1">Th</div>
            <div class="text-muted-foreground w-8 font-normal rounded-md py-1">Fr</div>
            <div class="text-muted-foreground w-8 font-normal rounded-md py-1">Sa</div>
            <div
                v-for="i in firstDayOfMonth"
                :key="'empty-' + i"
                class="w-8 h-8 pointer-events-none"
            ></div>
            <button
                v-for="day in daysInMonth"
                :key="day"
                @click="selectDate(day)"
                type="button"
                :class="cn(
                    'inline-flex h-8 w-8 items-center justify-center rounded-md p-0 text-sm font-normal ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 aria-selected:opacity-100 hover:bg-accent hover:text-accent-foreground',
                    isSelected(day) && 'bg-primary text-primary-foreground hover:bg-primary hover:text-primary-foreground focus:bg-primary focus:text-primary-foreground',
                     isToday(day) && !isSelected(day) && 'bg-accent text-accent-foreground'
                )"
            >
                {{ day }}
            </button>
        </div>
    </div>
</template>
