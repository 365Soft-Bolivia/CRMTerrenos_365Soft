<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog'
interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <Toast position="top-right" />
    <ConfirmDialog>
        <template #message="slotProps">
            <div class="flex flex-col items-center w-full gap-4">
                <i :class="slotProps.message.icon" class="text-4xl text-primary"></i>
                <p class="text-sm" v-html="slotProps.message.message"></p>
            </div>
        </template>
    </ConfirmDialog>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>