<template>
    <div class="generalBody page-not-home">
        <HeaderComponentVotingVue :scrolled="y" :name="event.name" />
        <section class="main">
            <div v-if="!event.error && !event.isLoading" class="name-span">
                <div>
                    <div class="large-name text-custom">{{event.name}} </div>
                    <div class="small text-center">{{event.desc}}</div>
                </div>
            </div>

            <router-view></router-view>

            <div class="container">
                <div v-if="!event.isEpired">
                    <div v-if="!event.error && !event.isLoading" class="text-center small fst-italic text-muted">
                        Created <b>{{event.start.toDateString()}}, {{event.start.toLocaleTimeString()}}</b>, &nbsp;
                        Expires <b>{{event.end.toDateString()}} , {{event.end.toLocaleTimeString()}}</b>
                    </div>
                </div>
                <div v-else>
                    <div class="text-center fw-bold fs-5 text-danger">
                        Voting ended {{event.end.toDateString()}} , {{event.end.toLocaleTimeString()}}
                    </div>
                </div>
            </div>
        </section>
        <FooterComponent />
    </div>
</template>

<script setup lang="ts">
import { onMounted, watch, inject } from 'vue';
import HeaderComponentVotingVue from '@/components/HeaderComponentVoting.vue';
import { useRoute, useRouter } from "vue-router";
import { useWindowScroll } from '@vueuse/core';
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';

const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");

const vSt = voteStore()
const { event } = storeToRefs(vSt)

const { x, y } = useWindowScroll()

const route = useRoute()
const router = useRouter()

onMounted(async () => {
    checkForQuery()
    window.scrollTo(0, 0);
    await vSt.getEventDetails()
    checkIfVoteIsExpired()
})

function checkIfVoteIsExpired() {
    let todayDate = event.value.todaysDate.setHours(0, 0, 0, 0)
    let endDate = event.value.end.setHours(0, 0, 0, 0)
    event.value.isEpired = (todayDate > endDate) ? true : false
}


function checkForQuery() {
    let Code: any = route.query.e
    if (Code === undefined) {
        router.replace({
            name: 'Home'
        })
    }
}

watch(route, () => {
    vSt.getEventDetails()
})
</script>

<style scoped>
.name-span {
    display: flex;
    justify-content: center;
    /* background-color: v-bind(ccBg); */
    padding: 0px;
    /* border-bottom: 1px solid v-bind(ccBg); */
}

.large-name {
    font-weight: 900;
    font-size: 1.8rem;
    /* color: v-bind(cc1); */
    text-transform: uppercase;
    /* text-align: center; */
    margin-bottom: 0px;
    /* color: v-bind(ccThk) */
}


@media (max-width: 991px) {
    .large-name {
        font-size: 1.3rem;
    }


    .name-span {
        margin-bottom: 0px;
    }
}
</style>