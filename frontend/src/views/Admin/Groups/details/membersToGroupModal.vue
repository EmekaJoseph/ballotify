<template>
    <div class="modal fade" id="mListModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header text-capitalize">
                    <span class="text-white">
                        Add to: &nbsp;<span class="text-warning">{{ groupStore.fGroupName(group.group_id) }}</span>
                    </span>
                    <span class="float-end">
                        <button ref="btnX" class="btn btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </span>
                </div>
                <div v-if="list.length" class="col-12">
                    <span class="float-end">
                        <div class="mt-3 me-4">
                            <input v-model="searchBox" class="form-control form-control-sm" placeholder="search.."
                                type="text">
                        </div>
                    </span>
                </div>
                <div v-if="list.length">
                    <div class="modal-body p-sm-4">
                        <div>
                            <div class="table-responsive list-span">
                                <table class="table text-nowrap table-borderless table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Group
                                                &nbsp;
                                                <span @click="sortByGroup()">
                                                    <span v-if="groupSortedFrom == '0'" class="sort-btn">
                                                        <i class="bi bi-funnel"></i>
                                                    </span>
                                                    <span v-else class="sort-btn">
                                                        <i class="bi bi-funnel-fill"></i>
                                                    </span>
                                                </span>
                                            </th>
                                            <th class="smallCol"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(v, i) in membersToShow">
                                            <td>{{ v.lastname }} {{ v.firstname }} ({{ v.gender }})</td>
                                            <td>{{ groupStore.fGroupName(v.group_id) }}</td>
                                            <td><input v-model="v.checked" class="form-check-input" type="checkbox">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button @click="addToGroup()" v-if="isChecked.some" type="button" class=" customBtn btn-sm">Add
                            selection</button>
                        <button @click="addToGroup('all')" type="button" class="btn btn-sm btn-outline-dark px-4">Add
                            all</button>
                        <!-- <button ref="btnX" data-bs-dismiss="modal" type="button"
                        class="btn btn-secondary btn-sm">cancel</button> -->
                    </div>
                </div>

                <div v-else class="modal-body text-center ">
                    <div class="list-span"> No record to add</div>
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { groudDetailStore } from './groupDetailStore';
import { storeToRefs } from 'pinia'
import { ref, computed, reactive } from 'vue';

const emit = defineEmits(['add'])

const searchBox = ref('')

const membersToShow = computed(() => {

    if (list.value.length > 0) {
        const filtered = searchBox.value === ""
            ? list.value
            : list.value.filter(wo => Object.values(wo).join("").toLocaleLowerCase().indexOf(searchBox.value.toLocaleLowerCase()) !== -1);
        return filtered;

    }
    else {
        return list.value
    }



})

const groupStore = groudDetailStore()
const { group, membersAddList: list }: any = storeToRefs(groupStore)

const isChecked = reactive({
    some: computed(() => { return list.value.some((x: { checked: boolean; }) => x.checked == true) ? true : false })
})

const btnX = ref<any>(null)
function addToGroup(w?: string) {
    let arr = list.value
    if (w != 'all') {
        arr = list.value.filter((x: any) => x.checked == true)
    }
    arr = arr.map((x: { id: any; }) => ({ id: x.id, group_id: group.value.group_id }))
    emit('add', arr)
    btnX.value.click()
}



//    sorting start ###############################
const groupSortedFrom = ref('1')
function sortByGroup() {
    groupSortedFrom.value = groupSortedFrom.value == '1' ? '0' : '1'
    list.value.sort((a, b) => {
        const groupA = a.group_id.toLowerCase();
        const groupB = b.group_id.toLowerCase();

        let comparison = 0;
        if (groupA > groupB) {
            comparison = (groupSortedFrom.value == '1') ? 1 : -1;
        } else if (groupA < groupB) {
            comparison = (groupSortedFrom.value == '1') ? -1 : 1;
        }
        return comparison;
    })
}
//

</script>

<style scoped>
.list-span {
    height: 300px;
}
</style>