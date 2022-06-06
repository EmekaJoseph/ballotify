<template>
    <div class="col-lg-12 col-xl-7">
        <div class="card mainCard">
            <div class="card-header">Members
                <span class="badge rounded-pill bg-light text-dark">
                    {{ data.length }}
                </span>
                <span v-if="data.length" class="float-end">
                    <button class="btn btn-outline-success float-end btn-sm p-0 px-3 m-0">
                        <i class="bi bi-plus-circle"></i> add members
                    </button>
                </span>
                <transition name="xSlide">
                    <span v-if="aMember.isChecked" class="float-end">
                        <button class="btn btn-outline-danger me-2 float-end btn-sm p-0 px-3 m-0">
                            <i class="bi bi-folder-minus"></i> Remove
                        </button>
                        <button class="btn btn-outline-primary me-2 float-end btn-sm p-0 px-3 m-0">
                            <i class="bi bi-folder-symlink"></i> Move
                        </button>
                    </span>
                </transition>
            </div>
            <div v-if="data.length" class="card-body row">
                <div class="table-responsive">
                    <table class="table table-sm text-nowrap table-borderless">
                        <thead>
                            <tr>
                                <th class="smallCol"><input @change="toggleAll" v-model="allCheck"
                                        class="form-check-input" type="checkbox"></th>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Move</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, index) in data" :key="val.id">
                                <td><input v-model="val.checked" class="form-check-input" type="checkbox"></td>
                                <td class="text-capitalize">{{ val.firstname + ' ' + val.lastname }}</td>
                                <td>{{ val.gender }}</td>
                                <td>
                                    <button class="m-0 p-0 btn btn-sm text-primary">
                                        <i class="bi bi-folder-symlink"></i> Move to..
                                    </button>
                                </td>
                                <td>
                                    <button class="m-0 p-0 btn btn-sm text-danger">
                                        <i class="bi bi-folder-minus"></i> Remove
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="card-body emptytable">
                <div class="row justify-content-center butt">
                    <span class="text-center"><i class="bi bi-folder-plus bi-lg"></i></span>
                    <h5 class="text-center text-muted text1">Empty List</h5> <br>
                    <h6 class="text-center text-muted text1">click <i class="bi bi-folder-plus"></i> to add members</h6>
                    <span class="text-center text2">add members</span> <br>

                    <!-- <small class="text-center">
                        <button class="btn btn-link text-success btn-sm">
                            add members..
                        </button>
                    </small> -->
                </div>
            </div>
        </div>

    </div>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from 'vue';

interface Props {
    data: any[]
}
const prop = defineProps<Props>()



const allCheck = ref(false)
function toggleAll() {
    prop.data.forEach(chk => {
        chk.checked = allCheck.value
    });
}

const aMember = reactive({
    isChecked: computed(() => { return prop.data.find(x => x.checked == true) })
})

</script>

<style scoped>
.card {
    border: 2px solid #eee;
}

.mainCard {
    min-height: 70vh;

}

.emptytable {
    display: flex;
    justify-content: center;
    align-items: center;
}


.bi-lg {
    font-size: 6rem;
    color: #ece8e8;
    cursor: pointer;
}

.bi-lg:hover {
    color: #c7bfbf;
}

.text2 {
    display: none;
}

.butt:hover .text1 {
    display: none;
}

.butt:hover .text2 {
    display: block;
}

@media (max-width: 550px) {
    .bi-lg {
        font-size: 3rem;
    }

    .mainCard {
        min-height: 50vh;

    }
}
</style>