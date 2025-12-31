<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Clock, LayoutGrid, Users, Shield, UserCog, FileBarChart } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'My Attendance',
            href: '/attendance',
            icon: Clock,
        },
    ];

    // Add manager dashboard for managers only (not admins)
    if (user.value?.role === 'manager') {
        items.push({
            title: 'Team Attendance',
            href: '/attendance/manager',
            icon: Users,
        });
    }

    // Add reports for managers and admins
    if (user.value?.role === 'manager' || user.value?.role === 'admin') {
        items.push({
            title: 'Reports',
            href: '/attendance/reports',
            icon: FileBarChart,
        });
    }

    // Add admin dashboard for admins only
    if (user.value?.role === 'admin') {
        items.push({
            title: 'Admin Panel',
            href: '/attendance/admin',
            icon: Shield,
        });
        items.push({
            title: 'Employee Management',
            href: '/employees',
            icon: UserCog,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- User menu moved to header -->
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
