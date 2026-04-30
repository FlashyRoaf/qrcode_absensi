<script setup lang="ts">

import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

// ambil data user dari inertia
const page = usePage();

// sesuaikan dengan struktur backend kamu
const isAdmin = page.props.auth.user.role === 'admin';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const AdminNavItems: NavItem[] = [
    {
        title: 'User Management',
        href: '/admin/users',
        icon: LayoutGrid,
    },
    {
        title: 'Attendance',
        href: '/admin/attendance',
        icon: LayoutGrid,
    },
    {
        title: 'Weekly Report',
        href: '/admin/weekly-report',
        icon: LayoutGrid,
    },
];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain v-if="isAdmin" :items="AdminNavItems" label="Admin"/>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>