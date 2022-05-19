<template>
    <div class="col-md-10">
        <div class="row justify-content-center">
            <div class="col-11 col-lg-6 order-lg-1 order-2">
                <div class="card p-4 shadow-sm border-0">
                    <span class="text-center fw-bold mb-2">
                        <!-- <img src="@/assets/house34_logo1.png" width="130" /> -->
                    </span>
                    <newUserForm v-if="currForm == 1" @next="currForm = 2" />
                    <newOrgForm v-else @back="currForm = 1" @done="submitForm" />
                </div>
                <div class=" d-md-none text-center AcntBtn my-3" @click="emit('switchForm', 'login')">
                    <span class="btn">I already have an Account</span>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 sideWriteUp order-1 order-lg-2">
                <div class="container spanni">
                    <h2>SignUp</h2>
                    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Adipisci aspernatur ea et
                        dolorem.
                    </p>
                    <div class="d-none d-md-block text-center newOrgBtn" @click="emit('switchForm', 'login')">
                        <span class="btn">I have an Account</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { reactive, ref, inject } from 'vue'
import newUserForm from './newUserComponent.vue'
import newOrgForm from './newOrgComponent.vue'
import entryData from './user-data'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const emit = defineEmits(["switchForm"]);
const currForm = ref(1)


function submitForm() {
    console.log(entryData.data);
}
</script>

<style scoped>
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