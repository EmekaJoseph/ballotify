<template>
    <div class="col-md-10">
        <div class="row justify-content-center">
            <div class="col-11 col-lg-5 order-lg-1 order-2">
                <div class="card p-4 shadow-sm border-0">
                    <span class="text-center fw-bold mb-2">
                        <!-- <img src="@/assets/house34_logo1.png" width="130" /> -->
                    </span>
                    <small class="text-center fw-bold text-muted mb-2">Admin</small>
                    <form>
                        <div class="row gy-3">
                            <div class="col-md-12">
                                <div class="alert alert-danger small my-1 p-2 border-0" v-if="formError.msg"
                                    v-html="formError.msg"></div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input v-model="form.username" type="text" class="form-control" id="floatUserName"
                                        placeholder="username" />
                                    <label for="floatUserName">username:</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input v-model="form.username" type="text" class="form-control" id="floatUserName"
                                        placeholder="username" />
                                    <label for="floatUserName">username:</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input v-model="form.username" type="text" class="form-control" id="floatUserName"
                                        placeholder="username" />
                                    <label for="floatUserName">username:</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input v-model="form.username" type="text" class="form-control" id="floatUserName"
                                        placeholder="username" />
                                    <label for="floatUserName">username:</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating passF">
                                    <input :type="pw.type" v-model="form.password" class="form-control" id="floatpassw"
                                        placeholder="password" />
                                    <span @click="pw.toggle()">
                                        <i v-if="(pw.type == 'password')" class="bi bi-eye-slash"></i>
                                        <i v-else class="bi bi-eye"></i>
                                    </span>
                                    <label for="floatpassw">password:</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating passF">
                                    <input :type="pw.type" v-model="form.password" class="form-control" id="floatpassw"
                                        placeholder="password" />
                                    <span @click="pw.toggle()">
                                        <i v-if="(pw.type == 'password')" class="bi bi-eye-slash"></i>
                                        <i v-else class="bi bi-eye"></i>
                                    </span>
                                    <label for="floatpassw">password:</label>
                                </div>
                            </div>
                            <div class="col-md-12 mt-4">
                                <button type="submit" @click.prevent="loginUser"
                                    class="btn btn-lg w-100 customBtn">Continue</button>
                            </div>
                            <!-- <span class="forgot text-center small mt-4">
                                        <a href="#">I forgot my password </a>
                                    </span> -->
                        </div>
                    </form>
                </div>
                <div class=" d-md-none text-center AcntBtn my-3" @click="switchForm">
                    <span class="btn">I already have an Account</span>
                </div>
            </div>
            <div class="col-lg-4 col-md-12 sideWriteUp order-1 order-lg-2">
                <div class="container spanni">
                    <h2>SignUp</h2>
                    <p>Lorem ipsum, dolor sit amet consectetur adipisicing elit. Adipisci aspernatur ea et
                        dolorem.
                    </p>
                    <div class="d-none d-md-block text-center newOrgBtn" @click="switchForm">
                        <span class="btn">I have an Account</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script lang="ts" setup>
import { reactive, ref, inject } from 'vue'
import { useRouter } from 'vue-router'
import { LoginFormInterface } from '@/types'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const router = useRouter();

const emits = defineEmits(["switchForm"]);
function switchForm() {
    emits('switchForm', 'login')
}


const pw = reactive({
    type: 'password',
    toggle: () => { pw.type = pw.type === 'password' ? 'text' : 'password' }
})

// const errMsg = ref('')

const initialFormState: LoginFormInterface = {
    username: '',
    password: '',
    err: {
        msg: '',
        username: false,
        password: false,
    },
}


const form = reactive({ ...initialFormState });
const formError = reactive({ ...initialFormState.err });

function resetForm() {
    Object.assign(form, initialFormState);
}
function resetFormError() {
    Object.assign(formError, initialFormState.err);
}

function loginUser() {
    if (form.username.length == 0) {
        formError.msg = '<i class="bi bi-exclamation-circle"></i>&nbsp;Enter a name'
        return false
    }
    else if (form.password.length == 0) {
        formError.msg = '<i class="bi bi-exclamation-circle"></i>&nbsp;Enter a password biko'
        return false
    }
    else {
        router.replace({ name: 'Dashboard' })
        formError.msg = ''
        // user.signIn()
    }
}

</script>

<style scoped>
.form-control {
    border: none;
    border-radius: 0px;
    border-bottom: 1px solid #eee;
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

.customBtn {
    background-color: v-bind(cc1);
    color: #fff;
}

.customBtn:hover {
    background-color: v-bind(ccBtnH);
    color: #fff;
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