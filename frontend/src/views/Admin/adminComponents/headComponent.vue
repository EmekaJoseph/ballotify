<template>
    <div>
        <nav class="navbar navbar-light fixed-top shadow-sm">
            <div class="container-fluid">
                <span class="d-md-none">Admin <span>| {{ route.name }}</span></span>
                <span></span>
                <!-- <div class="inputSearch d-none d-md-block">
                    <input type="text" class=" form-control form-control-sm" placeholder="search.." style="width:250px">
                    <span class="icon">
                        <i class="bi bi-search"></i>
                    </span>
                </div> -->
                <span class="dropdown d-none d-md-block">
                    <a class="nav-link dropdown-toggle" href="#" id="accountDrop" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">

                        {{ orgName }} <br>
                        <div class="fw-lighter float-end" style="font-size:12px;">
                            {{ _user == '' ? fullName : _user }}
                            <i class="bi bi-chevron-down"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu fade-up" aria-labelledby="accountDrop">
                        <!-- <div class="fullNameSpan">
                            <span class="fullName">
                                <i class="bi bi-person text-white"></i>&nbsp; {{ fullName }}
                            </span>
                        </div> -->
                        <li class="text-muted">
                            <a class="dropdown-item" href="#help" data-bs-toggle="modal" data-bs-target="#helpModal">
                                <i class="bi bi-question-circle"></i>&nbsp;&nbsp;Help
                            </a>
                        </li>
                        <li>
                            <router-link class="dropdown-item" to="/account/settings">
                                <i class="bi bi-sliders"></i>&nbsp;&nbsp;settings
                            </router-link>
                        </li>

                        <li class="text-muted">
                            <a class="dropdown-item" @click.prevent="logOut" href="">
                                <i class="bi bi-box-arrow-left"></i>&nbsp;&nbsp;log out
                            </a>
                        </li>
                    </ul>
                </span>
                <nav class="d-md-none navbar">
                    <h3 class="fw-bold" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAdmin"
                        aria-controls="offcanvasAdmin">
                        <i class="bi bi-filter-right"></i>
                    </h3>
                </nav>
            </div>
        </nav>
        <helpModal />
    </div>
</template>

<script setup>

import { inject, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { adminAccount } from '@/store/admin/account'
import helpModal from './helpModalComponent.vue'
import { storeToRefs } from 'pinia'

const router = useRouter()
const route = useRoute()

const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");

const admin = adminAccount()
const { fullName, orgName, _user } = storeToRefs(admin)

function logOut() {
    admin.signOut()
    router.replace({ name: 'Login' })
}

</script>

<style scoped>
.navbar {
    margin-left: 250px;
    padding: 10px;
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
    font-size: 15px;
    margin-right: 55px;
    font-weight: bold;
}




.dropdown-menu {
    /* font-size: 14px; */
    /* min-width: 140px !important; */
    border: 1px solid #eee;
    border-top: 3px solid v-bind(ccThk);
    padding-top: 0px;
}

.dropdown-menu :is(a, i) {
    color: #2c3e50;
    margin-block: 7px;
}

.dropdown-item:hover {
    background: transparent;
    color: v-bind(cc1);
    /* font-weight: bold; */
}

.dropdown-item:hover i {
    color: v-bind(cc1);
}

.active {
    background-color: transparent;
    /* pointer-events: none; */
}

/* Acount DropDowm ##################################### */
</style>