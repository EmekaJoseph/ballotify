<template>
    <div class="thForm">
        <transition name="xSlide" mode="out-in">
            <LoginForm v-if="formShowing == 'login'" @switch-form="switchTo" />
            <SignUpForm v-else @switch-form="switchTo" />
        </transition>
    </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'
import LoginForm from './LoginForm.vue'
import SignUpForm from './SignUpForm.vue'

const formShowing = ref('login')
function switchTo(name) {
    formShowing.value = name
}
</script>

<style scoped>
.thForm {
    min-height: 100vh;
    display: -ms-flexbox;
    display: flex;
    -ms-flex-pack: center;
    justify-content: center;
    -ms-flex-align: center;
    align-items: center;
    background: url("@/assets/images/hero-bg.png") center center;
    background-size: cover;
    background-color: #00BD8C21;
    transition: 1.8s ease-in-out;
}

.mini-footer {
    background-color: #eee;
    text-align: center;
    padding: 20px;
}



.xSlide-enter-active {
    transition: all 0.7s ease;
}

.xSlide-enter-from,
.xSlide-leave-to {
    transform: translateY(-20px);
    opacity: 0;
}
</style>