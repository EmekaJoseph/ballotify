<template>
    <div class="col-lg-12 col-xl-8">
        <div class="card mainCard p-3">
            <div v-if="data.list.length" class="list-span ">
                <div class="table-responsive">
                    <table class="table table-sm text-nowrap table-hover">
                        <thead>
                            <tr>

                                <th class="smallCol"></th>
                                <th></th>
                                <th></th>
                                <th class="smallCol">
                                    <input v-if="data.list.length > 1" @change="toggleAll" v-model="allCheck"
                                        class="form-check-input" type="checkbox">
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, index) in data.list" :key="val.id">
                                <th>{{ index + 1 }}</th>
                                <th></th>
                                <td class="text-capitalize">{{ val.lastname + ' ' + val.firstname }} ({{ val.gender }})
                                </td>
                                <!-- <td>
                                    <button @click="remove(val.id)" class="m-0 p-0 btn btn-sm text-danger actnbtn">
                                        <i class="bi bi-folder-minus"></i> Remove
                                    </button>
                                </td> -->
                                <td>
                                    <input v-model="val.checked" class="form-check-input mt-0" type="checkbox">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div v-else class="card-body emptytable">
                <div class="row justify-content-center butt">
                    <span class="text-center"><i class="bi bi-folder2-open bi-lg"></i></span>
                    <h5 class="text-center text1">Group is empty</h5> <br>
                    <!-- <h6 class="text-center text-muted text1">click <i class="bi bi-folder-plus"></i> to add members</h6> -->

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

<script  lang="ts" setup>
import { computed, reactive, ref, watchEffect } from 'vue';
import { groudDetailStore } from './groupDetailStore';
import { storeToRefs } from 'pinia'

const groupStore = groudDetailStore()
const { group, removeList }: any = storeToRefs(groupStore)

const data = reactive({
    list: computed(() => {
        let tableGroup = group.value.members
        tableGroup.forEach((x: { checked: boolean }) => {
            x.checked = false
        });
        return tableGroup
    })
})


const allCheck = ref(false)
function toggleAll() {
    data.list.forEach((chk: { checked: boolean; }) => {
        chk.checked = allCheck.value
    });
}

watchEffect(() => {
    if (data.list.some(x => x.checked == false)) {
        allCheck.value = false
    }

    if (data.list.some(x => x.checked == true)) {
        let arr = data.list.filter((x: any) => x.checked == true)
        arr = arr.map((x) => ({ id: x.id, group_id: '0' }))
        removeList.value = arr
        console.log(removeList.value);

    }
    else {
        removeList.value = []
    }
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
}

.text1 {
    color: #d8d3d3;
}

/* .bi-lg:hover {
    color: #c7bfbf;
} */

/* .text2 {
    display: none;
}

.butt:hover .text1 {
    display: none;
}

.butt:hover .text2 {
    display: block;
} */

@media (max-width: 550px) {
    .bi-lg {
        font-size: 3rem;
    }

    .mainCard {
        min-height: 50vh;

    }
}

.actnbtn:hover {
    color: #ddee44 !important;
}
</style>