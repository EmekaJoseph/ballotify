<template>
    <div class="card px-2">
        <div class="card-head"><i class="bi bi-person-check-fill"></i> Eligible Voters:
            <span class="badge rounded-pill bg-secondary text-white fw-light">
                {{ list.length }}
            </span>
        </div>
        <div class="card-body">
            <div class="row justify-content-cente gy-3">
                <div class="col-12 col-xl-6">
                    <v-select v-model="voter.details" :multiple="true" :closeOnSelect="false"
                        placeholder="select voter.." :clearable="false" class="vSelect voter-select"
                        :options="data.members" />
                </div>
                <!-- <div class="col-12 col-xl-4">
                    <button @click.prevent="" class="w-100 btn btn-outline-secondary">Add by groups <i
                            class="bi  bi-folder fs-6"></i></button>
                </div> -->
                <div class="col-12 col-xl-2">
                    <transition name="xSlide">
                        <button :disabled="voter.isSaving" v-if="voter.details != null" @click.prevent="saveVoter"
                            class="w-100 btn customBtn">Add <i class="bi text-white bi-arrow-down-circle fs-6"></i>
                        </button>
                    </transition>
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
                                        <td>
                                            <span @click.prevent="removeAll" v-if="list.length > 1">
                                                <span v-if="!isRemovingAll" class="text-danger remove-btn">
                                                    <i class="bi bi-trash2"></i> Remove all
                                                </span>
                                                <span v-else class="text-muted remove-btn">Removing...</span>
                                            </span>
                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(voter, index) in list">
                                        <th>{{ index + 1 }}</th>
                                        <td>
                                            {{voter.lastname}} {{voter.firstname}}, ({{voter.gender}})
                                        </td>
                                        <td>
                                            <button @click="removeVoter(voter.id)"
                                                class="btn btn-sm btn-link text-danger remove-btn">
                                                <i class="bi bi-trash2"></i>
                                            </button>
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
import { reactive, computed, ref, watch } from 'vue';
import server from '@/store/apiStore'
import { dataStore } from '@/store/admin/dataStore';
import { eventStore } from '../eventStore';
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'

const mStore = dataStore()
const { members: membrsList }: any = storeToRefs(mStore)

const evtStore = eventStore()
const { voters: list, event }: any = storeToRefs(evtStore)

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

const alreadyAddedVoters = ref<any[]>([])
const data: any = reactive({
    members: computed(() => {
        let alreadyAddedLst: any[] = []
        if (list.value.length > 0) {
            alreadyAddedLst = list.value.map((x: { member_id: any; }) => x.member_id)
        }
        alreadyAddedLst.forEach(x =>
            alreadyAddedVoters.value.push(x)
        )



        //remove alreadyAddedVoters from dropdown
        let filteredDropdown = membrsList.value.filter((person: { id: any; }) => {
            return !alreadyAddedVoters.value.includes(person.id);
        })

        let data1 = filteredDropdown.map((x: any) => ({ id: x.id, label: x.firstname + ' ' + x.lastname + ', (' + x.gender + ')' }))
        return data1
    }),

})




watch(() => voter.details, () => {
    if (voter.details !== null) {
        alreadyAddedVoters.value = []
        let alreadyAddedLst = voter.details.map((x: { id: any; }) => x.id)
        let alreadyAddedLst2 = list.value.map((x: { member_id: any; }) => x.member_id)

        alreadyAddedLst.forEach(x => {
            alreadyAddedVoters.value.push(x)
        })
        alreadyAddedLst2.forEach(x => {
            alreadyAddedVoters.value.push(x)
        })
    }

})









// submiting ##########################################
async function saveVoter() {
    let ids = voter.details.map(x => x.id)
    voter.isSaving = true
    let formData = new FormData();
    formData.append("member_ids", ids);
    formData.append("event_id", event.value.event_id);

    try {
        var { data } = await server.saveVoter(formData)
        if (data == 1) {
            voter.details = null;
            voter.isSaving = false
            evtStore.getVoters()
        }
    } catch (error) {
        Swal.fire({
            text: 'Internet Error',
            icon: 'error',
            showConfirmButton: false,
            timer: 1100,
        })
        voter.isSaving = false
        console.log(error);

    }
}
// submiting ##########################################
async function removeVoter(id: any) {
    try {
        await server.removeVoter(id)
        await evtStore.getVoters()
        alreadyAddedVoters.value = []
    } catch (error) {
        Swal.fire({
            text: 'Internet Error',
            icon: 'error',
            showConfirmButton: false,
            timer: 1100,
        })
        console.log(error);

    }
}


function removeAll() {
    Swal.fire({
        title: 'Remove entire list?',
        text: "",
        showCancelButton: true,
        confirmButtonText: 'Yes, Remove!',
        cancelButtonText: 'cancel',
        confirmButtonColor: '#A93226',
        cancelButtonColor: '#641E16',
        background: `#641E16`,
        reverseButtons: true,
        color: '#fff',
        width: 'auto'

    }).then((result) => {
        if (result.isConfirmed) {
            removeAllCont()

        }
    })
}


const isRemovingAll = ref(false)
function removeAllCont() {
    isRemovingAll.value = true
    list.value.forEach(async (el: { id: any; }) => {
        await removeVoter(el.id)
    });
    isRemovingAll.value = false
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