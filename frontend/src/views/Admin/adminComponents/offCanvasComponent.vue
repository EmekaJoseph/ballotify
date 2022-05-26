<template>
    <div>
        <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasAdmin" aria-labelledby="offcanvasAdminLabel">
            <div class="offcanvas-header">
                <span id="offcanvasAdminLabel text-muted">
                    <!-- <img src="@/assets/house34_logo2.png" width="90" alt /> -->
                    <span class="ms-3"> Menu</span>
                </span>
                <button type="button" class="btn btn-link text-white fs-1" data-bs-dismiss="offcanvas">
                    <i class="bi bi-x"></i>
                </button>
            </div>

            <!-- menu -->
            <menuList showOn='offcanvas' />

            <div class="offcanvas-body"></div>
            <span class="logOutSection">
                <span class="logOutBtn" @click="logOut">
                    <i class="bi bi-x text-white"></i>&nbsp;Log Out
                </span>
            </span>
        </div>
    </div>
</template>

<script setup>

import { inject } from 'vue'
import { useAdminStore } from '@/store/user/admin'
import { useRouter } from 'vue-router'
import menuList from './menuListComponent.vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");

const router = useRouter()

const admin = useAdminStore()
function logOut() {
    admin.signOut()
    router.replace({ name: 'Admin' })
}



</script>

<style scoped>
.offcanvas {
    background-color: fff;
    width: 250px;
    border: none;
}

.logOutBtn {
    /* margin: 45px; */
    font-weight: bold;
    font-size: 12px;
    text-decoration: none;
    color: #fff;
    display: flex;
    justify-content: center;
}

.logOutSection {
    background-color: v-bind(ccThk);
    padding: 20px;
    border-top: 2px solid v-bind(cc1);
}
</style>