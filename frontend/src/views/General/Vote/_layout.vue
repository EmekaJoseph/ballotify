<template>
    <div class="generalBody page-not-home">
        <HeaderComponentVotingVue :scrolled="y" :name="event.name" />
        <section class="main">
            <div v-if="!event.error && !event.isLoading" class="name-span">
                <div>
                    <div class="large-name">{{event.name}} </div>
                    <div class="small text-center">{{event.desc}}</div>
                </div>
            </div>

            <router-view></router-view>

            <div v-if="!event.error && !event.isLoading" class="text-center small fst-italic text-muted">
                Created <b>{{event.start.toDateString()}}, {{event.start.toLocaleTimeString()}}</b>, &nbsp;
                Expires <b>{{event.end.toDateString()}} , {{event.end.toLocaleTimeString()}}</b>
            </div>
        </section>
        <FooterComponent />
    </div>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue';
import HeaderComponentVotingVue from '@/components/HeaderComponentVoting.vue';
import { useRoute, useRouter } from "vue-router";
import { useWindowScroll } from '@vueuse/core';
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';

const vSt = voteStore()
const { event } = storeToRefs(vSt)

const { x, y } = useWindowScroll()

const route = useRoute()
const router = useRouter()

onMounted(() => {
    checkForQuery()
    getEventDetails()

})

function checkForQuery() {
    let Code: any = route.query.e
    if (Code === undefined) {
        router.replace({
            name: 'Home'
        })
    }
}

async function getEventDetails() {
    window.scrollTo(0, 0);
    let Code: any = route.query.e
    let event_id = ''
    try {
        event_id = atob(Code)
        await vSt.getEventDetails(event_id)
    } catch (error) {
        event.value.error = true
    }
}
watch(route, () => {
    getEventDetails()
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