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
<script setup>
import { inject } from 'vue'
import offcanvas from './adminComponents/offCanvasComponent.vue'
import sideBarComponent from './adminComponents/sideBarComponent.vue'
const ccBg = inject("c$").ccBg.value;
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