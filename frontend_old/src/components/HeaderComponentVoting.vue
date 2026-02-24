<script setup>
import { useRoute } from 'vue-router';
import { inject } from 'vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");
const prop = defineProps({
    scrolled: {
        type: Number,
        default: 0,
        required: true,
    },
    name: {
        type: String,
        default: '',
        required: false
    }
})
const route = useRoute()
</script>

<template>
    <div>
        <header id="header" class="fixed-top"
            :class="{ 'shadow-sm': prop.scrolled > 50, 'bg-custom': prop.scrolled > 50 }">
            <div class="container d-flex justify-content-between">
                <div v-show="prop.scrolled > 50" class="text-white thisName">{{prop.name}}</div>
                <!-- <div class="container d-flex align-items-center justify-content-center"> -->
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

.bg-custom {
    background-color: v-bind(cc1);
    border-bottom: 2px solid v-bind(cc2);
}

.thisName {
    font-weight: bold;
    margin-top: 5px;
    text-transform: uppercase;
}
</style>