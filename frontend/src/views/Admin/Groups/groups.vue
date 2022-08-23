<template>
    <div>
        <div class="card" style="min-height: 100vh">
            <div class="card-header">Groups:<span class="badge rounded-pill bg-light text-dark">{{ groups.length
            }}</span>
            </div>
            <div class="card-body p-lg-4">
                <div class="row g-3">
                    <div class="col-4 col-md-4 col-lg-4 col-xl-2" data-bs-toggle="modal"
                        data-bs-target="#newGroupModal">
                        <div class="card groupCard groupCardAdd">
                            <div class="group-content">
                                <i class="bi bi-plus-square-dotted bi-lg text-white"></i>
                                <div class="text-center group-name text-white">&nbsp;</div>
                                <small class="info-text text-white">new group.</small>
                            </div>
                        </div>
                    </div>
                    <div v-for="(g, i) in  groups" :key="g.id" class="col-4 col-md-4 col-lg-4 col-xl-2">

                        <!-- desktop display -->
                        <div class="d-none d-md-block card groupCard">
                            <i v-if="numInGrp(g.id) > 0" class="bi bi-folder-fill bi-lg"></i>
                            <i v-else class="bi bi-folder2-open bi-lg"></i>
                            <div class="group-name text-center">{{ g.name }}</div>
                            <small class="info-text text-muted">
                                {{ numInGrp(g.id) }} {{ spell('member', numInGrp(g.id)) }}
                            </small>
                            <div class="optionsBg">
                                <div class="row justify-content-center mt-5">
                                    <div class="col-md-6 text-center">
                                        <button @click="showThisGroup(g.id)" class="btn btn-sm customBtn optionsBtn">
                                            <i class="bi bi-pencil text-white"></i>
                                        </button>
                                    </div>
                                    <div v-if="numInGrp(g.id) == 0" class="col-md-6 text-center">
                                        <button @click="deleteGroup(g.id)" class="btn btn-sm btn-danger optionsBtn">
                                            <i class="bi bi-trash3 text-white"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- mobile display -->
                        <div class="d-md-none card groupCard" @click="showThisGroup(g.id)">
                            <i v-if="numInGrp(g.id) > 0" class="bi bi-folder-fill bi-lg"></i>
                            <i v-else class="bi bi-folder2-open bi-lg"></i>
                            <div class="group-name text-center">{{ g.name }}</div>
                            <small class="info-text text-muted">
                                {{ numInGrp(g.id) }} {{ spell('member', numInGrp(g.id)) }}
                            </small>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <newGroupModal @added="re_getGroupNames" />
    </div>
</template>

<script setup lang="ts">
import { inject, onMounted } from 'vue'
import newGroupModal from './newGroupModalComponent.vue'
import router from '@/router';
import { dataStore } from '@/store/admin/dataStore';
import { storeToRefs } from 'pinia'
import useFunc from '@/store/useFunction'
import Swal from 'sweetalert2'
import server from '@/store/apiStore'

const spell = useFunc.fx.spell
const { cc1, ccBtnH }: any = inject("c$");

const mStore = dataStore()
const { members, groups }: any = storeToRefs(mStore)

const numInGrp = (group_id: string) => {
    let mems = members.value.filter((x: { group_id: string; }) => x.group_id == group_id)
    return mems.length
}

function re_getGroupNames() {
    mStore.getGroupNames()
}

function showThisGroup(id: string) {
    router.push({
        path: 'groupdetails',
        query: { g: id }
    })
}

function deleteGroup(id: any) {
    if (numInGrp(id) > 0) {
        Swal.fire({
            toast: true,
            icon: 'error',
            title: 'Group is not empty',
            position: 'top-end',
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: false,
        })
        return false
    }
    else {

        Swal.fire({
            title: 'Confirm Delete?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'cancel',
            confirmButtonColor: '#d33',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                continueDelete(id)

            }
        })


    }
}

async function continueDelete(id: any) {
    let group = groups.value.find((x: { id: string; }) => x.id == id)
    try {
        var { data } = await server.deleteGroup(id);
        if (data == 1) {
            Swal.fire({
                toast: true,
                icon: 'success',
                title: `'${group.name}' deleted`,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: false,
            })
            await mStore.getGroupNames()
        }
        else console.log(data);

    } catch (error) {
        console.log(error);
    }
}

</script>

<style scoped>
.groupCard {
    border: 1px solid #eee;
    padding: 30px 10px;
    cursor: pointer;
    border-radius: 20px;
}

.bi-lg {
    font-size: 25px;
    display: flex;
    justify-content: center;
    margin-bottom: 2px;
}

.groupCard:hover :is(.bi, .group-name) {
    color: v-bind(cc1);
}

.groupCard:hover {
    /* background-color: #fdfbfb9c; */
    border: 1px solid v-bind(cc1);
    transform: scale(1.03);
}

.group-name {
    font-size: 14px;
    text-transform: capitalize;
}

.info-text {
    display: flex;
    justify-content: center;
    font-size: 11px;
}

.optionsBg {
    position: absolute;
    height: 100%;
    width: 100%;
    top: 0;
    left: 0;
    background-color: rgba(243, 240, 240, 0.555);
    /* border: 1px solid #eee; */
    padding: 30px 10px;
    border-radius: 20px;
    display: none;
}

.groupCard:hover .optionsBg {
    display: block;
}

.optionsBtn {
    font-size: 11px;
    width: 100%;
    padding: 0px 2px;
}



.groupCardAdd {
    border: none;
    background-color: v-bind(cc1);
}

.groupCardAdd:hover {
    background-color: v-bind(ccBtnH);
}

@media (max-width: 550px) {
    .group-name {
        font-size: 9px;
    }

    .bi-lg {
        font-size: 15px;

    }

    .info-text {
        font-size: 7px;
    }

    .groupCard {
        padding: 14px 5px
    }
}
</style>