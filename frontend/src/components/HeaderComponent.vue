<script setup lang="ts">
import offCanvasComponentVue from './offCanvasComponent.vue';

defineProps({
    scrolled: {
        type: Number,
        default: 0,
        required: true,
    }
})
</script>

<template>
    <div>
        <header id="header" class="fixed-top d-flex align-items-center"
            :class="{ 'shadow-sm': scrolled > 50, 'bg-white': scrolled > 50 }">
            <div class="container d-flex align-items-center justify-content-between">
                <div class="logo">
                    <span class="fw-bold">
                        <router-link class="text-decoration-none" style="font-size: 20px" to="/">
                            Ballotify
                        </router-link>
                    </span>
                </div>
                <div v-for="i in 10" :key="i"></div>
                <div>
                    <nav id="navbar" class="navbar d-none d-md-block">
                        <ul>
                            <li>
                                <router-link class="nav-link" to="/">Home</router-link>
                            </li>
                            <!-- <li>
                                <router-link class="nav-link " to="/">Help</router-link>
                            </li> -->
                            <li>
                                <router-link class="nav-link " to="/contact">Reach Us</router-link>
                            </li>
                        </ul>
                    </nav>
                </div>


                <nav class="d-md-none navbar">
                    <h3 class="fw-bold" data-bs-toggle="offcanvas" data-bs-target="#offcanvasGeneral"
                        aria-controls="offcanvasGeneral">
                        <i class="bi bi-list"></i>
                    </h3>
                </nav>
            </div>
        </header>

        <offCanvasComponentVue />
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
}

.navbar a:focus {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px 20px 5px 30px;
    font-size: 15px;
    font-weight: lighter;

    white-space: nowrap;
    transition: 0.3s;
}

.navbar .active,
.navbar .active:focus {
    color: #00BD8C;
    border-bottom: 1px solid #00BD8C;
}

.navbar a:hover {
    font-weight: bold;
}
</style>