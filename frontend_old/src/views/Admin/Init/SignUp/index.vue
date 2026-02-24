<template>



    <div class="thForm">
        <nav class="navbar fixed-top">
            <div class="container">
                <div></div>
                <router-link class="homeBtn" to="/"><i class="bi bi-house-fill"></i> Home</router-link>
            </div>
        </nav>


        <div class="col-md-10">
            <div class="row justify-content-center">
                <div class="col-11 col-lg-7 order-lg-1 order-2">
                    <div class="card p-4 shadow-sm border-0">
                        <span class="text-center fw-bold mb-2">
                            <!-- <img src="@/assets/house34_logo1.png" width="130" /> -->
                        </span>
                        <newUserForm v-if="currForm == 1" @next="currForm = 2" @error="showErrorToast" />
                        <newOrgForm v-else @back="currForm = 1" @done="submitForm" @error="showErrorToast" />

                        <div class=" d-md-none text-center AcntBtn my-3" @click="router.push({name: 'Login'})">
                            <span class="btn">I already have an Account</span>
                        </div>
                    </div>

                </div>
                <div class="col-lg-4 col-md-12 sideWriteUp order-1 order-lg-2">
                    <div class="container spanni">
                        <h2>SignUp</h2>
                        <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Adipisci aspernatur ea et
                            dolorem.
                        </p>
                        <div class="d-none d-md-block text-center newOrgBtn" @click="router.push({name: 'Login'})">
                            <span class="btn">I have an Account</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>



</template>

<script lang="ts" setup>
import { ref, inject } from 'vue'
import newUserForm from './newUserComponent.vue'
import newOrgForm from './newOrgComponent.vue'
import userStore from '../user-data'
import { newRegister } from '@/types'
import server from '@/store/apiStore'
import Swal from 'sweetalert2'
import { useRouter } from 'vue-router'

const router = useRouter()
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const currForm = ref(1)

const state = userStore.state
const user = state.user
const org = state.org
const mthds = userStore.methods

function submitForm() {
    var obj: newRegister = {
        firstname: user.firstname,
        lastname: user.lastname,
        email: user.email,
        password: user.password,
        org_name: org.name,
        org_address: org.address
    }

    sendToServer(obj)
}

async function sendToServer(obj: object) {
    state.loading = true
    let _Data = window.btoa(JSON.stringify(obj))
    try {
        var { data } = await server.registerNew(_Data);
        switch (data) {
            case 1:
                showSuccessToast()
                state.loading = false
                mthds.resetData()
                router.push({ name: 'Login' })
                break;
            case 0:
                showErrorToast()
                state.loading = false
                break;

            default:
                showErrorToast()
                state.loading = false
                break;
        }
    } catch (error) {
        console.log(error);
        showErrorToast()
        state.loading = false
    }
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
    font-size: 14px;
    padding: 2px 7px;
    font-weight: bold;
    border-radius: 5px;
    margin-top: 10px;
    margin-right: 18px;
}

.homeBtn i {
    color: v-bind(cc1);
}

.homeBtn:hover {
    color: v-bind(cc1);
}

@media (max-width: 991px) {
    .navbar {
        background-color: #F5FCFA
    }
}




.newOrgBtn .btn {
    margin-top: 10px;
    cursor: pointer;
    border: none;
    color: v-bind(cc1);
    float: right;
    font-size: 20px;
}

.newOrgBtn .btn:hover {
    text-decoration: underline;
}

.sideWriteUp {
    display: flex;
    align-items: center;
    padding: 20px;
}

.sideWriteUp .spanni {
    background-color: #00bd8b07;
    padding: 20px;
    animation: up-down 2s ease-in-out infinite alternate-reverse both;
}

.AcntBtn .btn {
    color: v-bind(cc1);
    font-size: 20px;
}
</style>