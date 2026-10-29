<script setup lang="ts">

import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { AlertTriangle, ClipboardCheck, FileSpreadsheet, LayoutGrid, ShieldAlert, Users } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';
import AppLogoIcon from './AppLogoIcon.vue';

// ambil data user dari inertia
const page = usePage();

// sesuaikan dengan struktur backend kamu
const isAdmin = page.props.auth.user.role != 'anggota' && page.props.auth.user.is_admin === true;

const mainNavItems: NavItem[] = [
    {
        title: 'Dasbor',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Hukuman Saya',
        href: '/penalties',
        icon: AlertTriangle,
    },
];

const AdminNavItems: NavItem[] = [
    {
        title: 'Kelola Pengguna',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Absensi',
        href: '/admin/attendance',
        icon: ClipboardCheck,
    },
    {
        title: 'Laporan Mingguan',
        href: '/admin/weekly-report',
        icon: FileSpreadsheet,
    },
    {
        title: 'Kelola Hukuman',
        href: '/admin/penalties',
        icon: ShieldAlert,
    },
];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" class="h-20" as-child>
                        <Link :href="route('dashboard')" class="flex w-full items-center group-data-[collapsible=icon]:justify-center">
                            <AppLogo class="group-data-[collapsible=icon]:hidden"/>

                            <AppLogoIcon size="20px" class="hidden group-data-[collapsible=icon]:block"/>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain v-if="isAdmin" :items="AdminNavItems" label="Admin" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>