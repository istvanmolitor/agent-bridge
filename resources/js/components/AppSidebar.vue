<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Bot,
    Folder,
    KeyRound,
    ScrollText,
    TerminalSquare,
    Users,
} from '@lucide/vue';
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
import { index as indexAdminAgents } from '@/routes/admin/agents';
import { index as indexAdminApiKeys } from '@/routes/admin/api-keys';
import { index as indexAdminLogs } from '@/routes/admin/logs';
import { index as indexAdminUsers } from '@/routes/admin/users';
import { index as indexAdminChat } from '@/routes/admin/chat';
import { index as indexAdminWorkspaces } from '@/routes/admin/workspaces';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Chat',
            href: indexAdminChat(),
            icon: TerminalSquare,
        },
    ];

    if (page.props.auth.user.is_admin) {
        items.push(
            {
                title: 'Ügynökök',
                href: indexAdminAgents(),
                icon: Bot,
            },
            {
                title: 'Munkaterületek',
                href: indexAdminWorkspaces(),
                icon: Folder,
            },
            {
                title: 'API kulcsok',
                href: indexAdminApiKeys(),
                icon: KeyRound,
            },
            {
                title: 'Felhasználók',
                href: indexAdminUsers(),
                icon: Users,
            },
            {
                title: 'Logok',
                href: indexAdminLogs(),
                icon: ScrollText,
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
                        <Link :href="indexAdminChat()">
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
