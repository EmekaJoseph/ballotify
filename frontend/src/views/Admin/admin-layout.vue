<template>
    <div class="">
        <sideBarComponent />
        <div class="adminMain">
            <div class="admin-content">
                <div v-if="internetError" class="alert alert-danger py-2 border-0 small" role="alert">
                    <i class="bi bi-wifi-off"></i> <b>Connection lost, </b> please check your internet and refresh.
                </div>
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
import { dataStore } from '@/store/admin/dataStore';
import { storeToRefs } from 'pinia'


const mStore = dataStore()
const { internetError } = storeToRefs(mStore)

onMounted(() => {
    mStore.loadMaster()
})


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