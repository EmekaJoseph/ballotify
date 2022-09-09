<template>
    <div class="card px-2">
        <div class="card-head"><i class="bi bi-person-check-fill"></i> Eligible Voters:
            <span class="badge rounded-pill bg-secondary text-white fw-light">
                {{  list.length  }}
            </span>
        </div>
        <div class="card-body">
            <div class="row justify-content-center gy-3">
                <div class="col-12 col-xl-6">
                    <v-select v-model="voter.details" placeholder="select voter.." :clearable="false" class="vSelect"
                        :options="data.members" />
                </div>
                <div class="col-12 col-xl-4">
                    <button @click.prevent="" class="w-100 btn btn-outline-secondary">Add by groups <i
                            class="bi  bi-folder fs-6"></i></button>
                </div>
                <div class="col-12 col-xl-2">
                    <button @click.prevent="" class="w-100 btn customBtn">Add <i
                            class="bi text-white bi-arrow-down-circle fs-6"></i></button>
                </div>

                <div class="col-md-12 col-lg-12 mt-4">
                    <small v-if="voter.inputError" class="text-danger">{{
                         voter.inputError 
                        }}</small>
                    <div class="list-span">
                        <div v-if="!(list.length)" class="text-center empty-list-text mt-5 pt-5">
                            List is Empty
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-sm text-nowrap text-capitalize">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Post</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(cand, index) in list">
                                        <th>{{  index + 1  }}</th>
                                        <td>
                                            {{  m_name(cand.member_id)  }}
                                        </td>
                                        <td>
                                            {{  p_name(cand.position_id)  }}
                                        </td>
                                        <td>
                                            <button @click="removeCandidate(cand.id)"
                                                class="btn btn-sm btn-link text-danger remove-btn">Remove</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, computed, watchEffect, ref, onMounted } from 'vue';
import server from '@/store/apiStore'
import { dataStore } from '@/store/admin/dataStore';
import { useImageUpload } from "@/store/useImageUpload";
import { eventStore } from '../eventStore';
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'

const mStore = dataStore()
const { members }: any = storeToRefs(mStore)

const evtStore = eventStore()
const { positions, event, candidates: list }: any = storeToRefs(evtStore)

interface Voter {
    details: any,
    post: any,
    info?: string,
    inputError: string
    isSaving: boolean
}

const voter: Voter = reactive({
    details: null,
    post: null,
    inputError: '',
    isSaving: false
})


const data: any = reactive({
    members: computed(() => {
        let data = members.value.map((x: any) => ({ id: x.id, label: x.firstname + ' ' + x.lastname + ', (' + x.gender + ')' }))
        return data
    }),

})

const m_name = (id: string) => {
    let thisMember = members.value.find((x: { id: string; }) => x.id == id)
    return thisMember == undefined ? '...' : `${thisMember.firstname} ${thisMember.lastname} (${thisMember.gender})`
}

const p_name = (id: string) => {
    let thisPosition = positions.value.find((x: { id: string; }) => x.id == id)
    return thisPosition == undefined ? '...' : `${thisPosition.name}`
}





// image###########################################
let { handleFileUpload, imageURL, imageFile, imgSize } = useImageUpload();
const mainImgBtn = ref<any>(null);
const ImgForm = ref<any>(null);
function chooseImage() {
    mainImgBtn.value.click();
}

function clearImage() {
    imageURL.value = "";
    imageFile.value = "";
    ImgForm.value.reset();
}
// image###########################################


// submiting ##########################################
async function saveCandidate() {


    voter.isSaving = true

    let formData = new FormData();

    try {
        var { data } = await server.saveCandidate(formData)
        if (data == 1) {
            //ImgForm.value.reset();
            voter.details = null;
            voter.isSaving = false
            evtStore.getCandidates()
        }
        else {
            Swal.fire({
                text: 'Duplicate data',
                // text: `Already added`,
                icon: 'warning',
                showConfirmButton: false,
                timer: 1100,
            })
            voter.details = null;
            voter.isSaving = false
            return
        }

    } catch (error) {
        voter.isSaving = false
        console.log(error);

    }
}
// submiting ##########################################




async function removeCandidate(id: any) {
    try {
        await server.removeCandidate(id)
        evtStore.getCandidates()
    } catch (error) {
        console.log(error);

    }
}

</script>

<style scoped>
.card-head {
    padding: 20px 15px;
    background-color: #fff;
    border-bottom: 1px solid #eee;
}

.empty-list-text {
    color: #c7c4c4;
    font-size: 20px;
}

.candidate-desc {
    color: #c7c4c4;
    font-size: 12px;
}

.candidate-image-span {
    width: 20px;
    height: 20px;
}

.imagePreviewWrapper {
    background-repeat: no-repeat;
    width: 18px;
    height: 18px;
    /* display: block; */
    cursor: pointer;
    /* margin: 0 auto 20px; */
    background-size: contain;
    background-position: center center;
    border-radius: 100%;
}

.imagePicker {
    /* padding: 8px 10px; */
    border: 1px solid #eee;
    background-color: #fcfafa7a;
    cursor: pointer;
    /* margin-top: 70px; */
    border-radius: 5px;
    font-size: 16px;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 5px;
    border: 1px solid #ccc;
}

.imagePicker:hover {
    background-color: #f1eeee;
}


.remove-btn {
    float: right;
    margin-right: 15px;
    cursor: pointer;
    padding-inline: 5px;
    border-radius: 5px;
}

.remove-btn:hover {
    background-color: rgb(250, 212, 212);
}
</style>