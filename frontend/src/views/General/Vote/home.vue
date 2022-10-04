<template>
    <div class="generalBody page-not-home">
        <HeaderComponentVotingVue :scrolled="y" />
        <!-- ======= Contact Section ======= -->
        <section class="main">
            <transition name="xSlide">
                <div v-if="event.isLoading" class="page-loader">
                    <div class="loading"></div>
                </div>

                <div v-else class="container">
                    <div v-if="event.error">
                        <section class="menu-card">
                            <div class="container">
                                <div class="row justify-content-center gy-4">
                                    <div class="col-md-6" data-aos="zoom-out" data-aos-delay="200">
                                        <div class="card-item position-relative" @click="goToHome">
                                            <div class="icon"><i class="bi bi-file-earmark icon text-muted"></i></div>
                                            <span class="hh text-muted">Broken Link</span>
                                            <p class="text-center">This page link is broken, has been removed or does
                                                not exist</p>
                                            <p class="text-center">
                                                Visit home page instead
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                    <div v-else>
                        <div class="name-span">
                            <div>
                                <div class="large-name">{{event.name}} </div>
                                <div class="small text-center">{{event.desc}}</div>
                            </div>
                        </div>

                        <section class="menu-card">
                            <div class="container">

                                <div class="row justify-content-center gy-4">
                                    <div class="col-xl-3 col-md-6" data-aos="zoom-out" data-aos-delay="200">
                                        <div class="card-item position-relative">
                                            <div class="icon"><i class="bi bi-bar-chart-fill icon"></i></div>
                                            <span class="hh">Leaderboard</span>
                                            <p class="text-center">See progress and results</p>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6 " data-aos="zoom-out" data-aos-delay="200">
                                        <div class="card-item position-relative">
                                            <div class="icon"><i class="bi bi-people-fill icon"></i></div>
                                            <span class="hh">Statistics</span>
                                            <p class="text-center">See event voters, details and others stats
                                            </p>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6" data-aos="zoom-out" data-aos-delay="400">
                                        <div class="card-item  position-relative" data-bs-toggle="modal"
                                            data-bs-target="#codeEntryModal">
                                            <div class="icon"><i class="bi bi-ui-checks-grid icon"></i></div>
                                            <span class="hh">Vote Now</span>
                                            <p class="text-center">
                                                Enter voting code to vote.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                        <div class="text-center small fst-italic text-muted">
                            Created <b>{{event.start.toDateString()}}, {{event.start.toLocaleTimeString()}}</b>, &nbsp;
                            Expires <b>{{event.end.toDateString()}} , {{event.end.toLocaleTimeString()}}</b>
                        </div>
                    </div>
                </div>
            </transition>
        </section>
        <FooterComponent />
        <codeEntryModal />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, inject } from 'vue';
import { useWindowScroll } from '@vueuse/core';
import HeaderComponentVotingVue from '@/components/HeaderComponentVoting.vue';
import codeEntryModal from './components/codeEntryModalComponent.vue';
import { useRoute, useRouter } from "vue-router";
import server from '@/store/apiStore'
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';

const vSt = voteStore()
const { event } = storeToRefs(vSt)

const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const { x, y } = useWindowScroll()
const route = useRoute()
const router = useRouter()
onMounted(() => {
    window.scrollTo(0, 0);
    loadPage()

})



async function loadPage() {
    let Code: any = route.query.e
    let event_id = ''
    try {
        event_id = atob(Code)
        await vSt.getEventDetails(event_id)
    } catch (error) {
        event.value.error = true
    }
}

function goToHome() {
    router.push({
        name: 'Home'
    })
}


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
    font-size: 1.8rem;
    /* color: v-bind(cc1); */
    text-transform: uppercase;
    /* text-align: center; */
    margin-bottom: 0px;
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
    transition: all ease-in-out 0.4s;
    background: #fff;
    height: 100%;
    border: 1px #ccc solid;
    border-radius: 20px;
    cursor: pointer;
    padding: 50px;
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

.menu-card .card-item p {
    /* line-height: 24px; */
    font-size: 12px;
    margin-bottom: 0;

}

.menu-card .card-item:hover {
    border: 1px solid v-bind(cc1);
    transform: translateY(-10px);
    box-shadow: 0px 0 60px 0 rgba(72, 86, 100, 0.1);
}

@media (max-width: 991px) {
    .large-name {
        font-size: 1.3rem;
    }

    .menu-card .card-item {
        padding: 50px 30px;
        margin-inline: 20px;
        border: 1px solid v-bind(cc1);
    }

    .name-span {
        margin-bottom: 0px;
    }
}
</style>