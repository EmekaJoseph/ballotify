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
                    <div v-if="errMsg.length" class="errMsg">{{ errMsg }}</div>
                    <form>
                        <div class="row gy-3">
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <input :class="{ 'formError': err.username }" v-model="form.username" type="text"
                                        class="form-control" id="floatUserName" placeholder="username" />
                                    <label for="floatUserName">email:</label>
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

                                <button :disabled="loading" @click.prevent="checkForm" type="submit"
                                    class="btn btn-lg customBtn w-100">
                                    <span class="text-white" v-if="!loading">Login</span>
                                    <div v-else class="c-loader"></div>
                                </button>
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
import { reactive, inject, ref } from 'vue'
import server from '@/store/apiStore'
import { useRouter } from 'vue-router'
import userStore from '../user-data'
import { useAdminStore } from '@/store/user/admin'
import { LoginFormInterface } from '@/types'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const router = useRouter();
const userMthds = userStore.methods
const admin = useAdminStore()

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


function checkForm() {
    resetErr()
    if (form.username.length == 0) {
        err.username = 'This field is empty'
        return false
    }
    else if (form.password.length == 0) {
        err.password = 'This field is empty'
        return false
    }
    else if (!(userMthds.testEmail(form.username))) {
        err.username = 'Invalid email format'
        return
    }
    else {
        loginUser()
    }
}

const errMsg = ref('')
const loading = ref(false)
async function loginUser() {
    errMsg.value = ''
    let obj = {
        email: form.username,
        password: form.password
    }
    loading.value = true
    try {
        var { data } = await server.login(obj);
        if (data.status != 1) {
            errMsg.value = 'invalid credentials.'
            loading.value = false
        }
        else {
            let obj = data.data
            admin.signIn(obj)
            router.replace({ name: 'Dashboard' })
            loading.value = false
        }
    } catch (error) {
        console.log(error);
        errMsg.value = 'Sorry, Internet Error!'
        loading.value = false
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
    border-bottom: 1px solid var(--bs-danger);
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
    color: v-bind(cc2);
    text-decoration: underline;
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

.customBtn {
    background-color: v-bind(cc1);
    color: #fff;
}

.customBtn:hover {
    background-color: v-bind(ccBtnH);
    color: #fff;
}

.errMsg {
    background-color: #b936360e;
    padding: 5px;
    border-radius: 10px;
    margin: 10px;
    color: var(--bs-danger);
    text-align: center;
}
</style>