<template>
    <div class="card px-2">
        <div class="card-head"><i class="bi bi-person-check-fill"></i>Candidates:
            <span class="badge rounded-pill bg-secondary text-white fw-light">
                {{ candidate.list.length }}
            </span>
        </div>
        <div class="card-body">
            <div class="row justify-content-center gy-3">
                <div class="col-12 col-xl-7">
                    <v-select v-model="candidate.name" placeholder="select candidate.." :clearable="false"
                        class="vSelect" :options="data.members" />
                </div>
                <div class="col-12 col-xl-5">
                    <v-select v-model="candidate.post" placeholder="select position.." class="vSelect"
                        :options="data.posts" :disabled="!(data.posts.length)" />
                </div>
                <div class="col-12 col-xl-6">
                    <input v-model="candidate.info" placeholder="info (optional).." type="text" class="form-control">
                </div>
                <input class="form-control" accept="image/jpg, image/png" ref="mainImgBtn" id="fileUp" type="file"
                    @change="handleFileUpload" hidden />
                <div class="col-xl-3 ">

                    <span v-if="!imageURL" @click.prevent="chooseImage" class="imagePicker">
                        optional image
                    </span>
                    <span v-else class="imagePicker">
                        <span class="imagePreviewWrapper" :style="{ 'background-image': `url(${imageURL})` }"></span>
                        &nbsp;<button @click.prevent="chooseImage" class="btn btn-link text-info m-0 p-0 btn-sm"
                            style="font-size: 16px">Change</button>
                    </span>
                </div>
                <div class="col-12 col-xl-3">
                    <button class="w-100 btn customBtn">Add <i
                            class="bi text-white bi-arrow-down-circle fs-6"></i></button>
                </div>
                <div class="col-md-12 col-lg-12 mt-4">
                    <div class="list-span">
                        <!-- <div class="text-center empty-list-text mt-5 pt-5">
                            List is Empty
                        </div> -->
                        <div class="table-responsive">
                            <table class="table table-sm text-nowrap text-capitalize">
                                <tbody>
                                    <tr>
                                        <th>1</th>
                                        <td>
                                            <span class="candidate-image-span">



                                            </span>
                                            <!-- <figure class="figure">
                                                <img src="@/assets/images/about.jpg"
                                                    class="figure-img img-fluid rounded"
                                                    alt="A generic square placeholder image with rounded corners in a figure.">
                                            </figure> -->
                                        </td>
                                        <td>
                                            <div>Emmanuel Josiah</div>
                                            <div class="candidate-desc">ss</div>
                                        </td>

                                        <td>
                                            <button class="btn btn-sm btn-link text-danger">Remove</button>
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
import { reactive, computed, watchEffect, ref } from 'vue';
import { dataStore } from '@/store/admin/dataStore';
import { useImageUpload } from "@/store/useImageUpload";
import { eventStore } from '../eventStore';
import { storeToRefs } from 'pinia'


const mStore = dataStore()
const { members }: any = storeToRefs(mStore)
const event_ = eventStore()
const { positions }: any = storeToRefs(event_)

interface Cand {
    name: any,
    post: any,
    info: string,
    photo: any,
    list: any[]
}

const candidate: Cand = reactive({
    name: null,
    post: null,
    info: '',
    photo: null,
    list: []
})




const data: any = reactive({
    members: computed(() => {
        let data = members.value.map((x: any) => ({ id: x.id, label: x.firstname + ' ' + x.lastname + ', (' + x.gender + ')' }))
        return data
    }),
    posts: computed(() => {
        let data = positions.value.map((x: any) => ({ id: x.id, label: x.name }))
        return data
    })
})

watchEffect(() => {
    if (candidate.post !== null) {
        if (positions.value.find((x: { name: any; }) => x.name == candidate.post.label) == undefined)
            candidate.post = null
    }
})


let { handleFileUpload, imageURL, imageFile, imgSize } = useImageUpload();
const mainImgBtn = ref<any>(null);
const ImgForm = ref(null);
function chooseImage() {
    mainImgBtn.value.click();
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
    background-color: #f3f0f0;
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
    background-color: #e9e7e7;
}
</style>