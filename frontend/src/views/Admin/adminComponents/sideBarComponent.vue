<template>
    <div>
        <newEventModal />
        <div class="sidenav">
            <span class="brand">
                <img src="@/assets/images/ballotify-logo.png" width="45" alt="">
                <!-- Ballotify -->
            </span>
            <menuList />
        </div>
        <adminHeadBar />
    </div>
</template>

<script setup>
import adminHeadBar from './headComponent.vue'
import menuList from './menuListComponent.vue'
import newEventModal from './newEventModalComponent.vue';
</script>

<style scoped>
/* The sidebar menu */
.sidenav {
    height: 100%;
    /* Full-height: remove this if you want "auto" height */
    width: 250px;
    /* Set the width of the sidebar */
    position: fixed;
    /* Fixed Sidebar (stay in place on scroll) */
    z-index: 1;
    /* Stay on top */
    top: 0;
    /* Stay at the top */
    left: 0;
    background-color: #fff;

    overflow-x: hidden;
    /* Disable horizontal scroll */
    /* padding: 25px 10px; */
}

.sidenav .brand {
    padding: 20px 30px 17px 30px;
    /* color: #BD2c00; */
    display: flex;
    font-weight: bold;
    font-size: 20px;
}

@media screen and (max-width: 767px) {
    .sidenav {
        display: none;
    }
}
</style>