<template>
    <div class="modal fade" data-backdrop="static" id="newEventModal" tabindex="-1" aria-labelledby="newEventModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered ">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-white">New Event</h5>
                    <button ref="modalClose" type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form class="row g-3">
                        <div class="col-md-12 col-lg-6">
                            <label>Name:</label>
                            <input v-model="event.name" :class="{ 'formError': err.name }" type="text"
                                class="form-control">
                            <small class="text-center text-danger">{{ err.name }}</small>
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <label>Type:</label>
                            <v-select v-model="event.type" :clearable="false" :searchable="false" class="vSelect"
                                :options="['People', 'Opinion']" />
                        </div>
                        <div class="col-md-12 col-lg-12">
                            <label>Description (optional):</label>
                            <textarea placeholder="describe this event" v-model="event.desc" class="form-control"
                                rows="2"></textarea>
                        </div>

                        <div class="col-md-12 col-lg-6">
                            <label>Start:</label>
                            <Datepicker monthNameFormat="long" :previewFormat="format" :minDate="new Date()"
                                hideOffsetDates v-model="event.start" :is24="false" :clearable="false"
                                placeholder="birthday" autoApply />
                        </div>
                        <div class="col-md-12 col-lg-6">
                            <label>expiry:</label>
                            <Datepicker :class="{ 'formError': err.expiry }" monthNameFormat="long"
                                :previewFormat="format" :minDate="new Date()" hideOffsetDates v-model="event.expiry"
                                :is24="false" :clearable="false" placeholder="birthday" autoApply />
                            <small class="text-center text-danger">{{ err.expiry }}</small>
                        </div>

                        <div class="col-md-12 col-lg-12">
                            <button v-if="!event.is.saving" @click.prevent="checkForm" type="button"
                                class="btn customBtn w-100 mt-3">Save</button>
                            <button v-else disabled type="button" class="btn customBtn w-100 mt-3">saving..</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue';
import server from '@/store/apiStore'
import router from '@/router';

const event = reactive({
    name: '',
    type: 'People',
    desc: '',
    start: <any>new Date(),
    expiry: <any>new Date(),
    is: {
        saving: false
    },

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
    name: '',
    expiry: '',
})


const format = (date: Date) => {
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

function checkForm() {
    err.name = err.expiry = '';
    if (event.name == '') { err.name = 'fill in name'; return }

    let timediff = event.expiry - event.start
    if (timediff <= 0) { err.expiry = 'invalid event duration!'; return }
    saveEvent()
}

const modalClose = ref<any>(null)
async function saveEvent() {
    event.is.saving = true
    let obj = {
        event_name: event.name,
        event_description: event.desc,
        event_start: event.start,
        event_expiry: event.expiry,
        event_type: event.type
    }

    try {
        var { data } = await server.saveNewEvent(obj)
        if (data.state != 0) {
            event.is.saving = false
            modalClose.value.click()
            navigateToEvent(data.id)
        }
        else {
            event.is.saving = false
            err.name = 'already exists';
            return
        }
    } catch (error) {
        event.is.saving = false
        console.log(error);

    }
}

function navigateToEvent(id: any) {
    router.push({
        path: 'event',
        query: { org: id, e: event.name }
    })
}

</script>

<style scoped>
.formError {
    border: 1px solid var(--bs-danger);
}
</style>