<script setup lang="ts">

interface Props {
    size?: string,
}

const props = defineProps<Props>()
</script>

<template>
    <div>
        <img src="/images/logo-dark-full.png" :style="{ width: size || '140px' }" class="h-auto dark:hidden" alt="logo">
        <img src="/images/logo-light-full.png" :style="{ width: size || '140px' }" class="h-auto hidden dark:block" alt="logo">
    </div>
</template>