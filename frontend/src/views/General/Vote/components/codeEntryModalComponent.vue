<template>
    <div>
        <div class="modal fade" id="codeEntryModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header"> Enter Code:
                        <span class="float-end">
                            <button ref="btnX" class="btn btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </span>
                    </div>
                    <form>
                        <div class="modal-body p-4">
                            <div class="row justify-content-center gy-3">
                                <div class="col-md-12">
                                    <input placeholder="enter here..." v-model="codeForm.code" type="text"
                                        class="form-control w-100">
                                    <small v-if="codeForm.error" class="text-danger">{{ codeForm.error }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0">
                            <button v-if="!codeForm.isChecking" @click.prevent="checkCode"
                                class="btn btn-link btn-lg fw-bold float-end">
                                Continue</button>
                            <button v-else class="btn btn-link btn-lg fw-bold text-muted" disabled>
                                Checking...</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue';
import server from '@/store/apiStore'
import { voteStore } from '../voteStore'
import { storeToRefs } from 'pinia';

const vSt = voteStore()
const { event } = storeToRefs(vSt)

const emit = defineEmits(["found"]);

const codeForm = reactive({
    code: '',
    error: '',
    isChecking: false
})
const btnX = ref<any>(null)
async function checkCode() {
    codeForm.error = ''
    if (!codeForm.code || codeForm.code.length < 4) {
        codeForm.error = 'invalid code format'
        return
    }
    codeForm.isChecking = true
    try {
        var { data } = await server.checkVotingCode(codeForm.code)
        if (data.status == 1) {
            if (data.event_id == event.value.event_id) {
                codeForm.isChecking = false;
                emit('found', data.voter_id)
            }
            else {
                codeForm.isChecking = false
                codeForm.error = 'invalid code'
                return
            }
        }
        else {
            if (data.status == 0) {
                codeForm.isChecking = false
                codeForm.error = 'invalid code'
                return
            }
            else {
                codeForm.isChecking = false
                codeForm.error = 'code already used'
                return
            }
        }
    } catch (error) {
        console.log(error);
        codeForm.isChecking = false
    }
}
</script>

<style>
.form-control {
    border: none;
    border-bottom: 1px solid #ccc;
    border-radius: 0px;
}

.form-control:focus {
    border-bottom: 1px solid #999797;
}
</style>