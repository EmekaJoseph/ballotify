<template>
    <div class="thForm">
        <nav class="navbar fixed-top bg-none">
            <div class="container">
                <div></div>
                <router-link class="homeBtn" to="/"><i class="bi bi-house-fill"></i></router-link>
            </div>
        </nav>
        <transition name="xSlide" mode="out-in">
            <LoginForm v-if="formShowing == 'login'" @switch-form="switchTo" />
            <SignUpForm v-else @switch-form="switchTo" @error="showErrorToast" @success="state.regSuccess = true" />
        </transition>
        <notify ref="toast" />
        <reg-success v-if="state.regSuccess" @clicked="state.regSuccess = false" />
    </div>
</template>

<script setup>
import { ref, inject } from 'vue'
import LoginForm from './LoginForm.vue'
import SignUpForm from './signupForm/index.vue'
import regSuccess from './regSuccessModal.vue'
import userStore from './user-data'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");

const state = userStore.state


const formShowing = ref('login')
function switchTo(name) {
    formShowing.value = name
}


const toast = ref(null)
function showErrorToast() {
    toast.value.showToast('Sorry error occoured, try again', 'danger');
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

.xSlide-enter-active {
    transition: all 0.7s ease;
}

.xSlide-enter-from,
.xSlide-leave-to {
    transform: translateY(-20px);
    opacity: 0;
}

.homeBtn {
    text-decoration: none;
    background-color: v-bind(ccBg);
    font-size: 18px;
    padding: 2px 7px;
    font-weight: bold;
    border-radius: 5px;
    margin-top: 10px;
    margin-right: 18px;
}

.homeBtn i {
    color: v-bind(cc1);
}
</style>