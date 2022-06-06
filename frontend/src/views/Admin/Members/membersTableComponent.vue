<template>
    <div class="col-md-12">
        <div class="card" style="min-height: 85vh">
            <div class="card-header">Members List <span class="badge rounded-pill bg-light text-dark">{{ members.length
            }}</span>
                <!-- <span class="float-end optionsBtn">
                    <div class="dropdown">
                        <span class="dropdown-toggle" type="button" id="dropdownMenu2" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-three-dots-vertical"></i>
                        </span>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenu2">
                            <li>
                                <a @click="emit('openModal')" class="dropdown-item" href="#">
                                    <i class="bi bi-person-plus"></i> new member
                                </a>
                            </li>
                        </ul>
                    </div>
                </span> -->
                <span v-if="members.length" class="float-end">
                    <button @click.prevent="emit('openModal')"
                        class="btn btn-outline-success float-end btn-sm p-0 px-3 m-0">
                        <i class="bi bi-plus-circle"></i> add new
                    </button>
                </span>
                <transition name="xSlide">
                    <span v-if="aMember.isChecked" class="float-end">
                        <button @click="sendSetToDelete"
                            class="btn btn-outline-danger me-2 float-end btn-sm p-0 px-3 m-0">
                            <i class="bi bi-trash3"></i> Delete
                        </button>
                    </span>
                </transition>
            </div>
            <div class="card-body">
                <div v-if="!members.length" class="row justify-content-center my-4">
                    <span class="text-center"><i class="bi bi-person-x plus"></i></span>
                    <div class="text-center text-muted">You do not have any members yet.</div> <br>
                    <div class="text-center">
                        <button @click.prevent="emit('openModal')" class="btn btn-link">
                            <i class="bi bi-plus-circle-dotted"></i> add new
                            members</button>
                    </div>
                </div>
                <div v-else>
                    <div class="table-responsive">
                        <table class="table table-sm text-nowrap">
                            <thead>
                                <tr>
                                    <th class="smallCol"><input @change="toggleAll" v-model="mCheck"
                                            class="form-check-input" type="checkbox"></th>
                                    <th class="smallCol">S/N</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Group</th>
                                    <th>Birthday</th>
                                    <th>Satus</th>
                                    <th colspan="2">edit/delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(i, index) in members">
                                    <td><input v-model="i.checked" class="form-check-input" type="checkbox">
                                    </td>
                                    <th>{{ index + 1 }}</th>
                                    <td>{{ i.firstname }} {{ i.lastname }}, ({{ i.gender }})</td>
                                    <td>{{ i.email }}</td>
                                    <td>{{ whatGroupName(i.group_id) }}</td>
                                    <td>{{ fx.displayBD(i.birthday) }}</td>
                                    <td v-if="i.verified == 1"><span class="badge bg-successs">verified</span></td>
                                    <td v-else><span class="badge bg-warningg fst-italic">pending</span></td>
                                    <td><button class="btn btn-link btn-sm actnbtn"><i
                                                class="bi bi-pencil"></i></button></td>
                                    <td><button @click="emit('delete', i.id)"
                                            class="btn btn-link btn-sm text-danger actnbtn"><i
                                                class="bi bi-trash3"></i></button></td>

                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import useFunc from '@/store/useFunction'
// const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");
const emit = defineEmits(["openModal", "delete", "deleteSet"]);
const fx = useFunc.fx

interface Props {
    members: any[],
    groups: any[]
}
const prop = defineProps<Props>()

const mCheck = ref(false)
function toggleAll() {
    prop.members.forEach((chk: any) => {
        chk.checked = mCheck.value
    });
}


function sendSetToDelete() {
    let newData = prop.members.filter((x: any) => x.checked == true)
    let ids = newData.map(x => x.id)
    emit('delete', ids.toString())
}

const whatGroupName = (id: string) => {
    let grpObj = prop.groups.find((x: { id: any; }) => x.id == id)
    return grpObj == undefined ? 'None' : grpObj.name
}

const aMember = reactive({
    isChecked: computed(() => { return prop.members.find(x => x.checked == true) })
})

</script>

<style scoped>
.plus {
    color: #ece8e8;
    font-size: 10rem;
    cursor: pointer;
}



table td:nth-child(2),
th:nth-child(2) {
    text-align: center;
}

.smallCol {
    width: 50px;
}

.actnbtn:hover {
    background-color: #eee;
}

.bg-successs {
    background-color: #1987541e;
    color: #198754;
}

.bg-warningg {
    background-color: #c9c9082f;
    color: #919129;
}
</style>