<template>
    <div>
        <div class="modal fade show" data-backdrop="static" data-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header"> New member
                        <span class="float-end">
                            <button @click="emit('closeModal')" class="btn btn-close btn-close-white"></button>
                        </span>
                    </div>
                    <div class="modal-body p-sm-4">
                        <form>
                            <div class="row gy-3">
                                <div class="col-sm-6">
                                    <label>first name:</label>
                                    <input v-model="person.firstname" type="text" class="form-control w-100">
                                </div>

                                <div class="col-sm-6">
                                    <label>last name:</label>
                                    <input v-model="person.lastname" type="text" class="form-control w-100">
                                </div>
                                <div class="col-sm-6">
                                    <label>email:</label> <small class="text-muted"> *valid email</small>
                                    <input v-model="person.email" type="text" class="form-control w-100">
                                </div>
                                <div class="col-sm-6">
                                    <label>phone:</label>
                                    <input v-model="person.phone" type="number" class="form-control w-100">
                                </div>

                                <div class="col-sm-6">
                                    <label>birthday:</label>
                                    <Datepicker hideOffsetDates :format="format" :flow="flow" v-model="person.dob"
                                        :enableTimePicker="false" :clearable="false" placeholder="birthday" autoApply />
                                </div>
                                <div class="col-sm-6">
                                    <label>gender:</label>
                                    <v-select v-model="person.gender" :clearable="false" :searchable="false"
                                        class="vSelect" :options="['Male', 'Female']" />
                                </div>
                                <div class="col-sm-12">
                                    <label>group:</label>

                                    <v-select :value="person.group" v-model="person.group" placeholder="none"
                                        class="vSelect" :options="groups" />
                                    <button @click.prevent="openNewGroup = true"
                                        class="float-end text-decoration-none btn-sm ms-2 btn btn-link text-danger px-3">
                                        <i class="bi bi-plus-circle-dotted"></i> add group?
                                    </button>
                                </div>
                                <!-- <div class="col-sm-6 ">
                                    <button @click.prevent="checkForm" class="customBtn mt-4 p-2 w-100"
                                        style="border-radius: 5px"><i class="bi bi-save2"></i>&nbsp;
                                        Save</button>
                                </div> -->
                                <div>
                                    <span class="float-end mt-2">
                                        <button @click.prevent="checkForm" class="customBtn btn-sm w-100 p-2 px-5"><i
                                                class="bi bi-save2"></i>&nbsp;
                                            Save</button>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        <newGroupModal v-if="openNewGroup" @added="re_getGroupNames" @close-modal="openNewGroup = false" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
import useFunc from '@/store/useFunction'
import newGroupModal from '@/views/Admin/Groups/newGroupModalComponent.vue'
const orgId = useAdminStore().getData.org_id
const monthStr = useFunc.fx.monthStr
const emit = defineEmits(["closeModal"]);
const date = ref()
const groups = ref([])

onMounted(() => {
    getGroupNames()
})

const openNewGroup = ref(false)

async function re_getGroupNames() {
    await getGroupNames()
    person.value.group = groups.value[groups.value.length - 1]
}

async function getGroupNames() {
    try {
        var { data } = await server.getGroupNames(orgId)
        if (data) {
            let groups1 = data.groups;
            groups.value = groups1.map((x: { id: string; group_name: string; }) => ({ id: x.id, label: x.group_name }))
        }
    } catch (error) {
        console.log(error);
    }
}

const person = ref<any>({
    firstname: '',
    lastname: '',
    email: '',
    dob: null,
    phone: '',
    gender: 'Male',
    group: 'none'
})
const flow = ref(['month', 'calendar']);
const format = (date: Date) => {
    const day = date.getDate();
    const month = monthStr(date)
    return `${month} ${day}`;
}




function checkForm() {
    console.log(person.value);
}
</script>

<style>
.modal {
    display: block;
    background-color: #11111144;
}

.vSelect .vs__dropdown-toggle {
    height: 37px;
}

.vSelect .vs__search::placeholder {
    color: #b5b5b5;
}


.dp__month_year_select[aria-label="Open years overlay"] {
    display: none;
}

/* .dp__input {
    font-size: small;
} */

.dp__inner_nav,
.dp__button,
.dp__overlay_action,
.dp__button_bottom {
    display: none;
}
</style>