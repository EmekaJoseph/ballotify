<template>
    <div class="col-md-12">
        <div class="card p-lg-3" style="min-height: 85vh">
            <div class="card-header">List <span class="badge rounded-pill bg-light text-dark">{{ members.length
            }}</span>
                <span v-if="members.length" class="float-end">
                    <button ref="btnOpenModal" type="button" data-bs-toggle="modal" data-bs-target="#newMemberModal"
                        class="btn float-end btn-sm customBtn p-1 px-2 m-0">
                        <i class="bi bi-plus-circle"></i> add members
                    </button>
                </span>
                <transition name="xSlide">
                    <span v-if="aMember.isChecked.length > 0" class="float-end">
                        <button @click="sendSetToDelete" class="btn btn-danger me-2 float-end btn-sm p-1 px-2 m-0">
                            <i class="bi bi-trash3"></i> Delete
                            <span class="badge rounded-pill bg-white text-dark small">
                                {{ aMember.isChecked.length }}
                            </span>
                        </button>
                    </span>
                </transition>
                <div v-if="members.length" class="me-4 float-end">
                    <!-- <input type="text" class="form-control form-control-sm" placeholder="search.."> -->
                    <button class="btn btn-link m-0 p-0"><i class="bi bi-search"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 list-span">
                        <div v-if="!members.length" class="row justify-content-center my-4">
                            <span class="text-center"><i class="bi bi-person-x plus"></i></span>
                            <div class="text-center text-muted">You do not have any members yet.</div> <br>
                            <div class="text-center">
                                <button ref="btnOpenModal" type="button" data-bs-toggle="modal"
                                    data-bs-target="#newMemberModal" class="btn btn-link">
                                    <i class="bi bi-plus-circle-dotted"></i> add new
                                    members</button>
                            </div>
                        </div>
                        <div v-else>
                            <div class="table-responsive">
                                <table class="table table-sm text-nowrap">
                                    <thead>
                                        <tr>
                                            <th class="smallCol">S/N</th>
                                            <th>Name</th>
                                            <th>Phone No.</th>
                                            <th>Group</th>
                                            <th>Birthday</th>
                                            <th>Satus</th>
                                            <th>modify</th>
                                            <th class="smallCol"><input @change="toggleAll" v-model="mCheck"
                                                    class="form-check-input" type="checkbox"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(i, index) in members">
                                            <th>{{ index + 1 }}</th>
                                            <td class="text-capitalize">{{ i.firstname }} {{ i.lastname }}, ({{ i.gender
                                            }})
                                            </td>
                                            <td>{{ i.phone }}</td>
                                            <td>
                                                <span v-if="i.group_id != '0'">
                                                    <i class="bi bi-folder-check"></i>
                                                    {{ fx.shortenText(whatGroupName(i.group_id), 6) }}
                                                </span>
                                                <span v-else style="color: #ccc">
                                                    <i class="bi bi-folder-x"></i>
                                                    {{ whatGroupName(i.group_id) }}
                                                </span>


                                                <!-- <i v-if="i.group_id != '0'" class="bi bi-folder"></i> -->
                                            </td>
                                            <td>{{ fx.displayBD(i.birthday) }}</td>
                                            <td v-if="i.verified == 1"><span class="badge bg-successs">verified</span>
                                            </td>
                                            <td v-else><span class="badge bg-warningg fst-italic">pending</span></td>
                                            <td><button @click="openModalEdit(i.id)"
                                                    class="btn btn-link btn-sm actnbtn"><i
                                                        class="bi bi-pencil"></i>__</button>
                                            </td>
                                            <td><input v-model="i.checked" class="form-check-input" type="checkbox">
                                            </td>
                                            <!-- <td><button @click="conFirmDelete(i.id)"
                                                    class="btn btn-link btn-sm text-danger actnbtn"><i
                                                        class="bi bi-trash3"></i></button></td> -->

                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <newMemberModal @saved="emit('update')" @newGroup="reOpenThis" />
        <editMemberModal v-if="edit.modal" :id="edit.id" @saved="emit('update')" @close="edit.modal = false" />
    </div>
</template>
<!-- :class="edit.modal ? 'animate__animated animate__bounce' : ''" -->
<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import useFunc from '@/store/useFunction'
import { dataStore } from '@/store/admin/dataStore';
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'
import editMemberModal from './editMemberModalComponent.vue'
import newMemberModal from './newMemberModalComponent.vue'

const emit = defineEmits(["delete", "update"]);
const fx = useFunc.fx

const mStore = dataStore()
const { members, groups }: any = storeToRefs(mStore)


const mCheck = ref(false)
function toggleAll() {
    members.value.forEach((chk: any) => {
        chk.checked = mCheck.value
    });
}


function sendSetToDelete() {
    let newData = members.value.filter((x: any) => x.checked == true)
    let ids = newData.map(x => x.id)
    conFirmDelete(ids.toString())
}

const whatGroupName = (id: string) => {
    let grpObj = groups.value.find((x: { id: any; }) => x.id == id)
    return grpObj == undefined ? 'None' : grpObj.name
}

const aMember = reactive({
    isChecked: computed(() => { return members.value.filter(x => x.checked == true) })
})

function conFirmDelete(id: string) {
    Swal.fire({
        title: 'Confirm delete?',
        text: "entire record will be deleted!",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#767676',
        confirmButtonText: 'Confirm delete'
    }).then((result) => {
        if (result.isConfirmed) {
            emit('delete', id)

        }
    })
}

const edit = reactive({
    modal: false,
    id: ''
})
function openModalEdit(id: string) {
    edit.id = id
    edit.modal = true

}

const btnOpenModal: any = ref(null)
function reOpenThis() {
    btnOpenModal.value.click()
}
</script>

<style scoped>
.plus {
    color: #ece8e8;
    font-size: 10rem;
    cursor: pointer;
}

table td:nth-child(1),
th:nth-child(1) {
    text-align: center;
}

.smallCol {
    width: 50px;
}


.bg-successs {
    background-color: #cae9dab6;
    color: #198754;
    color: #24bd75;
}

.bg-warningg {
    background-color: #c9c9082f;
    color: #919129;
}

.actnbtn:hover {
    background-color: #eee;
}

.list-span {
    height: 450px !important;
}
</style>