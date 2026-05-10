<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

// defineOptions({
//     inheritAttrs: false,
// });

interface Props {
    size?: string,
    className?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <div>
        <img src="/images/logo-dark-icon.png" :style="{ width: size || '48px' }" class="h-auto dark:hidden" alt="logo">
        <img src="/images/logo-light-icon.png" :style="{ width: size  || '48px' }" class="h-auto hidden dark:block" alt="logo">
    </div>
</template>
