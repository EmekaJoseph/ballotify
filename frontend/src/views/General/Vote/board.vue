<template>
    <div>
        <div v-if="event.isLoading" class="page-loader">
            <div class="loading"></div>
        </div>
        <div v-else class="container-fluid">
            <div v-if="event.error">
                <brokenLinkVue />
            </div>
            <div v-else>
                <div class="my-3">
                    <div class="col-md-12 mt-3">
                        <div class="card main-card">
                            <div class="card-body">
                                <div class="row gy-3">
                                    <div v-for="(post, index) in list" :key="index" class="col-xl-4 col-lg-6">
                                        <div class="card post-card">
                                            <h5 class="card-title h6 text-uppercase text-center text-white m-0">
                                                {{post.position_name}}
                                            </h5>
                                            <div class="card-body p-1">
                                                <div v-for="(i, ind) in sortByVotes(post.candidates)" class="name-card">
                                                    <div>
                                                        <span class="text-capitalize small text-white ">{{i.lastname}}
                                                            {{i.firstname}}</span>
                                                        <!-- <span v-if="ind == 0" class="float-end"><i
                                                                class="bi bi-check-circle-fill"></i></span> -->
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-bar"
                                                            :style="{width: getPercent(i.votes)+'%'}" role="progressbar"
                                                            :aria-valuenow="getPercent(i.votes)" aria-valuemin="0"
                                                            aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <div style="font-size: 12px">

                                                        <span class=" text-warnin">{{getPercent(i.votes)}}%</span>
                                                        <span class="float-end text-warnin">
                                                            {{ VotesFn(i.votes)}}
                                                            {{VotesFn(i.votes) !== 1? 'votes': 'vote'}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

</template>

<script setup lang="ts">
import { inject, onMounted } from 'vue';
import brokenLinkVue from './components/brokenLink.vue';
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';


const vSt = voteStore()
const { event, voters, votingMasterData: list } = storeToRefs(vSt)

const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");

async function loadPage() {
    if (event.value.event_id == '') {
        await vSt.getEventDetails()
    }
    await vSt.votingDataQuery()
}

onMounted(() => {
    loadPage();

    setInterval(async () => {
        await vSt.getVoters()
        await vSt.votingDataQuery()

    }, 5000);
})


const sortByVotes = (candidates: any): any => {
    candidates.sort(function (a: any, b: any) {
        return parseInt(atob(b.votes)) - parseInt(atob(a.votes));
    });
    return candidates
}


const VotesFn = (votes: any) => {
    let decoded = atob(votes)
    return parseInt(decoded)
}

const getPercent = (votes: string) => {
    let decoded = atob(votes)
    let votedVoters = voters.value.filter(x => x.voted_status == '1').length
    let percent = (votedVoters == 0) ? 0 : (parseInt(decoded) / votedVoters) * 100
    return Math.floor(percent)
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

.main-card {
    /* border: 1px solid #f7f3f3; */
    padding: 0.85rem;
    background-color: v-bind(ccThk);
}

.post-card {
    border: 1px solid #f7f3f3;
    padding: 0.85rem;
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
    background-color: v-bind(ccBg);
}


.name-card {
    padding: 0.2rem;
    /* background-color: #fff; */
    margin-block: 9px;
    /* box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important; */
    box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
    border-block: 1px solid rgba(204, 204, 204, 0.192);
}





.progress {
    height: 7px;
    background-color: rgba(255, 255, 255, 0.075);
}

.bg-bar {
    background-color: v-bind(cc2)
}

.text-warnin {
    color: v-bind(cc2);
}
</style>