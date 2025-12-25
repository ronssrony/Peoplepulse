<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import UserInfo from '@/components/UserInfo.vue';
import UserMenuContent from '@/components/UserMenuContent.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/50 bg-background px-6 transition-[width,height] ease-in-out md:px-6"
    >
        <div class="flex items-center gap-4">
            <SidebarTrigger class="-ml-1 h-8 w-8 text-muted-foreground hover:text-foreground" />
            <div class="h-4 w-px bg-border/50 mx-1"></div>
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger class="flex items-center gap-2 outline-none">
                    <div class="flex items-center gap-2 rounded-full border border-transparent px-2 py-1 transition-colors hover:bg-muted/50">
                        <UserInfo :user="user" />
                        <ChevronDown class="h-4 w-4 text-muted-foreground" />
                    </div>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-56">
                    <UserMenuContent :user="user" />
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
