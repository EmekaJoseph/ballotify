<template>
    <div class="modal fade" data-backdrop="static" id="newEventModal" tabindex="-1" aria-labelledby="newEventModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white">New Event</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="row g-3">
                        <div class="col-md-12 col-lg-6">
                            <label>Name:</label>
                            <input v-model="event.name" :class="{ 'formError': err.name }" type="text"
                                class="form-control">
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <label>Type:</label>
                            <v-select v-model="event.type" :clearable="false" :searchable="false" class="vSelect"
                                :options="['People', 'Opinion']" />
                        </div>
                        <div class="col-md-12 col-lg-12">
                            <label>Description:</label>
                            <textarea :class="{ 'formError': err.desc }" v-model="event.desc" class="form-control"
                                rows="2"></textarea>
                        </div>

                        <div class="col-md-12 col-lg-6">
                            <label>Start:</label>
                            <Datepicker :class="{ 'formError': err.start }" monthNameFormat="long"
                                :previewFormat="format" :minDate="new Date()" hideOffsetDates v-model="event.start"
                                :is24="false" :clearable="false" placeholder="birthday" autoApply />
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <label>expiry:</label>
                            <Datepicker :class="{ 'formError': err.expiry }" monthNameFormat="long"
                                :previewFormat="format" :minDate="new Date()" hideOffsetDates v-model="event.expiry"
                                :is24="false" :clearable="false" placeholder="birthday" autoApply />
                        </div>
                        <small class="text-center text-danger">{{ err.dateDiffTxt }}</small>
                        <div class="col-md-12 col-lg-12">
                            <button @click.prevent="checkForm" type="button"
                                class="btn customBtn w-100 mt-3">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, inject, reactive, computed } from 'vue';
import useFunc from '@/store/useFunction'
const event = reactive({
    name: '',
    type: 'People',
    desc: '',
    start: <any>new Date(),
    expiry: <any>new Date(),

    days: computed(() => {
        var seconds = Math.floor((event.expiry - event.start) / 1000);
        var minutes = Math.floor(seconds / 60);
        var hours = Math.floor(minutes / 60);
        var days = Math.floor(hours / 24);

        hours = hours - (days * 24);
        minutes = minutes - (days * 24 * 60) - (hours * 60);
        seconds = seconds - (days * 24 * 60 * 60) - (hours * 60 * 60) - (minutes * 60);
        return `${days} days, ${hours} hours, ${minutes}`

    })
})

const err = reactive({
    name: false,
    desc: false,
    start: false,
    expiry: false,
    dateDiffTxt: '',
})


const format = (date: Date) => {
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function checkForm() {
    err.desc = err.name = err.start = err.expiry = false;
    err.dateDiffTxt = '';
    if (event.name == '') { err.name = true; return }
    if (event.desc == '') { err.desc = true; return }

    let timediff = event.expiry - event.start
    if (timediff <= 0) { err.dateDiffTxt = 'Check duration!'; return }

    console.log(event);
    console.log(timediff);
}

</script>

<style scoped>
.formError {
    border: 1px solid var(--bs-danger);
}
</style>