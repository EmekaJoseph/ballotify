<template>
    <div>
        <div class="card" style="min-height: 100vh">
            <div class="card-header">Groups:<span class="badge rounded-pill bg-light text-dark">{{ groups.length
            }}</span>
            </div>
            <div class="card-body p-lg-4">
                <div class="row g-3">
                    <div class="col-4 col-md-4 col-lg-2" data-bs-toggle="modal" data-bs-target="#newGroupModal">
                        <div class="card groupCard groupCardAdd">
                            <div class="group-content">
                                <i class="bi bi-plus-square-dotted bi-lg text-white"></i>
                                <div class="text-center group-name text-white">New</div>
                                <small class="info-text text-white">add a new group.</small>
                            </div>
                        </div>
                    </div>
                    <div v-for="(g, i) in  groups" :key="g.id" class="col-4 col-md-4 col-lg-2">
                        <div class="card groupCard" @click="showThisGroup(g.id)">
                            <div class="group-content">
                                <i v-if="numInGrp(g.id) > 0" class="bi bi-folder-fill bi-lg"></i>
                                <i v-else class="bi bi-folder2-open bi-lg"></i>
                                <div class="group-name text-center">{{ g.name }}</div>
                                <small class="info-text text-muted">
                                    {{ numInGrp(g.id) }} {{ spell('member', numInGrp(g.id)) }}
                                </small>
                                <small class="info-text2">
                                    click to view
                                </small>
                            </div>
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

const spell = useFunc.fx.spell
const { cc1, ccBtnH }: any = inject("c$");

const mStore = dataStore()
const { members, groups }: any = storeToRefs(mStore)

// onMounted(async () => {
//     await mStore.getMembers(orgId)
//     await mStore.getGroupNames(orgId)
// })

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

</script>

<style scoped>
.groupCard {
    border: 1px solid #eee;
    padding: 30px 0px;
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
    background-color: #fdfbfb9c;
    border: 1px solid v-bind(cc1);
    transform: scale(1.03);
}

.group-name {
    font-size: 18px;
    text-transform: capitalize;
}

.info-text {
    display: flex;
    justify-content: center;
    font-size: 11px;
}

.info-text2 {
    display: none;
}

.groupCard:hover .info-text2 {
    display: flex;
    justify-content: center;
    font-size: 11px;
    position: absolute;
    top: 60;
    margin-left: 45px;
    color: v-bind(cc1);
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
        padding: 14px 0px
    }
}
</style>