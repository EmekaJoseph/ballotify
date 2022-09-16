<template>
    <div class="card px-2">
        <div class="card-head"><i class="bi bi-person-check-fill"></i> Candidates:
            <span class="badge rounded-pill bg-secondary text-white fw-light">
                {{  list.length  }}
            </span>
        </div>
        <div class="card-body">
            <div class="row justify-content-center gy-3">
                <div class="col-12 col-xl-7">
                    <label>Select Candidate:</label>
                    <v-select v-model="candidate.details" placeholder="select.." :clearable="false" class="vSelect"
                        :options="data.members" />
                </div>
                <div class="col-12 col-xl-5">
                    <label>Select Post:</label>
                    <v-select v-model="candidate.post"
                        :placeholder="(data.posts.length) ? 'select..' : 'add a post first'" class="vSelect"
                        :options="data.posts" :disabled="!(data.posts.length)" />
                </div>

                <!-- <div class="col-12 col-xl-7"> -->
                <!-- <form ref="ImgForm">
                        <input class="form-control" accept="image/jpeg, image/png, image/jpg" ref="mainImgBtn"
                            id="fileUp" type="file" @change="handleFileUpload" hidden />
                        <span v-if="!imageURL" @click.prevent="chooseImage" class="imagePicker">
                            select photo (optional)
                        </span>
                        <span v-else class="imagePicker">
                            <small> Photo: &nbsp;</small><span class="imagePreviewWrapper"
                                :style="{ 'background-image': `url(${imageURL})` }"></span>
                            &nbsp;&nbsp;<button @click.prevent="chooseImage"
                                class="btn btn-link text-info m-0 p-0 btn-sm" style="font-size: 16px">change</button>
                            &nbsp; &nbsp;<button @click.prevent="clearImage"
                                class="btn btn-link text-danger m-0 p-0 btn-sm" style="font-size: 16px">cancel</button>
                        </span>
                    </form> -->
                <!-- </div> -->

                <!-- <div class="col-xl-2 "> </div> -->
                <div class="col-12 col-xl-12">
                    <transition name="xSlide">
                        <button v-if="(candidate.details !== null) && (candidate.post !== null)"
                            :disabled="(candidate.isSaving)" @click="saveCandidate" class="w-100 btn customBtn">
                            Add <i class="bi text-white bi-arrow-down-circle fs-6"></i>
                        </button>
                    </transition>

                </div>
                <div class="col-md-12 col-lg-12">
                    <small v-if="candidate.inputError" class="text-danger">{{
                         candidate.inputError 
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

interface Cand {
    details: any,
    post: any,
    info?: string,
    inputError: string
    isSaving: boolean
}

const candidate: Cand = reactive({
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
    posts: computed(() =>
        (positions.value.map((x: any) => ({ id: x.id, label: x.name })))
    )
})

const m_name = (id: string) => {
    let thisMember = members.value.find((x: { id: string; }) => x.id == id)
    return thisMember == undefined ? '...' : `${thisMember.firstname} ${thisMember.lastname} (${thisMember.gender})`
}

const p_name = (id: string) => {
    let thisPosition = positions.value.find((x: { id: string; }) => x.id == id)
    return thisPosition == undefined ? '...' : `${thisPosition.name}`
}


watchEffect(() => {
    if (candidate.post !== null) {
        if (positions.value.find((x: { name: any; }) => x.name == candidate.post.label) == undefined)
            candidate.post = null
    }
})



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

    // if (imageFile.value) {
    //     // get the file name
    //     let fileName = imageFile.value.name
    //     let regex = new RegExp('[^.]+$');
    //     let ext = fileName.match(regex);
    //     // get the extension
    //     let fileExtension = ext[0].toLowerCase()
    //     const validFormats = ['png', 'jpg', 'jpeg']

    //     //make sure the file is a valid picture format
    //     if (!(validFormats.some(x => x == fileExtension.toLowerCase()))) {
    //         Swal.fire({
    //             title: 'Invalid format',
    //             text: '...image should be a picture',
    //             icon: 'warning',
    //             showConfirmButton: false,
    //             timer: 3000,
    //         })
    //         return
    //     }

    //     //picture not greater than 50k
    //     if (imgSize.kb > 50) {
    //         Swal.fire({
    //             title: 'Image too large!',
    //             text: `NB: not more than 50kb`,
    //             icon: 'warning',
    //             showConfirmButton: false,
    //             timer: 3000,
    //         })
    //         return
    //     }

    // }
    candidate.isSaving = true

    let formData = new FormData();
    //formData.append("image", imageFile.value);
    formData.append("member_id", candidate.details.id);
    formData.append("event_id", event.value.event_id);
    formData.append("position_id", candidate.post.id);

    try {
        var { data } = await server.saveCandidate(formData)
        if (data == 1) {
            //ImgForm.value.reset();
            candidate.details = null;
            candidate.post = null;
            candidate.isSaving = false
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
            candidate.details = null;
            candidate.post = null;
            candidate.isSaving = false
            return
        }

    } catch (error) {
        candidate.isSaving = false
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