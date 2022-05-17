<script setup>
import { useRoute } from 'vue-router';
import { inject } from 'vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");
defineProps({
    scrolled: {
        type: Number,
        default: 0,
        required: true,
    }
})
const route = useRoute()
</script>

<template>
    <div>
        <header id="header" class="fixed-top d-flex align-items-center"
            :class="{ 'shadow-sm': scrolled > 50, 'bg-white': scrolled > 50 }">
            <div class="container d-flex align-items-center justify-content-between">
                <div class="logo">
                    <span class="fw-bold">
                        <router-link class="text-decoration-none brand-Name" to="/">
                            Ballotify
                        </router-link>
                    </span>
                </div>
                <div v-for="i in 10" :key="i"></div>
                <div>
                    <nav id="navbar" class="navbar">
                        <ul>
                            <li>
                                <router-link v-if="route.name == 'Home'" class="nav-link" to="/about">Help <i
                                        class="bi bi-info-circle"></i></router-link>
                                <router-link v-else class="nav-link" to="/"><i class="bi bi-arrow-left-short"></i> Home
                                </router-link>
                            </li>
                        </ul>
                    </nav>
                </div>

                <nav>
                    <a class="d-none d-md-block nav-link signUpBtn" href="#account_">
                        Account</a>
                </nav>
            </div>
        </header>
    </div>
</template>



<style scoped>
#header {
    z-index: 997;
    transition: all 0.5s;
    padding: 12px 0;
}


.brand-Name:hover {
    color: v-bind(cc1);
}

.navbar {
    padding: 0;
}

.navbar ul {
    margin: 0;
    padding: 0;
    display: flex;
    list-style: none;
    align-items: center;
}

.navbar li {
    position: relative;
}

.navbar a {
    color: #2c3e50;
    font-size: 15px;
}


.navbar a:hover {
    color: v-bind(cc1);
}

.navbar a:hover i {
    color: v-bind(cc1);
}

.signUpBtn {
    background-color: v-bind(cc1);
    border-radius: 5px;
    padding: 4px 14px;
    color: #fff;
}

.signUpBtn:hover {
    background-color: v-bind(ccBtnH);
}

.brand-Name {
    font-size: 20px;
    /* color: #2c3e50; */
    /* color: #BD2c00; */
}
</style>