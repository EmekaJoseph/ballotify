<template>
    <div class="col-md-10">
        <div class="row justify-content-center">
            <div class="col-lg-4 col-md-12 sideWriteUp">
                <div class="container spanni">
                    <h2>Login</h2>
                    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Adipisci aspernatur ea et
                        dolorem.
                    </p>
                    <div class="d-none d-md-block text-center newOrgBtn" @click="switchForm">
                        <span class="btn">Create new Organisation?</span>
                    </div>
                </div>
            </div>
            <div class="col-11 col-lg-4">
                <div class="card p-4 shadow-sm border-0">
                    <span class="text-center fw-bold mb-2">
                        <!-- <img src="@/assets/house34_logo1.png" width="130" /> -->
                    </span>
                    <small class="text-center fw-bold text-muted mb-2">Admin</small>
                    <form>
                        <div class="row gy-3">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input :class="{ 'formError': err.username }" v-model="form.username" type="text"
                                        class="form-control" id="floatUserName" placeholder="username" />
                                    <label for="floatUserName">username:</label>
                                    <small class="text-danger" v-if="err.username">{{ err.username
                                    }}</small>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-floating passF">
                                    <input :class="{ 'formError': err.password }" :type="pw.type"
                                        v-model="form.password" class="form-control" id="floatpassw"
                                        placeholder="password" />
                                    <span @click="pw.toggle()">
                                        <i v-if="(pw.type == 'password')" class="bi bi-eye-slash"></i>
                                        <i v-else class="bi bi-eye"></i>
                                    </span>
                                    <label for="floatpassw">password:</label>
                                    <small class="text-danger" v-if="err.password">{{ err.password
                                    }}</small>
                                </div>
                            </div>
                            <div class="col-md-12 mt-4">
                                <button type="submit" @click.prevent="loginUser"
                                    class="btn btn-lg w-100 customBtn">Login</button>
                            </div>
                            <span class="forgot text-center small mt-4">
                                <a href="#">I forgot my password </a>
                            </span>
                        </div>
                    </form>
                </div>
                <div class="d-md-none text-center AcntBtn my-3" @click="switchForm">
                    <span class="btn">Create new Organisation?</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { reactive, ref, inject } from 'vue'
import { useRouter } from 'vue-router'
import { LoginFormInterface } from '@/types'

const router = useRouter();

const emits = defineEmits(["switchForm"]);
function switchForm() {
    emits('switchForm', 'signup')
}


const pw = reactive({
    type: 'password',
    toggle: () => { pw.type = pw.type === 'password' ? 'text' : 'password' }
})


const initialFormState: LoginFormInterface = {
    username: '',
    password: '',
    err: {
        username: '',
        password: '',
    },
}


const form = reactive({ ...initialFormState });
const err = reactive({ ...initialFormState.err });

function resetForm() {
    Object.assign(form, initialFormState);
}
function resetErr() {
    Object.assign(err, initialFormState.err);
}

function loginUser() {
    resetErr()
    if (form.username.length == 0) {
        err.username = 'This field is empty'
        return false
    }
    else if (form.password.length == 0) {
        err.password = 'This field is empty'
        return false
    }
    else {
        router.replace({ name: 'Dashboard' })
        resetForm()
    }
}

</script>

<style scoped>
.form-control {
    border: none;
    border-radius: 0px;
    border-bottom: 1px solid #eee;
}

.formError {
    border-bottom: 1px solid #dc3545;
}

small {
    font-size: 12px;
}

.passF i {
    position: absolute;
    margin-top: 21px;
    margin-right: 10px;
    right: 0;
    top: 0;
    font-size: 20px;
    cursor: pointer;
}

.forgot a {
    text-decoration: none;
    /* font-weight: bold; */
}

.forgot a:hover {
    color: #BD2c00;
    text-decoration: underline;
}

.customBtn {
    background-color: #00BD8C;
    color: #fff;
}

.customBtn:hover {
    background-color: #05d8a0;
}

.newOrgBtn .btn {
    margin-top: 10px;
    cursor: pointer;
    border: none;
    color: #00BD8C;
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
    color: #00BD8C;
    font-size: 20px;
}
</style>