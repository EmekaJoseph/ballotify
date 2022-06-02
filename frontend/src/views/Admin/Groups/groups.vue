<template>
    <div>
        <div class="card" style="min-height: 100vh">
            <div class="card-header">Groups:<span class="badge rounded-pill bg-light text-dark">{{ groupList.length
            }}</span>
            </div>
            <div class="card-body p-lg-4">
                <div class="row g-3">
                    <div class="col-4 col-md-4 col-lg-2" @click="ifAddModal = true">
                        <div class="card groupCard groupCardAdd">
                            <div class="group-content">
                                <i class="bi bi-folder-plus bi-lg text-white"></i>
                                <div class="text-center group-name text-white">Add Group</div>
                                <small class="info-text text-white">add a new group.</small>
                            </div>
                        </div>
                    </div>
                    <div v-for="(g, i) in  groupList" :key="g.id" class="col-4 col-md-4 col-lg-2">
                        <div class="card groupCard" @click="showThisGroup(g.id)">
                            <div class="group-content">
                                <i class="bi bi-folder bi-lg"></i>
                                <div class="group-name text-center">{{ g.group_name }}</div>
                                <small class="info-text text-muted">
                                    {{ getGroupsNum(g.id) }} {{ spell('member', getGroupsNum(g.id)) }}
                                </small>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <transition name="ySlide" mode="out-in">
            <newGroupModal v-if="ifAddModal" @closeModal="ifAddModal = false" @added="re_getGroupNames" />
        </transition>
    </div>
</template>

<script setup lang="ts">
import { computed, inject, ref, onMounted } from 'vue'
import newGroupModal from './newGroupModalComponent.vue'
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
import router from '@/router';
import useFunc from '@/store/useFunction'
const spell = useFunc.fx.spell
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const orgId = useAdminStore().getData.org_id
const ifAddModal = ref(false)
const groupList = ref<any>([])
const groupsCount = ref<any>(null)

const getGroupsNum = (group_id: string) => {
    if (groupsCount.value == null) {
        return 0;
    }
    else {
        let num = groupsCount.value[group_id];
        return num == undefined ? 0 : num;
    }

}

onMounted(() => {
    getGroupNames()
})

async function getGroupNames() {
    try {
        var { data } = await server.getGroupNames(orgId)
        groupList.value = data.groups
        if (data.counted != null) {
            groupsCount.value = data.counted
        }
    } catch (error) {
        console.log(error);
    }
}
function re_getGroupNames() {
    getGroupNames()
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
    font-size: 40px;
    display: flex;
    justify-content: center;
    margin-bottom: 2px;
}

.groupCard:hover :is(.bi, .group-name) {
    color: v-bind(cc1);
}

.groupCard:hover {
    background-color: #fdfbfb71;
    transform: scale(1.03);
}

.group-name {
    font-size: 16px;
}

.info-text {
    display: flex;
    justify-content: center;
    font-size: 11px;
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
        font-size: 11px;
    }

    .bi-lg {
        font-size: 20px;

    }

    .info-text {
        font-size: 8px;
    }

    .groupCard {
        padding: 10px 0px
    }
}
</style>