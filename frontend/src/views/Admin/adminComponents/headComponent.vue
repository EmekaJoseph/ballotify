<template>
    <div>
        <nav class="navbar navbar-light fixed-top shadow-sm">
            <div class="container-fluid">
                <span class="d-md-none fw-bold">Ballotify</span>
                <div class="inputSearch d-none d-md-block">
                    <input type="text" class=" form-control form-control-sm" placeholder="search.." style="width:250px">
                    <span class="icon">
                        <i class="bi bi-search"></i>
                    </span>
                </div>
                <span class="dropdown d-none d-md-block">
                    <a class="nav-link dropdown-toggle" href="#" id="accountDrop" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-badge"></i>&nbsp;Account
                        <i class="bi bi-chevron-down"></i>
                    </a>
                    <ul class="dropdown-menu fade-up" aria-labelledby="accountDrop">
                        <li>
                            <router-link class="dropdown-item" to="/admin">
                                <i class="bi bi-gear"></i>&nbsp;&nbsp;settings
                            </router-link>
                        </li>

                        <li class="text-muted">
                            <a class="dropdown-item" @click.prevent href="#">
                                <i class="bi bi-dash-circle"></i>&nbsp;&nbsp;log out
                            </a>
                        </li>
                    </ul>
                </span>
                <nav class="d-md-none navbar">
                    <h3 class="fw-bold" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAdmin"
                        aria-controls="offcanvasAdmin">
                        <i class="bi bi-list"></i>
                    </h3>
                </nav>
            </div>
        </nav>
    </div>
</template>

<script setup>

import { onMounted, inject } from 'vue'
import { useRouter, useRoute } from 'vue-router'
// import { useUserStore } from '@/stores/user'
const router = useRouter()
const route = useRoute()

const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");



// const user = useUserStore()

function signOut() {
    // user.signOut()
    router.replace({ name: 'Admin' })
}

</script>

<style scoped>
.navbar {
    margin-left: 250px;
    padding: 15px;
    background-color: #fff;
}

@media screen and (max-width: 767px) {
    .navbar {
        margin-left: 0px;
        padding: 5px;
    }
}


/* input search  ############################################### */
.inputSearch .icon {
    position: absolute;
    margin-top: 21px;
    margin-right: 50px;
    margin-left: 220px;
    top: 0;
    font-size: 14px;
    cursor: pointer;
}

/* input search  ############################################### */


/* Acount DropDowm #############################################*/
#accountDrop {
    color: #2c3e50;
    font-size: 14px;
    /* pointer-events: none; */
    margin-right: 55px;
}

.dropdown-toggle::after {
    display: none;
}

.dropdown-menu {
    font-size: small;
    min-width: 140px !important;
    border: 1px solid #eee;
}

.dropdown-menu :is(a, i) {
    color: #2c3e50;
    margin-block: 6px;
}

.dropdown-item:hover {
    background: transparent;
    color: v-bind(cc1);
    /* font-weight: bold; */
}

.dropdown-item:hover i {
    color: v-bind(cc1);
}

/* Acount DropDowm ##################################### */
</style>