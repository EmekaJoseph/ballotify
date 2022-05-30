<template>
    <fieldset class="border rounded-3 p-4">
        <legend class="text-muted float-none small p-0 px-3 w-auto">1/2 - Personal Info</legend>

        <form>
            <div class="row gy-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input :class="{ 'formError': err.firstname }" v-model="user.firstname" type="text"
                            class="form-control" id="flFirstname" placeholder="firstname" />
                        <label for="flFirstname">First Name:</label>
                        <small class="text-danger" v-if="err.firstname">{{ err.firstname }}</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input :class="{ 'formError': err.lastname }" v-model="user.lastname" type="text"
                            class="form-control" id="flLastname" placeholder="lastname" />
                        <label for="flLastname">Last Name:</label>
                        <small class="text-danger" v-if="err.lastname">{{ err.lastname }}</small>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-floating">
                        <input :class="{ 'formError': err.email }" v-model="user.email" type="text" class="form-control"
                            id="flemail" placeholder="username" />
                        <label for="flemail">Email:</label>
                        <small class="text-danger" v-if="err.email">{{ err.email }}</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating passF">
                        <input :class="{ 'formError': err.password }" :type="pw.pw1" v-model="user.password"
                            class="form-control" id="flpasswd1" placeholder="password" />
                        <span @click="pw.toggle('pw1')">
                            <i v-if="(pw.pw1 == 'password')" class="bi bi-eye-slash"></i>
                            <i v-else class="bi bi-eye"></i>
                        </span>
                        <label for="flpasswd1">Password:</label>
                        <small class="text-danger" v-if="err.password">{{ err.password }}</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating passF">
                        <input :class="{ 'formError': err.rpassword }" :type="pw.pw2" v-model="user.rpassword"
                            class="form-control" id="flpasswd2" placeholder="password"
                            :disabled="!(user.password.length > 1)" />

                        <span @click="pw.toggle('pw2')">
                            <i v-if="(pw.pw2 == 'password')" class="bi bi-eye-slash"></i>
                            <i v-else class="bi bi-eye"></i>
                        </span>
                        <label for="flpasswd2">Repeat:</label>
                        <small class="text-danger" v-if="err.rpassword">{{ err.rpassword }}</small>
                    </div>
                </div>
                <div class="col-md-12 mt-5">
                    <button :disabled="state.loading" @click.prevent="checkForm" type="submit"
                        class="btn btn-lg btn-secondary w-100">
                        <span class="text-white" v-if="!state.loading">Continue <i
                                class="bi bi-arrow-right text-white"></i></span>
                        <div v-else class="c-loader"></div>
                    </button>
                </div>
            </div>
        </form>
    </fieldset>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue'
import userStore from '../user-data'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const emit = defineEmits(["next", "error"]);

const userMthds = userStore.methods
const state = userStore.state
const user = state.user
const err = user.err

async function checkForm() {
    userMthds.resetError()
    if (!user.firstname) {
        err.firstname = 'This field is empty'
        return
    }
    else if (!user.lastname) {
        err.lastname = 'This field is empty'
        return
    }
    else if (!user.email) {
        err.email = 'This field is empty'
        return
    }
    else if (!user.password || (user.password.length < 5)) {
        err.password = 'enter password not less than 5 characters'
        return
    }
    else if (!user.rpassword) {
        err.rpassword = 'repeat password'
        return
    }
    else if (user.password != user.rpassword) {
        err.rpassword = 'passwords do not match'
        return
    }
    else if (!(userMthds.testEmail(user.email))) {
        err.email = 'This emails looks invalid'
        return
    }
    else {
        let exists = await userMthds.emailExists(user.email)
        if (exists == null) {
            emit('error')
            return
        }
        else if (exists == true) {
            err.email = 'email already exists'
            return
        }
        else {
            emit('next')
        }
    }

}


const pw = reactive({
    pw1: 'password',
    pw2: 'password',
    toggle: (field) => { pw[field] = pw[field] === 'password' ? 'text' : 'password' }
})

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

.formError {
    border-bottom: 1px solid var(--bs-danger);
}
</style>