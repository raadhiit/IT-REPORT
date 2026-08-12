<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Activity, CalendarCheck, ClipboardList, FileBarChart, LayoutGrid, Mail, Users } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as activitiesIndex } from '@/routes/activities';
import { index as complianceIndex } from '@/routes/admin/compliance';
import { index as monitoringIndex } from '@/routes/admin/monitoring';
import { edit as reportSettingsEdit } from '@/routes/admin/report-settings';
import { index as usersIndex } from '@/routes/admin/users';
import { weekly as weeklyReport } from '@/routes/reports';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Activities',
            href: activitiesIndex(),
            icon: ClipboardList,
        },
        {
            title: 'Weekly Report',
            href: weeklyReport(),
            icon: FileBarChart,
        },
    ];

    if (page.props.auth.user.role === 'admin') {
        items.push(
            {
                title: 'Compliance',
                href: complianceIndex(),
                icon: CalendarCheck,
            },
            {
                title: 'Users',
                href: usersIndex(),
                icon: Users,
            },
            {
                title: 'Report Settings',
                href: reportSettingsEdit(),
                icon: Mail,
            },
            {
                title: 'Monitoring',
                href: monitoringIndex(),
                icon: Activity,
            },
        );
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
                        <Link :href="dashboard()">
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
