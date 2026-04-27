<template>
    <h1>Scanned</h1>
    <p v-if="props.message">{{ props.message }}</p>
</template>

<script setup lang="ts">
interface Props {
    message?: string;
}

const props = defineProps<Props>();
</script>