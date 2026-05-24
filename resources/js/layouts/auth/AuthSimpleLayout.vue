<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import WelcomeContent from '@/components/WelcomeContent.vue';
import { Link } from '@inertiajs/vue3';
import { useWindowSize } from '@vueuse/core';

defineProps<{
    title?: string;
    description?: string;
}>();

const { width } = useWindowSize();
</script>

<template>
    <div class="flex min-h-svh bg-background">
        <WelcomeContent v-if="width >= 768" class="hidden lg:flex" />

        <!-- ── KANAN: form (sama persis seperti sebelumnya) ── -->
        <div
            class="flex w-full lg:w-[560px] flex-col items-center justify-center gap-6 p-6 md:p-10 border-l border-border">
            <div class="w-full max-w-sm">
                <div class="flex flex-col gap-8">
                    <div class="flex flex-col items-center gap-4">
                        <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                            <div class="lg:hidden mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon />
                            </div>
                            <span class="sr-only">{{ title }}</span>
                        </Link>
                        <div class="space-y-2 text-center">
                            <h1 class="text-xl font-medium">{{ title }}</h1>
                            <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                        </div>
                    </div>
                    <slot />
                </div>
            </div>
        </div>

    </div>
</template>