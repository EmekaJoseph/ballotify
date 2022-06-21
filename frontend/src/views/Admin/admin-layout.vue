<template>
    <div class="">
        <sideBarComponent />
        <div class="adminMain">
            <div class="admin-content">
                <router-view></router-view>
            </div>
        </div>
        <offcanvas />
    </div>
</template>
<script setup lang="ts">
import { inject, onMounted } from 'vue'
import offcanvas from './adminComponents/offCanvasComponent.vue'
import sideBarComponent from './adminComponents/sideBarComponent.vue'

import { useAdminStore } from '@/store/user/admin'
import { dataStore } from '@/store/dataStore';
const orgId = useAdminStore().getData.org_id
const mStore = dataStore()

onMounted(() => {
    loadData()
})

function loadData() {
    mStore.getMembers(orgId)
    mStore.getGroupNames(orgId)
}

const { ccBg }: any = inject("c$")

</script>

<style scoped>
.adminMain {
    margin-left: 250px;
    padding: 20px;
    background-color: v-bind(ccBg);
    min-height: 100vh
}

.admin-content {
    margin: 75px 10px;
}


@media screen and (max-width: 767px) {
    .adminMain {
        margin-left: 0px;
        padding: 10px;
    }
}
</style>