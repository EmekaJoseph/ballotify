<template>
    <fieldset class="border rounded-3 p-4">
        <legend class="text-muted float-none small p-0 px-3 w-auto">2/2 - Organisation Info:</legend>
        <form>
            <div class="row gy-3">
                <div class="col-md-12">
                    <div class="form-floating">
                        <input v-model="orgData.name" type="text" class="form-control" id="flOrgName"
                            placeholder="firstname" />
                        <label for="flOrgName">Organisation Name:</label>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-floating">
                        <input v-model="orgData.address" type="text" class="form-control" id="flOrdAddr"
                            placeholder="lastname" />
                        <label for="flOrdAddr">Organisation Address:</label>
                    </div>
                </div>
                <div class="col-md-12 mt-5">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <button @click.prevent="emit('back')" type="submit"
                                class="btn btn-lg btn-secondary w-100"><i class="bi bi-arrow-left text-white"></i>
                                Back</button>
                        </div>
                        <div class="col-md-6">
                            <button @click.prevent="checkForm" type="submit" class="btn btn-lg w-100 customBtn">Create
                                Account</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </fieldset>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue'
import entryData from './user-data'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const emit = defineEmits(["back", 'done']);

const orgData = entryData.data.org
const resetError = entryData.methods.resetError

const pw = reactive({
    type: 'password',
    toggle: () => { pw.type = pw.type === 'password' ? 'text' : 'password' }
})

function checkForm() {
    emit('done')
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
</style>