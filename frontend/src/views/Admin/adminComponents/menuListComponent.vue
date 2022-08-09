<template>
    <div>
        <div class="menuSection">
            <ul class="list-group list-group-flush">
                <li class="list-group-item" :class="{ 'gActive': (route.name == 'Dashboard') }"
                    :data-bs-dismiss="showOn">
                    <router-link :to="{ name: 'Dashboard' }">
                        <i class="bi bi-layout-wtf"></i>&nbsp;
                        Dashboard
                    </router-link>
                </li>
                <li class="list-group-item" :class="{ 'gActive': (route.name == 'Members') }" :data-bs-dismiss="showOn">
                    <router-link :to="{ name: 'Members' }">
                        <i class="bi bi-people"></i>&nbsp;
                        Members
                    </router-link>
                </li>
                <li class="list-group-item"
                    :class="{ 'gActive': (route.name == 'Groups') || (route.name == 'Grp(details)') }"
                    :data-bs-dismiss="showOn">
                    <router-link :to="{ name: 'Groups' }">
                        <i class="bi bi-folder"></i>&nbsp;
                        Groups
                    </router-link>
                </li>
                <li class="accordion" id="votingsDropdown">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingTwo"
                            :class="{ 'gActive': (route.name == 'Event') }">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#flush-votings" aria-expanded="false" aria-controls="flush-votings">
                                <i class="bi bi-list-check"></i>&nbsp; Votings
                            </button>
                        </h2>
                        <div id="flush-votings" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo"
                            data-bs-parent="#votingsDropdown">
                            <div class="accordion-body">
                                <ul class="list-group list-group-flush">
                                    <li v-for="list in events" :key="list" class="dropItem small">
                                        <a :data-bs-dismiss="showOn"
                                            :class="{ 'focused': route.query.id == list.event_id }" href="/"
                                            @click.prevent="navigateToEvent(list.event_id, list.event_name)">
                                            <i class="bi bi-calendar4-event"></i>&nbsp; {{ list.event_name }}
                                            <i v-if="route.query.id == list.event_id"
                                                class="bi bi-circle-fill indicator ms-2"></i>
                                        </a>
                                    </li>
                                    <li class="dropItem">
                                        <a class="add-new" data-bs-toggle="modal" data-bs-target="#newEventModal"
                                            href="#newEvent">
                                            <button :data-bs-dismiss="showOn"><i
                                                    class="bi bi-plus-square-fill"></i>&nbsp; create</button>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="list-group-item" :class="{ 'gActive': (route.name == 'Messages') }"
                    :data-bs-dismiss="showOn">
                    <router-link :to="{ name: 'Messages' }">
                        <i class="bi bi-envelope-paper"></i>&nbsp;
                        Messages
                    </router-link>
                </li>

                <li class="list-group-item" :class="{ 'gActive': (route.name == 'Settings') }"
                    :data-bs-dismiss="showOn">
                    <router-link :to="{ name: 'Settings' }">
                        <i class="bi bi-sliders"></i>&nbsp; Settings
                    </router-link>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup lang="ts">
import { inject, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router';
import { dataStore } from '@/store/admin/dataStore';
import { storeToRefs } from 'pinia'


const route = useRoute()
const router = useRouter()
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");

defineProps({
    showOn: {
        type: String,
        default: "",
        required: false,
    }
})

const { events }: any = storeToRefs(dataStore())

function navigateToEvent(id: any, name: string) {
    router.push({
        path: 'event',
        query: { id: id, e: name }
    })
}

</script>

<style scoped>
.menuSection {
    margin-top: 30px;
}

@media screen and (max-width: 767px) {
    .menuSection {
        margin-top: 20px;
    }
}

.list-group-item {
    background-color: transparent;
    color: #fff;
    margin-bottom: 10px;
    border: none;
}

.list-group-item a {
    text-decoration: none;
    padding: 10px 40px 10px 10px;
}

.list-group-item button {
    text-decoration: none;
    background: none;
    border: none;
    color: v-bind(cc2);
    margin-left: 5px;
}

/* .list-group-item button i {
    color: v-bind(cc2);
} */


/* a:hover i {
    color: #111;
} */



a:not(.gActive):hover {
    color: #111;
}

.active {
    color: v-bind(cc1);
}

.gActive {
    background-color: v-bind(ccBg);
    border-right: 2px solid v-bind(cc1);
}

.accordion-item {
    background-color: transparent;
    border: none;
}

.accordion-button {
    background-color: transparent;
    font-size: 16px;
    padding: 10px 40px 10px 25px;
    margin-bottom: 10px;
}

.accordion-button:hover {
    color: #111;
}

.accordion-button:not(.collapsed) {
    color: v-bind(cc1);
    box-shadow: none
}

.accordion-button.collapsed::after {
    background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232c3e50'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-button:not(.collapsed)::after {
    background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232c3e50'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-body {
    border: none;
    padding: 0px;
    margin: 0px;
}

.dropItem {
    margin-bottom: 12px;
}

.dropItem a {
    text-decoration: none;
    margin-left: 35px;
}

.dropItem a:hover {
    color: #111;
}

.focused {
    color: v-bind(cc1) !important;
}

.add-new button {
    color: v-bind(cc1) !important;
    border: none;
    margin: 0px;
    background: transparent;
    padding: 0px;
}

.indicator {
    font-size: 7px;
}
</style>