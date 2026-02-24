<template>
    <div class="col-xl-7">
        <div class="fw-bold mb-2">Recently Activities:</div>
        <div class="card" style="min-height: 300px">
            <div v-if="data.length" class="card-body">
                <div class="table-responsive list-span">
                    <table class="table table-sm text-nowrap">
                        <thead>
                            <tr>
                                <th></th>
                                <th>&nbsp;</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, index) in data" :key="index">
                                <td><i class="bi bi-dot text-secondary"></i></td>
                                <td class="fst-italic small">{{ val.activity }}</td>
                                <td class="small"><small class="fst-italic activity-date">{{ val.date }}</small></td>

                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- <div v-if="data.length > 4" class="my-3 text-center">
                    <router-link class="" :to="{ name: 'Members' }">
                        see all...
                    </router-link>
                </div> -->
            </div>
            <div v-else class="card-body ">
                <div class="row justify-content-center my-4">
                    <span class="text-center"><i class="bi bi-reception-2" style="font-size: 8rem"></i></span>
                    <small class="text-center text-muted">No Recent Activities.</small> <br>
                </div>


            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    data: {
        type: Array,
        default: []
    }
})
</script>

<style scoped>
a:hover {
    color: green;
    font-weight: bold;
}

.bi {
    color: #ece8e8
}

.activity-date {
    color: var(--bs-teal);
}
</style>