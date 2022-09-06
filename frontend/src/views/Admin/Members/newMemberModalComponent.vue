<template>
    <div>
        <div class="modal fade" id="newMemberModal" data-backdrop="static" data-keyboard="false" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0">
                    <div class="modal-header"> New member
                        <span class="float-end">
                            <button ref="btnX" class="btn btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </span>
                    </div>
                    <div class="modal-body p-sm-4">
                        <form>
                            <div class="row gy-3">
                                <div class="col-sm-6">
                                    <label>first name: <span class="text-danger">*</span></label>
                                    <input :class="{ 'formError': err.firstname }" v-model="person.firstname"
                                        type="text" class="form-control w-100">
                                    <small class="text-danger">{{  err.firstname  }}</small>
                                </div>

                                <div class="col-sm-6">
                                    <label>last name: <span class="text-danger">*</span></label>
                                    <input :class="{ 'formError': err.lastname }" v-model="person.lastname" type="text"
                                        class="form-control w-100">
                                    <small class="text-danger">{{  err.lastname  }}</small>
                                </div>
                                <div class="col-sm-6">
                                    <label>email:</label> <span class="text-danger">*</span><small class="text-muted">
                                        valid email</small>
                                    <input :class="{ 'formError': err.email }" v-model="person.email" type="text"
                                        class="form-control w-100">
                                    <small class="text-danger">{{  err.email  }}</small>
                                </div>
                                <div class="col-sm-6">
                                    <label>phone: <span class="text-danger">*</span></label>
                                    <input :class="{ 'formError': err.phone }" v-model="person.phone" type="text"
                                        @input="replace" class="form-control w-100">
                                    <small class="text-danger">{{  err.phone  }}</small>
                                </div>

                                <div class="col-sm-6">
                                    <label>birthday: <span class="text-danger">*</span></label>
                                    <Datepicker menuClassName="bd-input" hideInputIcon
                                        :class="{ 'formError': err.birthday }" hideOffsetDates :format="format"
                                        :flow="flow" v-model="person.birthday" :enableTimePicker="false"
                                        :clearable="false" placeholder="birthday" autoApply>
                                    </Datepicker>
                                    <!-- <Datepicker v-model="person.birthday" autoApply :enableTimePicker="false">
                                    </Datepicker> -->

                                    <small class="text-danger">{{  err.birthday  }}</small>
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
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#newGroupModal"
                                        class="float-end text-decoration-none btn-sm ms-2 btn btn-link px-3">
                                        <i class="bi bi-plus-circle-dotted"></i> create new group?
                                    </button>
                                </div>

                                <div>
                                    <span class="mt-2">
                                        <div class="col-md-12">
                                            <button @click.prevent="checkForm" class="customBtn btn btn-lg w-100"><i
                                                    class="bi bi-save2"></i>&nbsp;
                                                Save</button>
                                        </div>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
        <newGroupModal @added="re_getGroupNames" />
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, inject, reactive } from 'vue';
import { adminAccount } from '@/store/admin/account'
import server from '@/store/apiStore'
import useFunc from '@/store/useFunction'
import newGroupModal from '@/views/Admin/Groups/newGroupModalComponent.vue'
import { newMemberInterface } from '@/types'
import Swal from 'sweetalert2'


onMounted(() => {
    getGroupNames()
})


const orgId = adminAccount().getData.org_id
const emit = defineEmits(["newGroup", "saved"]);
const fx = useFunc.fx


async function getGroupNames() {
    try {
        var { data } = await server.getGroupNames()
        if (data) {
            let grp = data.groups;
            groups.value = grp.map((x: { id: string; group_name: string; }) => ({ id: x.id, label: x.group_name }))
        }
    } catch (error) {
        console.log(error);
    }
}

async function re_getGroupNames() {
    emit('newGroup')
    await getGroupNames()
    person.group = groups.value[groups.value.length - 1]
}


const groups = ref([])

// date picker
const flow = ref<any>(['month', 'calendar']);

const format = (date: Date) => {
    const day = date.getDate();
    const month = fx.monthStr(date)
    return `${month} ${day}`;
}


const initForm: newMemberInterface = reactive({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    birthday: new Date(),
    gender: 'Male',
    group: { id: '0', label: 'none' },
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
    if (person.email == '') { showError('email'); return }
    if (person.phone == '') { showError('phone'); return }
    if (!(fx.emailIsOk(person.email))) { err.email = 'invalid email'; return }
    saveNewMember()
}

const btnX: any = ref(null)

const isSaving = ref<boolean>(false)
async function saveNewMember() {
    isSaving.value = true
    let obj = {
        firstname: person.firstname,
        lastname: person.lastname,
        email: person.email,
        phone: person.phone,
        birthday: fx.birthdayStr(person.birthday),
        group_id: (person.group == null) ? "0" : person.group.id,
        gender: (person.gender == 'Male') ? 'M' : 'F'
    }

    try {
        var { data } = await server.saveNewMember(obj)
        if (data == 0) {
            err.email = 'email already exists.'
            isSaving.value = false
        }
        else {
            Object.assign(person, initForm);
            isSaving.value = false
            emit('saved')
            askToContinue()
        }

    } catch (error) {
        console.log(error);
        isSaving.value = false
    }

}

function askToContinue() {
    Swal.fire({
        title: 'Added Successfully',
        text: "Do you want to add AGAIN?",
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#03787c',
        cancelButtonColor: '#767676',
        confirmButtonText: 'Continue adding'
    }).then((result) => {
        if (result.isConfirmed) {

        }
        else {
            btnX.value.click()
        }
    })
}

function replace(e) {
    let val = e.target.value
    person.phone = val.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');

}

</script>

<style scoped>
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

<!-- <style>
.bd-input .dp__button_bottom,
.bd-input .dp__month_year_select,
.bd-input .dp__inner_nav svg,
.bd-input .dp__calendar_header_item {
    display: none !important;
    border: none !important;

}
</style> -->