<template>
    <div class="generalBody page-not-home">
        <HeaderComponentVotingVue :scrolled="y" />
        <!-- ======= Contact Section ======= -->
        <section class="main">
            <transition name="xSlide">
                <div v-if="pageIsLoading" class="page-loader">
                    <div class="loading"></div>
                </div>

                <div v-else class="container">
                    <div class="name-span">
                        <span class="large-name ms-3">Name Of event </span>
                    </div>



                    <section class="menu-card">
                        <div class="container">

                            <div class="row justify-content-center gy-4">

                                <!-- <div class="col-xl-4 col-md-6 d-flex" data-aos="zoom-out">
                                    <div class="card-item card-item-link position-relative">
                                        <div class="icon"><i class="bi bi-bar-chart-fill icon"></i></div>
                                        <span class="hh">Leaderboard</span>
                                        <p class="text-center">See progress and results
                                        </p>
                                    </div>
                                </div> -->
                                <!-- End Service Item -->
                                <div class="col-xl-4 col-md-6 d-flex" data-aos="zoom-out" data-aos-delay="200">
                                    <div class="card-item card-item-link position-relative">
                                        <div class="icon"><i class="bi bi-bar-chart-fill icon"></i></div>
                                        <span class="hh">Leaderboard</span>
                                        <p class="text-center">See progress and results</p>
                                    </div>
                                </div>

                                <div class="col-xl-4 col-md-6 d-flex" data-aos="zoom-out" data-aos-delay="200">
                                    <div class="card-item card-item-link position-relative">
                                        <div class="icon"><i class="bi bi-people-fill icon"></i></div>
                                        <span class="hh">Lorem Ipsum</span>
                                        <p class="text-center">See event voters, details and others stats
                                        </p>
                                    </div>
                                </div>
                                <!-- End Service Item -->

                                <div class="col-xl-4 col-md-6 d-flex" data-aos="zoom-out" data-aos-delay="400">
                                    <div class="card-item  position-relative">
                                        <div class="icon"><i class="bi bi-check-lg icon"></i></div>
                                        <span class="hh">Leaderboard</span>
                                        <p class="text-center">
                                        <form @submit.prevent="">
                                            <div class="row gy-2">
                                                <div class="col-12">
                                                    <input type="text" class="form-control" placeholder="voting code..">
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn customBtn w-100">Vote now</button>
                                                </div>
                                            </div>
                                        </form>
                                        </p>
                                    </div>
                                </div><!-- End Service Item -->


                            </div>

                        </div>
                    </section>
                </div>
            </transition>


        </section>
        <!-- End Contact Section -->
        <FooterComponent />
    </div>
</template>

<script setup>
import { onMounted, ref, inject } from 'vue';
import { useWindowScroll } from '@vueuse/core';
import HeaderComponentVotingVue from '@/components/HeaderComponentVoting.vue';
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");
const { x, y } = useWindowScroll()
onMounted(() => {
    window.scrollTo(0, 0);
    setTimeout(() => {
        pageIsLoading.value = false
    }, 500);
})

const pageIsLoading = ref(true)
</script>

<style scoped>
.name-span {
    display: flex;
    justify-content: center;
    /* background-color: v-bind(ccBg); */
    padding: 10px;
    margin-bottom: 20px;
    /* border-bottom: 1px solid v-bind(ccBg); */
}

.large-name {
    font-weight: 900;
    font-size: 1.7rem;
    color: v-bind(cc1);
    text-transform: uppercase;
    text-align: center;
}

@media (max-width: 991px) {
    .large-name {
        font-size: 0.8rem;

    }
}



.loading:before {
    content: "";
    display: inline-block;
    border-radius: 50%;
    width: 100px;
    height: 100px;
    margin: 0 10px -6px 0;
    border: 3px solid v-bind(cc1);
    border-top-color: var(--bs-white);
    -webkit-animation: animate-loading 1s linear infinite;
    animation: animate-loading 1s linear infinite;
}

@-webkit-keyframes animate-loading {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

@keyframes animate-loading {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}




.menu-card .card-item {
    padding: 50px;
    transition: all ease-in-out 0.4s;
    background: #fff;
    height: 100%;
    width: 100%;
    border: 1px #eee solid;
    border-radius: 20px;
}

.menu-card .card-item-link {
    cursor: pointer;
}

.menu-card .card-item .icon {
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.menu-card .card-item .icon i {
    color: v-bind(cc1);
    font-size: 4rem;
    transition: 0.3s;
}

.menu-card .card-item .hh {
    font-weight: 600;
    /* margin-bottom: 15px; */
    font-size: 24px;
    display: flex;
    justify-content: center;
    color: v-bind(cc1);
}

.menu-card .card-item h4 a {
    color: #485664;
    transition: ease-in-out 0.3s;
}

.menu-card .card-item p {
    line-height: 24px;
    font-size: 12px;
    margin-bottom: 0;

}

.menu-card .card-item-link:hover {
    transform: translateY(-10px);
    box-shadow: 0px 0 60px 0 rgba(72, 86, 100, 0.1);
}
</style>