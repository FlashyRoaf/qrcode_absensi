<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import { ref, onMounted, computed } from 'vue';
import AppLogoIcon from './AppLogoIcon.vue';
import { useWindowSize } from '@vueuse/core';

const { width } = useWindowSize();

interface Props {
    isMobile?: boolean;
    imgSize?: string;
}

const props = defineProps<Props>();

const isDark = ref(false);
let observer: MutationObserver | null = null;

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
});

const glowStyle = computed(() => {
    return isDark.value
        ? 'background: radial-gradient(circle at 50% 50%, rgba(158, 180, 216, 0.35) 0%, transparent 65%)'
        : 'background: radial-gradient(circle at 50% 50%, rgba(158, 180, 216, 0.2) 0%, transparent 65%)';
});

</script>

<template>
    <div class="relative overflow-hidden flex flex-1 flex-col justify-center bg-white/10 dark:bg-zinc-950 gap-6 p-8">
        <div class="absolute inset-0 pointer-events-none rounded-2xl" :style="glowStyle">
        </div>
        <!-- Taruh desain kamu di sini -->
        <div class="grid grid-cols-3 items-center gap-1">
            <div class="flex justify-start">
                <AppLogoIcon v-if="width < 768" />
                <AppLogo v-else size="140px" />
            </div>
            <span class="font-black text-center" :class="width < 1120 ? 'text-3xl' : 'text-5xl'">
                SK<span class="text-[#9ED8C3]">A</span>QS
            </span>
        </div>
        <div class="font-black text-center" :class="isMobile ? 'text-2xl' : 'text-4xl'">
            <span>Hadir untuk berkarya, </span>
            <span
                class="bg-gradient-to-r from-[#d4a29b] to-[#9eb4d8] dark:from-[#FFC3BA] dark:to-[#BAD5FF] bg-clip-text text-transparent">scan
            </span>
            <span>untuk </span>
            <span
                class="bg-gradient-to-r from-[#9eb4d8] to-[#9ed8c3] dark:from-[#BAD5FF] dark:to-[#BAFFE5] bg-clip-text text-transparent">memulai</span>
            <span>.</span>
        </div>

        <div class="flex items-center justify-center">
            <img src="/images/login-img-light.svg" :style="{ maxHeight: imgSize || '65vh' }"
                class="w-auto object-contain dark:hidden" alt="img">
            <img src="/images/login-img-dark.svg" :style="{ maxHeight: imgSize || '65vh' }"
                class="w-auto object-contain hidden dark:block" alt="img">
        </div>
    </div>
</template>