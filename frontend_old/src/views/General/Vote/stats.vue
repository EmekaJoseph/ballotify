<template>
    <div>
        <div v-if="event.isLoading" class="page-loader">
            <div class="loading"></div>
        </div>
        <div v-else class="container">
            <div v-if="event.error">
                <brokenLinkVue />
            </div>
            <div v-else class="row justify-content-center my-3">
                <div class="col-md-8 mt-4">
                    <div class="card shadow-lg">
                        <h5 class="card-title h6">Eligible Voters
                            <span class="badge rounded-pill bg-secondary text-white small">{{voters.length}}</span>
                            <span @click="reloadVoters()" class="float-end text-custom"><i
                                    class=" bi bi-arrow-clockwise"></i></span>
                        </h5>

                        <div class="card-body list-span">
                            <h6 class="card-subtitle mb-4 text-muted small ">
                                members who are eligble to vote in this election
                            </h6>
                            <p class="card-text">
                            <div v-if="voters.length == 0">
                                <div class=" my-5">
                                    <div class="text-center text-muted">This list is empty, no data to show.</div> <br>
                                </div>
                            </div>
                            <div v-else class="table-responsive">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr v-for="(voter, index) in voters" key="index">
                                            <th>{{(index+1)}}</th>
                                            <td>{{voter.lastname}} {{voter.firstname}}, ({{voter.gender}})</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            </p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup lang="ts">
import { inject } from 'vue';
import brokenLinkVue from './components/brokenLink.vue';
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';

const vSt = voteStore()
const { event, voters } = storeToRefs(vSt)

const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");

function reloadVoters() {
    voters.value = []
    vSt.getVoters()
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

.card {
    border: 1px solid #f7f3f3;
    padding: 1.4rem;
}

.card-title {
    color: v-bind(ccThk);
    padding-bottom: 0.9rem;
    margin: 0px;
    font-weight: bolder;
}

.bi-arrow-clockwise {
    cursor: pointer;
    padding: 5px;
    border-radius: 5px;
    font-weight: bold;
    background-color: #f5f5f5;
}

.bi-arrow-clockwise:hover {
    background-color: v-bind(ccBg);
}
</style>