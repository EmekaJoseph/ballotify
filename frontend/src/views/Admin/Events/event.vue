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
                    <div class="card p-2 actionCard">
                        <div class="card-body">
                            <div class="row justify-content-center g-3">
                                <div class="col-lg-3 p-2 text-center text-lg-start">
                                    <span v-if="!isActive" class="fst-italic text-muted">
                                        <i class="bi bi-x-circle-fill text-muted"></i>
                                        Voting is currently
                                        Inactive</span>
                                    <span v-else class="fst-italic text-success">
                                        <i class="bi bi-check-circle-fill text-success"></i> Voting is currently
                                        Active!</span>
                                </div>
                                <div class="col-lg-3">
                                    <span @click="isActive = !isActive">
                                        <button v-if="!isActive" class="btn customBtn w-100">Activate/Start</button>
                                        <button v-else class="btn btn-danger w-100">Deactive</button>
                                    </span>
                                </div>
                                <div class="col-1"></div>
                                <div class="col-lg-5">
                                    <transition name="xSlide">
                                        <div v-if="isShowingLink" class="input-group">
                                            <input v-model="source" type="text" class="form-control" placeholder=""
                                                aria-label="" aria-describedby="button-addon2">
                                            <button @click.prevent='copy()' v-if='!copied'
                                                class="btn-sm btn btn-outline-secondary" type="button"
                                                id="button-addon2">
                                                copy
                                            </button>
                                            <button v-else class="btn-sm  btn btn-outline-light text-dark">
                                                copied!
                                            </button>
                                        </div>
                                        <div v-else>
                                            <button @click="generateLink" class="btn btn-outline-secondary"
                                                type="button">
                                                Reveal link
                                            </button>
                                        </div>
                                    </transition>
                                    <!-- <div class="row">
                                        <div class="col-8">d</div>
                                        <div class="col-4">d</div>
                                    </div> -->
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
import modifyComponent from './modify/modifyComponent.vue';
import candidatesComponent from './candidates/candidatesComponent.vue';
import votersComponent from './voters/votersComponent.vue';
import { inject, onMounted, watch, ref } from 'vue';
import { useRoute } from 'vue-router';
import { eventStore } from './eventStore';
import { storeToRefs } from 'pinia';
import { useClipboard } from '@vueuse/core'



const event_ = eventStore()
const { event, voters, candidates, positions }: any = storeToRefs(event_)

const { cc1, ccBg }: any = inject("c$");
const route = useRoute()

// temp
const isActive = ref(false)
// temp


const isShowingLink = ref(false)
const source = ref('')
const { text, copy, copied, isSupported } = useClipboard({ source })

function generateLink() {
    isShowingLink.value = true
    source.value = `${window.location.origin}/vote/?e=${btoa(event.value.event_id)}`
}








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

.actionCard {
    background-color: #e3e9e983;
    border: 1px solid #ccc;
}
</style>