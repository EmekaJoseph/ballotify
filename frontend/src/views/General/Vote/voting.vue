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
                <div v-if="voting.thisVoter=== null">
                    <votingLinkExpired />
                </div>
                <div v-else>
                    <div class="my-3">
                        <div class="col-md-12 mt-5">
                            <span class="fs-5">Welcome <span
                                    class="fw-bold text-capitalize">{{thisVoterName()}},</span></span>
                            <h5 class="small  text-danger">
                                Do not refresh or leave this page until you are done!
                            </h5>
                        </div>
                        <div class="col-md-12 mt-3">
                            <div class="card main-card">
                                <div class="card-body">
                                    <div class="row gy-3">
                                        <div v-for="(post, index) in voting.votingData" :key="index"
                                            class="col-xl-4 col-lg-6">
                                            <div class="card post-card">
                                                <h5 class="card-title h6 text-capitalize"><i
                                                        class="bi bi-person-bounding-box text-muted"></i>&nbsp;
                                                    {{post.position_name}}
                                                </h5>
                                                <div class="card-body choice-span">
                                                    <div :class="{'chosen': i.chosen}"
                                                        @click="chooseThis(i.position_id, i.id )"
                                                        v-for="(i, ind) in getChecked(post.candidates)"
                                                        class="name-card">
                                                        <i v-if="!(i.chosen)" class="bi bi-circle"></i>
                                                        <i v-else class="bi bi-check-circle-fill"></i>
                                                        <span
                                                            :class="[{'text-custom': i.chosen}, {'fw-bold': i.chosen}]"
                                                            class="ms-3 text-capitalize">{{i.lastname}}
                                                            {{i.firstname}}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button v-if="!isSaving" @click.prevent="CheckChoices"
                                                class="btn customBtn float-end"><i class="bi bi-check-circle-fill"></i>
                                                Submit</button>
                                            <button v-else class="btn customBtn float-end " disabled>
                                                <i class="bi bi-hourglass-split"></i> Saving...
                                            </button>
                                            <button @click.prevent="router.back()" v-show="!isSaving"
                                                class="btn btn-secondary float-end me-3">
                                                <i class="bi bi-x-lg"></i> Cancel
                                            </button>
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
import { inject, ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import brokenLinkVue from './components/brokenLink.vue';
import votingLinkExpired from './components/votingLinkExpired.vue';
import { voteStore } from './voteStore'
import { storeToRefs } from 'pinia';
import Swal from 'sweetalert2'
import server from '@/store/apiStore'

const router = useRouter()

const vSt = voteStore()
const { event, voters, voting } = storeToRefs(vSt)

const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");


onMounted(async () => {
    await vSt.getEventDetails()
    await vSt.votingDataQuery()
})

const thisVoterName = () => {
    let thisVoter = voters.value.find((x: { id: string; }) => x.id == voting.value.thisVoter)
    return thisVoter == undefined ? '' : `${thisVoter.firstname}`
}

const getChecked = (list: any[]) => {
    list.forEach((el: { chosen: boolean; }) => {
        el.chosen == true ? true : false
    });

    return list
}

const chooseThis = (position_id: string, id: string) => {
    let thisPosition = voting.value.votingData.find((x: { position_id: string; }) => x.position_id == position_id)
    let candidates = thisPosition.candidates

    candidates.forEach((el: { chosen: boolean; id: any; }) => {
        el.chosen = false
        if (el.id == id) {
            el.chosen = true
        }
    })
}



function CheckChoices() {
    let ChoicesArray: any = []
    let list = voting.value.votingData;
    list.forEach((el: any) => {
        let newObj: any = {}
        el.candidates.forEach(x => {
            if (x.chosen == true) {
                newObj.position_id = x.position_id;
                newObj.id = x.id
                ChoicesArray.push(newObj)
            }
        })
    })


    if (!ChoicesArray.length || (ChoicesArray.length !== list.length)) {
        Swal.fire({
            toast: true,
            icon: 'warning',
            title: 'Please Complete Voting',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
        })
    }
    else {
        Swal.fire({
            title: `Submit Vote?`,
            text: "You can ONLY do this once!",
            showCancelButton: true,
            confirmButtonColor: '#03787c',
            cancelButtonColor: '#012A2B',
            confirmButtonText: 'Yes, Submit',
            background: `#012A2B`,
            color: '#fff',
            width: 'auto',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                let ChoicesArrayMap = ChoicesArray.map(x => x.id)
                let obj = {
                    voter_id: voting.value.thisVoter,
                    choices: ChoicesArrayMap.toString()
                }
                submitVote(obj)
            }
        })
    }

}


const isSaving = ref(false)

async function submitVote(obj: object) {
    isSaving.value = true
    try {
        var { data } = await server.submitVote(obj)
        if (data == 1) {
            isSaving.value = false
            voting.value.votingData = null
            Swal.fire({
                toast: true,
                icon: 'success',
                title: 'Vote Complete!',
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: false,
            })
            router.back()
        }
    } catch (error) {
        Swal.fire({
            toast: true,
            icon: 'error',
            title: 'Sorry, Operation Failed',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: false,
        })
        isSaving.value = false
    }
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


.post-card {
    border: 1px solid #f7f3f3;
    padding: 0.85rem;
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
}

.card-title {
    color: v-bind(ccThk);
    padding-bottom: 0.9rem;
    margin: 0px;
    font-weight: bolder;
}

.choice-span {
    background-color: #f5f5f5;
    padding: 0.7rem;
}

.name-card {
    padding: 0.4rem;
    background-color: #fff;
    margin-block: 9px;
    /* box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important; */
    box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
    cursor: pointer;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.name-card:hover {
    border: 1px solid v-bind(cc1);
}

.chosen {
    border: 1px solid v-bind(cc1);
    color: v-bind(cc1);
    background-color: v-bind(ccBg);
}

.main-card {
    /* border: 1px solid #f7f3f3; */
    padding: 0.85rem;
    background-color: v-bind(ccBg);
}
</style>