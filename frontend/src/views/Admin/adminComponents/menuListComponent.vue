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
                <li class="list-group-item" :class="{ 'gActive': (route.name == 'Groups') }" :data-bs-dismiss="showOn">
                    <router-link :to="{ name: 'Groups' }">
                        <i class="bi bi-folder-plus"></i>&nbsp;
                        Groups
                    </router-link>
                </li>
                <li class="accordion" id="votingsDropdown">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#flush-votings" aria-expanded="false" aria-controls="flush-votings">
                                <i class="bi bi-ui-checks"></i>&nbsp; Votings
                            </button>
                        </h2>
                        <div id="flush-votings" class="accordion-collapse collapse" aria-labelledby="flush-headingTwo"
                            data-bs-parent="#votingsDropdown">
                            <div class="accordion-body">
                                <ul class="list-group list-group-flush">
                                    <li class="dropItem">
                                        <router-link to="/" @click.prevent>
                                            <i class="bi bi-check2-circle"></i>&nbsp; event 1
                                        </router-link>
                                    </li>
                                    <li class="dropItem">
                                        <router-link to="/" @click.prevent>
                                            <i class="bi bi-check2-circle"></i>&nbsp; event 2
                                        </router-link>
                                    </li>
                                    <li class="dropItem">
                                        <router-link to="/" @click.prevent>
                                            <i class="bi bi-plus-circle-dotted"></i>&nbsp; new
                                        </router-link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
                <li class="list-group-item" :class="{ 'gActive': (route.name == 'Messages') }"
                    :data-bs-dismiss="showOn">
                    <router-link :to="{ name: 'Messages' }">
                        <i class="bi bi-app-indicator"></i>&nbsp;
                        Messages
                    </router-link>
                </li>

                <li class="accordion" id="settingsDropdown">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#flush-settings" aria-expanded="false" aria-controls="flush-settings">
                                <i class="bi bi-gear"></i>&nbsp; Settings
                            </button>
                        </h2>
                        <div id="flush-settings" class="accordion-collapse collapse" aria-labelledby="flush-headingOne"
                            data-bs-parent="#settingsDropdown">
                            <div class="accordion-body">
                                <ul class="list-group list-group-flush">
                                    <li class="dropItem">
                                        <router-link to="/" @click.prevent>
                                            <i class="bi bi-person-plus"></i>&nbsp; users
                                        </router-link>
                                    </li>
                                    <li class="dropItem">
                                        <router-link to="/" @click.prevent>
                                            <i class="bi bi-person-circle"></i>&nbsp; profile
                                        </router-link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'
import { useRoute } from 'vue-router';
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");

defineProps({
    showOn: {
        type: String,
        default: "",
        required: false,
    }
})
const route = useRoute()

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

a:hover i {
    color: #111;
}

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

.gActive i {
    color: v-bind(cc1);
}

.myaccordion {
    border: none;
}

.accordion-item {
    background-color: transparent;
    border: none;
}

.accordion-button {
    background-color: transparent;
    font-size: 16px;
    padding: 10px 40px 10px 25px;
    border: none;
    margin-bottom: 10px;
}

.accordion-button:hover {
    color: #111;
}

.accordion-button:not(.collapsed) {
    color: v-bind(cc1);

}

.accordion-button.collapsed::after {
    /* margin-left: 30px; */
    background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232c3e50'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-button:not(.collapsed)::after {
    /* margin-left: 30px; */
    background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232c3e50'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-body {
    border: none;
    padding: 0px;
    margin: 0px;
}

.dropItem {
    margin-bottom: 18px;
}

.dropItem a {
    text-decoration: none;
    margin-left: 50px;
}

.dropItem a:hover {
    color: #111;
}
</style>