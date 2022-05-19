<template>
    <fieldset class="border rounded-3 p-4">
        <legend class="text-muted float-none small p-0 px-3 w-auto">1/2 - Personal Info</legend>
        <form>
            <div class="row gy-3">
                <div class="col-md-6">
                    <div class="form-floating">
                        <input v-model="user.firstname" type="text" class="form-control" id="flFirstname"
                            placeholder="firstname" />
                        <label for="flFirstname">First Name:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating">
                        <input v-model="user.lastname" type="text" class="form-control" id="flLastname"
                            placeholder="lastname" />
                        <label for="flLastname">Last Name:</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-floating">
                        <input v-model="user.email" type="text" class="form-control" id="flemail"
                            placeholder="username" />
                        <label for="flemail">Email:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating passF">
                        <input :type="pw.type" v-model="user.password" class="form-control" id="flpasswd1"
                            placeholder="password" />
                        <span @click="pw.toggle()">
                            <i v-if="(pw.type == 'password')" class="bi bi-eye-slash"></i>
                            <i v-else class="bi bi-eye"></i>
                        </span>
                        <label for="flpasswd1">Password:</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating passF">
                        <input :type="pw.type" v-model="user.rpassword" class="form-control" id="flpasswd2"
                            placeholder="password" />
                        <span @click="pw.toggle()">
                            <i v-if="(pw.type == 'password')" class="bi bi-eye-slash"></i>
                            <i v-else class="bi bi-eye"></i>
                        </span>
                        <label for="flpasswd2">Repeat:</label>
                    </div>
                </div>
                <div class="col-md-12 mt-5">
                    <button @click.prevent="checkForm" type="submit" class="btn btn-lg btn-secondary w-100">Continue <i
                            class="bi bi-arrow-right text-white"></i></button>
                </div>
            </div>
        </form>
    </fieldset>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue'
import entryData from './user-data'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const emit = defineEmits(["next"]);

const resetError = entryData.methods.resetError
const user = entryData.data.user

function checkForm() {
    emit('next')
}

const pw = reactive({
    type: 'password',
    toggle: () => { pw.type = pw.type === 'password' ? 'text' : 'password' }
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
</style>