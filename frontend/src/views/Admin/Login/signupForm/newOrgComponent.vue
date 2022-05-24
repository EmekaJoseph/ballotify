<template>
    <fieldset class="border rounded-3 p-4">
        <legend class="text-muted float-none small p-0 px-3 w-auto">2/2 - Organisation Info:</legend>
        <form>
            <div class="row gy-3">
                <div class="col-md-12">
                    <div class="form-floating">
                        <input :class="{ 'formError': err.name }" v-model="org.name" type="text" class="form-control"
                            id="flOrgName" placeholder="firstname" />
                        <label for="flOrgName">Organisation Name:</label>
                        <small class="text-danger" v-if="err.name">{{ err.name }}</small>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-floating">
                        <input :class="{ 'formError': err.address }" v-model="org.address" type="text"
                            class="form-control" id="flOrdAddr" placeholder="lastname" />
                        <label for="flOrdAddr">Organisation Address:</label>
                        <small class="text-danger" v-if="err.address">{{ err.address }}</small>
                    </div>
                </div>
                <div class="col-md-12 mt-5">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <button @click.prevent="emit('back')" type="submit"
                                class="btn btn-lg btn-secondary w-100"><i class="bi bi-arrow-left text-white"></i>
                                Back</button>
                        </div>
                        <div class="col-md-8">
                            <button :disabled="state.loading" @click.prevent="checkForm" type="submit"
                                class="btn btn-lg customBtn w-100">
                                <span class="text-white" v-if="!state.loading">Create Account <i
                                        class="bi bi-check-circle text-white"></i></span>
                                <span v-else class="spinner-border spinner-border-sm" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </fieldset>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue'
import userStore from '../user-data'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const emit = defineEmits(["back", 'done', "error"]);

const userMthds = userStore.methods
const state = userStore.state
const org = state.org
const err = org.err

async function checkForm() {
    userMthds.resetError()
    if (!org.name) {
        err.name = 'This field is empty'
        return
    }

    else if (!org.address) {
        err.address = 'This field is empty'
        return
    }
    else {
        let exists = await userMthds.orgExists(org.name)
        if (exists == null) {
            emit('error')
            return
        }
        else if (exists == true) {
            err.name = 'This name already exists'
            return
        }
        else {
            emit('done')
        }
    }

}


</script>

<style scoped>
.form-control {
    border: none;
    border-radius: 0px;
    border-bottom: 1px solid #eee;
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