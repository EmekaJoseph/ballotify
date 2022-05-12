<template>
    <div>
        <sideBarComponent />
        <div class="adminMain">
            <router-view></router-view>
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
    margin-top: 80px;
}

@media screen and (max-width: 767px) {
    .adminMain {
        margin-left: 0px;
    }
}
</style>