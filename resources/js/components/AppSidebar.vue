<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { KeyRound, LayoutGrid, TerminalSquare } from '@lucide/vue';
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
import { index as indexAdminApiKeys } from '@/routes/admin/api-keys';
import { edit as editAdminPlayground } from '@/routes/admin/playground';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Vezérlőpult',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (page.props.auth.user.is_admin) {
        items.push(
            {
                title: 'API kulcsok',
                href: indexAdminApiKeys(),
                icon: KeyRound,
            },
            {
                title: 'Playground',
                href: editAdminPlayground(),
                icon: TerminalSquare,
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
