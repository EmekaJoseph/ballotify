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
                                <div class="col-sm-6">
                                    <label>group:</label>
                                    <v-select v-model="person.group" placeholder="none" class="vSelect"
                                        :options="['group1', 'group2']" />
                                </div>
                                <!-- <div class="col-sm-6 ">
                                    <button @click.prevent="checkForm" class="customBtn mt-4 p-2 w-100"
                                        style="border-radius: 5px"><i class="bi bi-save2"></i>&nbsp;
                                        Save</button>
                                </div> -->
                                <div>
                                    <span class="float-end">
                                        <button @click.prevent="checkForm" class="customBtn btn-sm"
                                            style="width: 100px"><i class="bi bi-save2"></i>&nbsp;
                                            Save</button>
                                    </span>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref } from 'vue';
const emit = defineEmits(["closeModal"]);
const date = ref()

const person = ref({
    firstname: '',
    lastname: '',
    email: '',
    dob: null,
    phone: '',
    gender: 'Male',
    group: 'none'
})
const flow = ref(['month', 'calendar']);
const format = (date) => {
    const day = date.getDate();
    // const month = date.getMonth() + 1;
    const month = monthStrings[date.getMonth()]
    return `${month} ${day}`;
}
const monthStrings = [
    'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'
]



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