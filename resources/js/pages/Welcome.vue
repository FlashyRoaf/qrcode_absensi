<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useWindowSize } from '@vueuse/core';
import Login from './auth/Login.vue';
import WelcomeContent from '@/components/WelcomeContent.vue';

const { width } = useWindowSize()
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div v-if="width < 1024">
        <div class="w-full">
            <WelcomeContent :isMobile="true" imgSize="40vh" />
            <div class="flex items-center justify-center">
                <Link :href="route('login')"
                    class="bg-[#BAD5FF] dark:bg-[#9eb4d8] w-[70vw] inline-flex items-center justify-center rounded-xl border font-bold px-6 py-3">
                    Masuk
                </Link>
            </div>
        </div>
    </div>

    <Login v-else :status="route().params.status"
        :can-reset-password="route().has('password.request')" />

</template>