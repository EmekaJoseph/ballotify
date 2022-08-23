<template>
    <div class="card">
        <!-- <div class="card-head"><i class="bi bi-gear"></i> Modify</div> -->
        <div class="card-body">
            <div class="details">
                <!-- Tabs -->
                <ul class="nav nav-pills mb-3">
                    <li>
                        <a ref="posClick" class="nav-link active" data-bs-toggle="pill" href="#tab1">
                            <i class="bi bi-list-check"></i> Positions
                            <span class="badge rounded-pill bg-secondary text-white fw-light">
                                {{ positions.length }}
                            </span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link " data-bs-toggle="pill" href="#tab2">
                            <i class="bi bi-pencil"></i> Modify
                        </a>
                    </li>
                </ul>
                <!-- End Tabs -->

                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Start Tab 1 Content -->
                    <div class="tab-pane fade show active" id="tab1">
                        <div class="row justify-content-center  gy-3">
                            <form class="row gy-3">
                                <div class="col-12 col-xl-8">
                                    <input v-model="positionInput" type="text" placeholder="enter position..."
                                        class="form-control">
                                </div>
                                <div class="col-12 col-xl-4">
                                    <button type="submit" :disabled="!(positionInput.length > 0)"
                                        @click.prevent="savePosition" class="w-100 btn customBtn">Add <i
                                            class="bi text-white bi-arrow-down-circle fs-6"></i></button>
                                </div>
                            </form>
                            <div class="row mt-3">
                                <div class="col-md-12 col-lg-12">
                                    <div class="list-panel">
                                        <div v-if="!(positions.length > 0)">
                                            <div class="text-center empty-list-text mt-5 pt-5">
                                                List is Empty
                                            </div>
                                        </div>
                                        <!-- <ul v-else>
                                            <li style="list-style-type: circle;" class="mb-2 text-capitalize"
                                                v-for="i in positions" :key="i">
                                                {{ i.name }}
                                                <span @click="removePosition(i.id)" class="remove-btn">
                                                    <button class="btn btn-close btn-sm float-end"></button>
                                                </span>
                                            </li>
                                        </ul> -->
                                        <div v-else class="table-responsive">
                                            <table class="table table-sm text-nowrap text-capitalize">
                                                <tbody>
                                                    <tr v-for="i in positions" :key="i">
                                                        <td>{{ i.name }}</td>
                                                        <td>
                                                            <span @click="removePosition(i.id)" class="remove-btn">
                                                                <button class="btn btn-close btn-sm float-end"></button>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Tab 1 Content -->


                    <!-- Start Tab 2 Content -->
                    <div class="tab-pane fade show" id="tab2">
                        <form class="row g-3">
                            <div class="col-md-12 col-lg-12">
                                <label>Name:</label>
                                <input v-model="event.name" :class="{ 'formError': inputErr.name }" type="text"
                                    class="form-control">
                                <small class="text-center text-danger">{{ inputErr.name }}</small>
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
                                <Datepicker :class="{ 'formError': inputErr.expiry }" monthNameFormat="long"
                                    :previewFormat="format" :minDate="new Date()" hideOffsetDates v-model="event.end"
                                    :is24="false" :clearable="false" placeholder="birthday" autoApply />
                                <small class="text-center text-danger">{{ inputErr.expiry }}</small>
                            </div>

                            <div class="col-md-12 col-lg-12">
                                <button v-if="!isLoading" @click.prevent="" type="button"
                                    class="btn customBtn w-100 mt-3">Save</button>
                                <button v-else disabled type="button" class="btn customBtn w-100 mt-3">saving..</button>
                            </div>
                        </form>

                    </div>
                    <!-- End Tab 2 Content -->
                </div>

            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, inject } from 'vue';
import server from '@/store/apiStore'
import { eventStore } from '../event-data';
import { storeToRefs } from 'pinia';
import { useRoute } from 'vue-router';

const event_ = eventStore()
const { event, positions }: any = storeToRefs(event_)

const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");

const route = useRoute()

// ###################### positions ########################################

const positionInput = ref('')
const posClick: any = ref(null)




async function removePosition(id: any) {
    try {
        await server.removePosition(id)
        event_.getPositions()
    } catch (error) {
        console.log(error);

    }
}

async function savePosition() {
    let obj = {
        'event_id': route.query.id,
        'name': positionInput.value,
    }

    try {
        var { data } = await server.saveNewPosition(obj)
        positionInput.value = ''
        if (data != 0) {
            event_.getPositions()
        }
    } catch (error) {
        console.log(error);

    }
}


// ###################### positions ########################################









// ###################### details ########################################

const format = (date: Date) => {
    const day = date.getDate();
    const month = date.getMonth() + 1;
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

const inputErr = reactive({
    name: '',
    expiry: ''

})
const isLoading = ref(false)
// ###################### details ########################################
</script>

<style scoped>
.card-head {
    padding: 15px;
    background-color: v-bind(ccBg);
    color: #fff;
    /* background-color: #f8f8f88e; */
}


.list-panel {
    background-color: #eeeeee2f;
    padding: 20px 10px;
    border: 1px solid #eee;
    height: 300px;
    overflow-y: auto;
}


.remove-btn {
    float: right;
    margin-right: 15px;
    cursor: pointer;
    padding-inline: 5px;
    border-radius: 5px;
}

.remove-btn:hover {
    background-color: rgb(250, 212, 212);
}

.empty-list-text {
    color: #c7c4c4;
    font-size: 20px;
}


.details .nav-pills {
    border-bottom: 1px solid rgba(var(--bs-secondary-rgb), 0.2);
}

.details .nav-pills li+li {
    margin-left: 40px;
}

.details .nav-link {
    background: none;
    font-size: 14px;
    font-weight: 400;
    color: var(--bs-secondary);
    padding: 12px 0;
    margin-bottom: -2px;
    border-radius: 0;
    font-family: var(--bs-secondary);
}

.details .nav-link.active {
    color: v-bind(cc1);
    background: none;
    border-bottom: 3px solid v-bind(cc1);
}

@media (max-width: 575px) {
    .details .nav-link {
        font-size: 16px;
    }
}

.details .tab-content h4 {
    font-size: 18px;
    margin: 0;
    font-weight: 700;
    color: var(--bs-secondary);
}

.details .tab-content i {
    font-size: 22px;
    line-height: 0;
    margin-right: 8px;
    color: v-bind(cc1);
}
</style>