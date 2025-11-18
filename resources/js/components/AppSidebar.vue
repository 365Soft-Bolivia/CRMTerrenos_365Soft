<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
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
import AppLogo from './AppLogo.vue';
import { useAuth } from '@/composables/useAuth';
import { computed } from 'vue';
import { allMainNavItems, leadsNavItems } from '@/config/navigation';
import type { NavItem } from '@/types';

const { hasAnyRole } = useAuth();

const mainNavItems = computed(() =>
  allMainNavItems.filter(item => hasAnyRole(item.roles || []))
);

const footerNavItems: NavItem[] = [];
</script>

<template>
  <Sidebar collapsible="icon" variant="inset">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <SidebarMenuButton size="lg" as-child>
            <AppLogo />
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>

    <SidebarContent>
      <NavMain :items="mainNavItems" />
      
      <!-- Nuevo: Sección de Leads -->
      <NavMain :items="leadsNavItems" label="Leads" />
    </SidebarContent>

    <SidebarFooter>
      <NavFooter :items="footerNavItems" />
      <NavUser />
    </SidebarFooter>
  </Sidebar>
  <slot />
</template>