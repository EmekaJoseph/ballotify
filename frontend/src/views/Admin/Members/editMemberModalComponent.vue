<template>
    <div>
        <div class="modal fade show" data-backdrop="static" data-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header">
                        <span class="text-white">
                            <i class="bi bi-person-fill"></i>
                            {{ person.firstname + ' ' + person.lastname }}
                        </span>
                        <span class="float-end">
                            <button @click="emit('close')" class="btn btn-close btn-close-white"></button>
                        </span>
                    </div>
                    <div class="modal-body p-sm-4">
                        <form>
                            <div class="row gy-3">
                                <div class="col-sm-6">
                                    <label>first name: <span class="text-danger">*</span></label>
                                    <input :class="{ 'formError': err.firstname }" v-model="person.firstname"
                                        type="text" class="form-control w-100">
                                    <small class="text-danger">{{ err.firstname }}</small>
                                </div>

                                <div class="col-sm-6">
                                    <label>last name: <span class="text-danger">*</span></label>
                                    <input :class="{ 'formError': err.lastname }" v-model="person.lastname" type="text"
                                        class="form-control w-100">
                                    <small class="text-danger">{{ err.lastname }}</small>
                                </div>
                                <div class="col-sm-6">
                                    <label>email:</label>
                                    <input disabled :class="{ 'formError': err.email }" v-model="person.email"
                                        type="text" class="form-control w-100">
                                </div>
                                <div class="col-sm-6">
                                    <label>phone: <span class="text-danger">*</span></label>
                                    <input :class="{ 'formError': err.phone }" v-model="person.phone" type="text"
                                        @input="replace" class="form-control w-100">
                                    <small class="text-danger">{{ err.phone }}</small>
                                </div>

                                <div class="col-sm-6">
                                    <label>birthday: </label>
                                    <Datepicker :class="{ 'formError': err.birthday }" hideOffsetDates :format="format"
                                        :flow="flow" v-model="person.birthday" :enableTimePicker="false"
                                        :clearable="false" placeholder="birthday" autoApply />
                                </div>
                                <div class="col-sm-6">
                                    <label>gender:</label>
                                    <v-select v-model="person.gender" :clearable="false" :searchable="false"
                                        class="vSelect" :options="['Male', 'Female']" />
                                </div>
                                <div class="col-sm-12">
                                    <label>group:</label>
                                    <v-select v-model="person.group" placeholder="none" class="vSelect"
                                        :options="groups" />
                                    <!-- <button @click.prevent="grpModalOpen = true"
                                        class="float-end text-decoration-none btn-sm ms-2 btn btn-link px-3">
                                        <i class="bi bi-plus-circle-dotted"></i> create new group?
                                    </button> -->
                                </div>

                                <div>
                                    <span class="mt-2">
                                        <div class="col-md-12 mt-4">
                                            <button v-if="isEditing" @click.prevent="checkForm"
                                                class="customBtn btn-lg w-100"><i class="bi bi-save2"></i>&nbsp;
                                                Update</button>
                                            <button v-else disabled class="btn btn-secondary btn-lg w-100"><i
                                                    class="bi bi-save2"></i>&nbsp;
                                                Update</button>
                                        </div>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        <!-- <newGroupModal v-if="grpModalOpen" @added="re_getGroupNames" @close-modal="grpModalOpen = false" /> -->
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, watch } from 'vue';
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
import useFunc from '@/store/useFunction'
// import newGroupModal from '@/views/Admin/Groups/newGroupModalComponent.vue'
import { newMemberInterface } from '@/types'
import { membersData } from './members-data';
import { storeToRefs } from 'pinia'
import Swal from 'sweetalert2'

const mStore = membersData()
const { groups }: any = storeToRefs(mStore)


interface Props {
    id: string,
}
const prop = defineProps<Props>()

let data: any = mStore.memberQuery(prop.id)

const orgId = useAdminStore().getData.org_id
const emit = defineEmits(["close", "saved"]);
const fx = useFunc.fx

async function re_getGroupNames() {
    await mStore.getGroupNames(orgId)
    person.group = groups.value[groups.value.length - 1]
}


const grpModalOpen = ref(false)

// date picker
const flow = ref(['month', 'calendar']);
const format = (date: Date) => {
    const day = date.getDate();
    const month = fx.monthStr(date)
    return `${month} ${day}`;
}


const initForm: newMemberInterface = reactive({
    firstname: data.firstname,
    lastname: data.lastname,
    email: data.email,
    phone: data.phone,
    birthday: new Date(data.birthday + '-2022'),
    gender: data.gender == 'M' ? 'Male' : 'Female',
    group: data.group,
    err: {
        firstname: '',
        lastname: '',
        email: '',
        phone: '',
        birthday: '',
        gender: '',
        group: '',
    }
})

const person = reactive({ ...initForm });
const err = reactive({ ...initForm.err });

function showError(field: string) {
    err[field] = 'required field'
}


function checkForm() {
    Object.assign(err, initForm.err);
    if (person.firstname == '') { showError('firstname'); return }
    if (person.lastname == '') { showError('lastname'); return }
    if (person.phone == '') { showError('phone'); return }
    updateMember()
}


const isEditing = ref(false)
watch(person, () => {
    isEditing.value = true
})

const isSaving = ref<boolean>(false)
async function updateMember() {
    isSaving.value = true
    let obj = {
        id: prop.id,
        firstname: person.firstname,
        lastname: person.lastname,
        phone: person.phone,
        birthday: fx.birthdayStr(person.birthday),
        group_id: (person.group == null) ? "0" : person.group.id,
        gender: (person.gender == 'Male') ? 'M' : 'F'
    }

    try {
        var { data } = await server.updateMember(obj)
        if (data == 1) {
            isSaving.value = false
            emit('close')
            emit('saved')
            Swal.fire({
                toast: true,
                icon: 'success',
                title: 'Updated',
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: false,
            })
        }
    } catch (error) {
        console.log(error);
        isSaving.value = false
    }

}

function replace(e) {
    let val = e.target.value
    person.phone = val.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');

}

</script>

<style scoped>
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

.dp__range_end,
.dp__range_start,
.dp__active_date,
.dp__overlay_cell_active {
    background: #03787c;
}

.formError {
    border: 1px solid var(--bs-danger);
}

small {
    font-size: 12px;
}
</style>