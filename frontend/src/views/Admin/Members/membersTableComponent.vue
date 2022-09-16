<template>
    <div class="col-md-12">
        <div class="card" style="min-height: 85vh">
            <div class="card-header">Members: <span class="badge rounded-pill bg-light text-dark">
                    {{  list.length  }} </span>
                <span v-if="list.length" class="float-end">
                    <button ref="btnOpenModal" type="button" data-bs-toggle="modal" data-bs-target="#newMemberModal"
                        class="btn float-end btn-sm customBtn p-1 px-4 m-0">
                        <i class="bi bi-person-plus-fill"></i>
                    </button>
                </span>
                <transition name="xSlide">
                    <span v-if="aMember.isChecked.length > 0" class="float-end">
                        <button @click="sendSetToDelete" class="btn btn-danger me-2 float-end btn-sm p-1 px-2 m-0">
                            <i class="bi bi-trash3"></i> Delete
                            <span class="badge rounded-pill bg-white text-dark small">
                                {{  aMember.isChecked.length  }}
                            </span>
                        </button>
                    </span>
                </transition>
                <div v-if="list.length" class="d-none d-sm-block m-0 me-4 float-end">
                    <span v-if="isSearching" class="inputSearcher">
                        <input ref="mainSearch" v-model="searchField" type="text"
                            class="form-control form-control-sm mainSearchBox" placeholder="search..">
                        <span @click.prevent="closeSearch" class="icon">
                            <i class="bi bi-x-lg"></i>
                        </span>

                    </span>
                    <input v-else @click.prevent="openSearch" type="text" class="form-control form-control-sm"
                        placeholder="search..">

                </div>
            </div>
            <div class="card-body p-lg-4">
                <div class="row g-3">
                    <span class="alert small m-0 p-1">Add & Modify members in your
                        orginisation.</span>
                    <div v-if="!list.length" class="col-12 list-span">
                        <div class="row justify-content-center my-4">
                            <span class="text-center"><i class="bi bi-person-x plus"></i></span>
                            <div class="text-center text-muted">You do not have any members yet.</div> <br>
                            <div class="text-center">
                                <button ref="btnOpenModal" type="button" data-bs-toggle="modal"
                                    data-bs-target="#newMemberModal" class="btn btn-link">
                                    <i class="bi bi-plus-circle-dotted"></i> add new
                                    members</button>
                            </div>
                        </div>

                    </div>
                    <div v-else class="col-12 table-span">
                        <div class="table-responsive">
                            <table class="table table-sm text-nowrap">
                                <thead>
                                    <tr>
                                        <th class="smallCol">S/N</th>
                                        <th>Name &nbsp;
                                            <span @click="sortByName()">
                                                <span v-if="nameSortedFrom == 'z'" class="sort-btn">
                                                    <i class="bi bi-sort-alpha-down"></i>
                                                </span>
                                                <span v-else class="sort-btn">
                                                    <i class="bi bi-sort-alpha-up-alt"></i>
                                                </span>
                                            </span>
                                        </th>
                                        <th>Phone No.</th>
                                        <th>Group &nbsp;
                                            <span @click="sortByGroup()">
                                                <span v-if="groupSortedFrom == '0'" class="sort-btn">
                                                    <i class="bi bi-funnel"></i>
                                                </span>
                                                <span v-else class="sort-btn">
                                                    <i class="bi bi-funnel-fill"></i>
                                                </span>
                                            </span>
                                        </th>
                                        <th>Birthday</th>
                                        <th>Satus</th>
                                        <th></th>
                                        <th class="smallCol"><input @change="toggleAll" v-model="mCheck"
                                                class="form-check-input" type="checkbox"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(i, index) in membersToShow">
                                        <th>{{  (currChunck > 0) ? (currChunck * 10) + (index + 1) : (index + 1)  }}</th>
                                        <td class="text-capitalize">{{  i.lastname  }} {{  i.firstname  }}, ({{  i.gender 
                                            }})
                                        </td>
                                        <td>{{  i.phone  }}</td>
                                        <td>
                                            <span v-if="i.group_id != '0'" data-bs-toggle="tooltip"
                                                data-bs-placement="top" :title="whatGroupName(i.group_id)">
                                                <i class="bi bi-folder-check"></i>
                                                {{  fx.shortenText(whatGroupName(i.group_id), 6)  }}
                                            </span>
                                            <span v-else style="color: #ccc">
                                                <i class="bi bi-folder-x"></i>
                                                {{  whatGroupName(i.group_id)  }}
                                            </span>


                                            <!-- <i v-if="i.group_id != '0'" class="bi bi-folder"></i> -->
                                        </td>
                                        <td>{{  fx.displayBD(i.birthday)  }}</td>
                                        <td v-if="i.verified == 1"><span class="badge bg-successs"><i
                                                    class="bi bi-check-circle"></i> verified</span>
                                        </td>
                                        <td v-else><span class="badge bg-warningg fst-italic"><i
                                                    class="bi bi-exclamation-circle"></i> pending</span></td>
                                        <td><button @click="openModalEdit(i.id)" class="btn btn-link btn-sm actnbtn"><i
                                                    class="bi bi-pencil"></i>
                                                _</button>
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
                    <div v-if="list.length" class="col-12 mt-4">
                        <div class="small">Showing page <b>{{  currChunck + 1  }}/{{  paginateSize  }}</b></div>
                        <div class="float-end">
                            <nav aria-label="Page navigation example">
                                <ul class="pagination">
                                    <li class="page-item">
                                        <a @click.prevent="move('p')" class="page-link"
                                            :class="{ 'disabled': isprev() }" href="" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <li v-for="(num, index) in paginateSize" :key="index" class="page-item"
                                        :class="{ 'active': (currChunck == index ? true : false) }">
                                        <a @click.prevent="currChunck = index" class="page-link" href="">{{  num 
                                            }}</a>
                                    </li>


                                    <li class="page-item">
                                        <a @click.prevent="move('n')" class="page-link"
                                            :class="{ 'disabled': isnext() }" href="" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
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
import { ref, reactive, computed, onMounted, watchEffect } from 'vue'
import useFunc from '@/store/useFunction'
import { dataStore } from '@/store/admin/dataStore';
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'
import editMemberModal from './editMemberModalComponent.vue'
import newMemberModal from './newMemberModalComponent.vue'

const emit = defineEmits(["delete", "update"]);
const fx = useFunc.fx

const mStore = dataStore()
const { members: list, groups }: any = storeToRefs(mStore)



//  showing data start ###############################################

const membersToShow = computed(() => {

    if (list.value.length > 0) {
        const filtered = searchField.value === ""
            ? divideByChunck()
            : list.value.filter(wo => Object.values(wo).join("").toLocaleLowerCase().indexOf(searchField.value.toLocaleLowerCase()) !== -1);
        // : list.value.filter(w => (w.lastname.toLocaleLowerCase()).startsWith(searchField.value.toLocaleLowerCase()) ||
        //     (w.firstname.toLocaleLowerCase()).startsWith(searchField.value.toLocaleLowerCase()));
        return filtered;

    }
    else {
        return list.value
    }



})

const paginateSize = ref(0)
const currChunck = ref(0)
const divideByChunck = () => {
    const chunkSize = 10;
    const array: any[] = []
    for (let i = 0; i < list.value.length; i += chunkSize) {
        const chunk: any = list.value.slice(i, i + chunkSize);
        array.push(chunk)
    }
    paginateSize.value = array.length
    return array[currChunck.value]
}


const isprev = () => {
    return (currChunck.value == 0) ? true : false
}
const isnext = () => {
    return ((currChunck.value + 1) == paginateSize.value) ? true : false
}


function move(d) {
    let direction = (d == 'p') ? -1 : 1
    currChunck.value += direction
}
//  showing data end ###############################################



//    sorting start ###############################
const nameSortedFrom = ref('z')
function sortByName() {
    nameSortedFrom.value = nameSortedFrom.value == 'a' ? 'z' : 'a'
    list.value.sort((a, b) => {
        const nameA = a.lastname.toLowerCase();
        const nameB = b.lastname.toLowerCase();

        let comparison = 0;
        if (nameA > nameB) {
            comparison = (nameSortedFrom.value == 'a') ? 1 : -1;
        } else if (nameA < nameB) {
            comparison = (nameSortedFrom.value == 'a') ? -1 : 1;
        }
        return comparison;
    })
}

const groupSortedFrom = ref('1')
function sortByGroup() {
    groupSortedFrom.value = groupSortedFrom.value == '1' ? '0' : '1'
    list.value.sort((a, b) => {
        const groupA = a.group_id.toLowerCase();
        const groupB = b.group_id.toLowerCase();

        let comparison = 0;
        if (groupA > groupB) {
            comparison = (groupSortedFrom.value == '1') ? 1 : -1;
        } else if (groupA < groupB) {
            comparison = (groupSortedFrom.value == '1') ? -1 : 1;
        }
        return comparison;
    })
}
//    sorting end ###############################


// serach start ################################################
const mainSearch: any = ref(null)
const isSearching = ref(false)
const searchField = ref('')
function openSearch() {
    isSearching.value = true
    setTimeout(() => {
        mainSearch.value.focus()
    }, 500);

}
function closeSearch() {
    isSearching.value = false
    searchField.value = ''
}

// search end ##########################################








const mCheck = ref(false)
function toggleAll() {
    membersToShow.value.forEach((chk: any) => {
        chk.checked = mCheck.value
    });
}

watchEffect(() => {
    if (membersToShow.value.some(x => x.checked == false)) {
        mCheck.value = false
    }
})


function sendSetToDelete() {
    let newData = list.value.filter((x: any) => x.checked == true)
    let ids = newData.map(x => x.id)
    conFirmDelete(ids.toString())
}

const whatGroupName = (id: string) => {
    let grpObj = groups.value.find((x: { id: any; }) => x.id == id)
    return grpObj == undefined ? 'None' : grpObj.name
}

const aMember = reactive({
    isChecked: computed(() => { return list.value.filter(x => x.checked == true) })
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
    background-color: #cae9da65;
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

.sort-btn {
    color: rgb(204, 113, 9);
    font-size: 20px;
    cursor: pointer;
}

.sort-btn:hover {
    color: rgb(6, 116, 106);
}

.inputSearcher {
    position: relative;
}


.inputSearcher .icon {
    position: absolute;
    margin-top: 6px;
    margin-right: 50px;
    margin-left: 270px;
    top: 0;
    font-size: 14px;
    cursor: pointer;
}

.table-span {
    background-color: #eeeeee2f;
    padding: 30px 10px;
    border: 1px solid #eee;
    height: 80vh;
    overflow-y: auto;
    /* font-size: 14px; */
}

a.disabled {
    pointer-events: none;
    cursor: default;
    color: #ccc;
}


.pagination .active a {
    background-color: #03787c;
    border: #03787c 1px solid;
    cursor: default;
    pointer-events: none;
}

.mainSearchBox {
    width: 300px;
}
</style>