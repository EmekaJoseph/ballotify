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
                    <div class="card">
                        <h5 class="card-title h6 text-muted">Eligible Voters
                            <span class="badge rounded-pill bg-white text-dark small">{{voters.length}}</span>
                        </h5>
                        <div class="card-body list-span">
                            <h6 class="card-subtitle mb-4 text-muted ">
                                members who are eligble to vote in this election
                            </h6>
                            <p class="card-text">
                            <div class="table-responsive">
                                <table class="table table-sm">
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
import { onMounted, ref, inject } from 'vue';
import brokenLinkVue from './components/brokenLink.vue';
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';

const vSt = voteStore()
const { event, voters } = storeToRefs(vSt)

const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
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
    border: 1px solid #eee;

}

.card-title {
    color: v-bind(ccThk);
    background-color: v-bind(ccBg);
    padding: 0.7rem;
    margin: 0px;
}
</style>