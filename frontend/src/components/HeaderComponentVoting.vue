<script setup>
import { useRoute } from 'vue-router';
import { inject } from 'vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");
defineProps({
    scrolled: {
        type: Number,
        default: 0,
        required: true,
    }
})
const route = useRoute()
</script>

<template>
    <div>
        <header id="header" class="fixed-top d-flex align-items-center"
            :class="{ 'shadow-sm': scrolled > 50, 'bg-white': scrolled > 50 }">
            <div class="container d-flex align-items-center justify-content-center">
                <div class="logo">
                    <img src="@/assets/images/ballotify-logo.png" width="40" alt="">
                </div>
            </div>
        </header>
    </div>
</template>



<style scoped>
#header {
    z-index: 997;
    transition: all 0.5s;
    padding: 12px 0;
}
</style>