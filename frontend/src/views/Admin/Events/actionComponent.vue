<template>
    <div class="card p-2 actionCard">
        <div class="card-body">
            <div class="row justify-content-center g-3">
                <div class="col-lg-7 text-center text-lg-start">
                    <span v-if="isActive" class="fst-italic  py-1 px-4 border-0">
                        <i class="bi bi-check-circle-fill"></i>
                        Currently Active!
                    </span>
                    <span v-else class="fst-italic py-1 px-4 border-0">
                        <i class="bi bi-x-circle-fill"></i>
                        Voting is Inactive!
                    </span>
                    <span @click="isActive = !isActive" class="fw-bold">
                        <button v-if="!isActive" class="btn btn-link text-success fw-bold">
                            <i class="bi bi-play-fill"></i> Click to Activate/Start
                        </button>
                        <button v-else class="btn btn-link text-danger fw-bold">
                            <i class="bi bi-pause-fill"></i> Pause/Deactive
                        </button>
                    </span>

                </div>

                <div class="col-lg-5">
                    <transition name="xSlide">
                        <div v-if="isShowingLink" class="input-group">
                            <input v-model="source" type="text" class="form-control" placeholder="" aria-label=""
                                aria-describedby="button-addon2">
                            <button @click.prevent='copy()' v-if='!copied' class="btn-sm btn btn-outline-secondary"
                                type="button" id="button-addon2">
                                <i class="bi bi-clipboard2"></i> copy
                            </button>
                            <button v-else class="btn-sm  btn btn-outline-light text-success">
                                <i class="bi bi-check"></i> copied!
                            </button>
                        </div>
                        <div v-else>
                            <button @click="generateLink" class="btn btn-outline-secondary" type="button">
                                <i class="bi bi-eye-slash"></i> Reveal link
                            </button>
                        </div>
                    </transition>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { eventStore } from './eventStore';
import { storeToRefs } from 'pinia';
import { useClipboard } from '@vueuse/core'



const event_ = eventStore()
const { event }: any = storeToRefs(event_)



// temp
const isActive = ref(false)
// temp


const isShowingLink = ref(false)
const source = ref('')
const { text, copy, copied, isSupported } = useClipboard({ source })

function generateLink() {
    isShowingLink.value = true
    source.value = `${window.location.origin}/vote/home/?e=${btoa(event.value.event_id)}`
}

</script>

<style scoped>
.actionCard {
    background-color: #f6f7f383;
    border: 1px solid #07787C;
}
</style>