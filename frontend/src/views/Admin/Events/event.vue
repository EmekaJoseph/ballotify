<template>
    <div>
        <div class="name-span">
            <!-- <span></span> -->
            <span v-if="event.name" class="large-name ms-3">{{  event.name  }} <span class="created">created {{
                     event.created 
                    }}</span></span>

            <div class="dropdown">
                <!-- <span class="dropdown-toggle" type="button" id="dropdownMenu2" data-bs-toggle="dropdown"
                    aria-expanded="false">
                    <i class="bi bi-three-dots-vertical"></i>
                </span>
                <ul v-if="!(event.isLoading)" class="dropdown-menu" aria-labelledby="dropdownMenu2">
                    <li><a class="dropdown-item" href="#"><i class="bi bi-trash3"></i> Delete</a></li>
                </ul> -->
            </div>
        </div>

        <div v-if="event.isLoading" class="page-loader">
            <div class="c-loader loaderBlack"></div>
        </div>
        <div v-else>
            <div class="row justify-content-center gy-4">
                <div class="col-12 col-lg-5">
                    <modifyComponent />
                </div>
                <div class="col-lg-7">
                    <candidatesComponent />
                </div>
                <div class="col-lg-12">
                    <votersComponent />
                </div>
                <!-- <div class="col-lg-12"> -->
                <div v-if="(voters.length && candidates.length && positions.length)" class="col-lg-12">
                    <actionComponent />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import modifyComponent from './modify/modifyComponent.vue';
import candidatesComponent from './candidates/candidatesComponent.vue';
import votersComponent from './voters/votersComponent.vue';
import actionComponent from './actionComponent.vue'
import { inject, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { eventStore } from './eventStore';
import { storeToRefs } from 'pinia';



const event_ = eventStore()
const { event, voters, candidates, positions }: any = storeToRefs(event_)

const { cc1, ccBg }: any = inject("c$");
const route = useRoute()

watch(() => route.query, () => {
    loadThisEvent()

})

onMounted(() => {
    loadThisEvent()
})

function loadThisEvent() {
    let event_id = route.query.id;
    if (event_id != undefined) {
        event_.getEventDetails()
    }
}
</script>

<style scoped>
.name-span {
    display: flex;
    justify-content: space-between;
    background-color: v-bind(ccBg);
    /* text-align: center; */
    padding: 10px;
    margin-bottom: 20px;
    border-left: 4px solid v-bind(cc1);
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

    .dropdown-toggle .bi {
        font-size: 0.8rem;
    }
}

.dropdown-toggle .bi {
    color: v-bind(cc1) !important;
    font-size: 1.7rem;
}

.dropdown-menu .bi {
    color: red !important;
}

.dropdown-item:hover {
    background-color: transparent;
}

.created {
    font-size: 11px;
    text-transform: lowercase !important;
    font-weight: 400;

}
</style>