<template>
    <div class="col-md-12">
        <div class="card" style="min-height: 85vh">
            <div class="card-header">Members List <span class="badge rounded-pill bg-light text-dark">{{ oData.length
            }}</span>
                <span class="float-end optionsBtn">
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
                </span>
            </div>
            <div class="card-body">
                <div v-if="data.length" class="row justify-content-center my-4">
                    <span @click="emit('openModal')" class="text-center"><i class="bi bi-person-plus plus"></i></span>
                    <div class="text-center text-muted">You do not have any members yet.</div> <br>
                    <small class="text-center">
                        Click the icon <i class="bi bi-person-plus"></i> to add new member
                    </small>
                </div>
                <div v-else>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="smallCol"><input @change="toggleAll" v-model="mCheck"
                                            class="form-check-input" type="checkbox"></th>
                                    <th class="smallCol">S/N</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Satus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(i, index) in oData">
                                    <td><input v-model="i.checked" class="form-check-input" type="checkbox">
                                    </td>
                                    <th>{{ index + 1 }}</th>
                                    <td>{{ i.name }}</td>
                                    <td>meme</td>
                                    <td>meme</td>
                                    <td>meme</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, inject, ref, onMounted } from 'vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");
const emit = defineEmits(["openModal"]);
defineProps({
    data: {
        type: Array,
        default: []
    }
})
const oData = ref([
    { id: 1, name: 'Ajoke' }, { id: 2, name: 'Samuel' }
])

onMounted(() => {
    oData.value.forEach((x) => {
        x.checked = false
    })
})

const mCheck = ref(false)
function toggleAll() {
    oData.value.forEach(chk => {
        chk.checked = mCheck.value
    });
}

function showChecked() {
    let newData = oData.value.filter((x) => x.checked == true)
    let ids = newData.map(x => x.id)
    console.log(newData, ids);
}
</script>

<style scoped>
.plus {
    color: #ece8e8;
    font-size: 12rem;
    cursor: pointer;
}

.plus:hover {
    color: #c3ece1;
}

.bi:not(.plus) {
    font-weight: bolder;
    color: #111;
    font-size: 18px;
}

.dropdown-item:hover {
    background: transparent;
    color: v-bind(cc1);
    /* font-weight: bold; */
}

.dropdown-item:hover i {
    color: v-bind(cc1);
}


.form-check-input {
    width: 22px;
    height: 22px;
    cursor: pointer;
}

.form-check-input:checked {
    background-color: v-bind(cc1);
    border-color: v-bind(cc1);
}

table td:nth-child(2),
th:nth-child(2) {
    text-align: center;
}

.smallCol {
    width: 50px;
}
</style>