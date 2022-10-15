<template>
    <div>
        <div v-if="event.isLoading" class="page-loader">
            <div class="loading"></div>
        </div>

        <div v-else class="container">
            <div v-if="event.error">
                <brokenLinkVue />
            </div>
            <div v-else>
                <section class="menu-card">
                    <div class="container">
                        <div class="row justify-content-center gy-4">
                            <div @click="goTo('Leaderboard')" class="col-xl-3 col-md-6" data-aos="zoom-out"
                                data-aos-delay="200">
                                <div class="card-item position-relative">
                                    <div class="icon"><i class="bi bi-bar-chart-fill icon"></i></div>
                                    <span class="hh">Leaderboard</span>
                                    <p class="text-center">See progress and results</p>
                                </div>
                            </div>

                            <div @click="goTo('Vote_Stats')" class="col-xl-3 col-md-6 " data-aos="zoom-out"
                                data-aos-delay="200">
                                <div class="card-item position-relative">
                                    <div class="icon"><i class="bi bi-people-fill icon"></i></div>
                                    <span class="hh">Statistics</span>
                                    <p class="text-center">See number voters and others stats
                                    </p>
                                </div>
                            </div>

                            <div v-if="!event.isEpired" class="col-xl-3 col-md-6" data-aos="zoom-out"
                                data-aos-delay="400">
                                <div ref="modalBtn" class="card-item  position-relative" data-bs-toggle="modal"
                                    data-bs-target="#codeEntryModal">
                                    <div class="icon"><i class="bi bi-ui-checks-grid icon"></i></div>
                                    <span class="hh">Vote Now</span>
                                    <p class="text-center">
                                        Enter voting code to vote.
                                    </p>
                                </div>
                            </div>
                            <div v-else class="col-xl-3 col-md-6">
                                <div class="card-item-grey  position-relative">
                                    <div class="icon"><i class="bi bi-ui-checks-grid icon"></i></div>
                                    <span class="hh">Vote Now</span>
                                    <p class="text-center small text-danger">
                                        Voting expired
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <codeEntryModal @codeIsValid="navigateToVoting" />
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, inject } from 'vue';
import codeEntryModal from './components/codeEntryModalComponent.vue';
import brokenLinkVue from './components/brokenLink.vue';
import { useRoute, useRouter } from "vue-router";
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';

const vSt = voteStore()
const { event, currentVoter } = storeToRefs(vSt)

const { cc1 }: any = inject("c$");
const route = useRoute()
const router = useRouter()


function goTo(destination: string) {
    router.push({
        name: destination,
        query: { e: route.query.e }
    })
}

const modalBtn = ref<any>(null)
function navigateToVoting(id) {
    currentVoter.value = id
    goTo('Voting')
    modalBtn.value.click()
}

</script>

<style scoped>
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

.menu-card .card-item-grey {
    background: #fff;
    height: 100%;
    border: 1px #ccc solid;
    border-radius: 20px;
    padding: 50px;
}



.menu-card .card-item .icon {
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.menu-card .card-item-grey .icon {
    margin-bottom: 10px;
    display: flex;
    justify-content: center;
}

.menu-card .card-item .icon i {
    color: v-bind(cc1);
    font-size: 4rem;
    transition: 0.3s;
}

.menu-card .card-item-grey .icon i {
    color: #eee;
    font-size: 4rem;
}

.menu-card .card-item .hh {
    font-weight: 600;
    /* margin-bottom: 15px; */
    font-size: 24px;
    display: flex;
    justify-content: center;
    color: v-bind(cc1);
}

.menu-card .card-item-grey .hh {
    font-weight: 600;
    /* margin-bottom: 15px; */
    font-size: 24px;
    display: flex;
    justify-content: center;
    color: #eee;
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


    .menu-card .card-item {
        padding: 50px 30px;
        margin-inline: 20px;
        border: 1px solid v-bind(cc1);
    }

    .menu-card .card-item-grey {
        padding: 50px 30px;
        margin-inline: 20px;
    }


}
</style>