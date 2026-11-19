<script setup lang="ts">
import { Link, usePage } from "@inertiajs/vue3";
import { Bot, KeyRound, ScrollText, TerminalSquare } from "@lucide/vue";
import { computed } from "vue";
import AppLogo from "@/components/AppLogo.vue";
import NavMain from "@/components/NavMain.vue";
import NavUser from "@/components/NavUser.vue";
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from "@/components/ui/sidebar";
import { index as indexAdminAgents } from "@/routes/admin/agents";
import { index as indexAdminApiKeys } from "@/routes/admin/api-keys";
import { index as indexAdminLogs } from "@/routes/admin/logs";
import { edit as editAdminPlayground } from "@/routes/admin/playground";
import type { NavItem } from "@/types";

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: "Playground",
            href: editAdminPlayground(),
            icon: TerminalSquare,
        },
    ];

    if (page.props.auth.user.is_admin) {
        items.push(
            {
                title: "Ügynökök",
                href: indexAdminAgents(),
                icon: Bot,
            },
            {
                title: "API kulcsok",
                href: indexAdminApiKeys(),
                icon: KeyRound,
            },
            {
                title: "Logok",
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
                        <Link :href="editAdminPlayground()">
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
