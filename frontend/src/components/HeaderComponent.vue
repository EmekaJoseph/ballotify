<script setup lang="ts">
import { useRoute } from 'vue-router';

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
                    <router-link class="d-none d-md-block nav-link signUpBtn" to="/admin">
                        Account</router-link>
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


.navbar .active {
    color: #00BD8C;
    border-bottom: 1px solid #00BD8C;
}

.navbar a:hover {
    color: #05d8a0;
}

.navbar a:hover i {
    color: #05d8a0;
}

.signUpBtn {
    background-color: #00BD8C;
    border-radius: 5px;
    padding: 5px 20px;
    color: #fff;
}

.signUpBtn:hover {
    background-color: #05d8a0;
}

.brand-Name {
    font-size: 20px;
    /* color: #2c3e50; */
    /* color: #BD2c00; */
}
</style>