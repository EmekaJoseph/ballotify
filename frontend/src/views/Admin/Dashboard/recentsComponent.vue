<template>
    <div class="col-xl-8">
        <div class="fw-bold mb-2">Recently Added:</div>
        <div class="card" style="min-height: 300px">
            <div v-if="data.length" class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Satus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(val, index) in data" :key="val.name">
                                <th>{{ index + 1 }}</th>
                                <td>{{ val.name }}</td>
                                <td>{{ val.email }}</td>
                                <td>{{ val.phone }}</td>
                                <td v-if="val.status == 1"><span class="text-success"><i
                                            class="bi bi-check-circle-fill text-success"></i> verified</span></td>
                                <td v-else><span class="text-warning"><i
                                            class="bi text-warning bi-exclamation-circle-fill"></i>
                                        pending</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="data.length > 4" class="my-3 text-center">
                    <router-link class="" :to="{ name: 'Members' }">
                        see all...
                    </router-link>
                </div>
            </div>
            <div v-else class="card-body ">
                <div class="row justify-content-center my-4">
                    <span class="text-center"><i class="bi bi-person-x" style="font-size: 8rem"></i></span>
                    <small class="text-center text-muted">You do not have any members yet.</small> <br>
                    <small class="text-center">
                        <router-link class="" :to="{ name: 'Members' }">
                            add new members..
                        </router-link>
                    </small>
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
</style>