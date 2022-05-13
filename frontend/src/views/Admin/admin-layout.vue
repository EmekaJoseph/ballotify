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
import offcanvas from './adminComponents/offCanvasComponent.vue'
import sideBarComponent from './adminComponents/sideBarComponent.vue'
</script>

<style scoped>
.adminMain {
    margin-left: 250px;
    padding: 20px;
    background-color: #00bd8b0e;
    min-height: 100vh
}

.admin-content {
    margin-top: 80px;
}


@media screen and (max-width: 767px) {
    .adminMain {
        margin-left: 0px;
    }
}
</style>