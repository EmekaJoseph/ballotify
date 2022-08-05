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
            <SignUpForm v-else @switch-form="switchTo" @error="showErrorToast" @success="showSuccessToast" />
        </transition>
    </div>
</template>

<script setup lang="ts">
import { ref, inject } from 'vue'
import LoginForm from './loginForm/index.vue'
import SignUpForm from './signupForm/index.vue'
import Swal from 'sweetalert2'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");

const formShowing = ref('login')
function switchTo(name) {
    formShowing.value = name
}

function showErrorToast() {
    Swal.fire({
        toast: true,
        icon: 'error',
        title: 'Sorry error occoured, try again',
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: false,
    })
}

function showSuccessToast() {
    Swal.fire({
        icon: 'success',
        title: 'Registration Successful',
        text: 'Continue to login',
        confirmButtonColor: '#03787c',
        confirmButtonText: 'Continue'
    })
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
    overflow: hidden;
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