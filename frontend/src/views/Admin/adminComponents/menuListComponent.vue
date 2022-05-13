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


                <!-- <li class="accordion" id="settingsDropdown">
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
                                    <li class="list-group-item">
                                        <router-link to="/">
                                            <i class="bi bi-people"></i>&nbsp; Users
                                        </router-link>
                                    </li>
                                    <li class="list-group-item">
                                        <router-link to="/">
                                            <i class="bi bi-person-lines-fill"></i>&nbsp; Account
                                        </router-link>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </li>-->
            </ul>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'
import { useRoute } from 'vue-router';
// const cols = inject("customColors");
// const { color1, color2, colorActive, color3 } = cols

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

/* .list-group-item:hover {
    border-right: 2px solid #00bd8b;
} */


/* .list-group-item :is(a, i) {
    color: #19FFC2;
} */


a:hover i {
    color: #111;
}

a:hover {
    color: #111;
}

.active {
    /* font-weight: bold; */
    color: #00bd8b;
}


.gActive {
    background-color: #00bd8b0e;
    border-right: 2px solid #00bd8b;
}

.gActive i {
    color: #00bd8b;
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
    color: #fff;
    font-weight: lighter;
    font-size: 16px;
    padding: 10px 40px 10px 25px;
    margin-top: 16px;
    border: none;
}

.accordion-button:hover {
    color: red;
}

.accordion-button.collapsed::after {
    background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-button:not(.collapsed)::after {
    background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23fff'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
}

.accordion-body {
    padding: 2px;
    margin-left: 35px;
    border: 0px;
}
</style>