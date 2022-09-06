<template>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Organisation:
                <span class="float-end">
                    <div v-if="org.isLoading" class="c-loader loaderBlack"></div>
                </span>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-12">
                        <label>Name:</label>
                        <div>
                            {{  org.name  }} &nbsp;
                            <span class="fw-bold text-custom">{{  org.org_id  }}</span>
                        </div>
                    </div>

                    <div class="col-12">
                        <label>Address:</label>
                        <div v-if="!org.keyIn">{{  org.address  }}</div>
                        <textarea v-else v-model="org.address" class="form-control" rows="2"></textarea>
                    </div>
                    <div>
                        <span class="float-end">
                            <button @click="org.keyIn = true" v-if="!org.keyIn && !org.isLoading"
                                class="btn btn-link text-decoration-none me-2 text-success" style="width: 100px"><i
                                    class="bi bi-pencil"></i> edit</button>
                            <button :disabled="org.isLoading" @click="cancelKeyIn" v-if="org.keyIn"
                                class="btn btn-secondary btn-sm me-2" style="width: 80px">Cancel</button>
                            <button :disabled="org.isLoading" @click="updateOrg" v-if="org.keyIn"
                                class="btn-sm customBtn" style="width: 100px">Update</button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import server from '@/store/apiStore'
import { onMounted, reactive } from 'vue';
const org = reactive({
    id: '',
    org_id: '',
    name: '',
    address: '',
    name_bk: '',
    address_bk: '',
    keyIn: false,
    isLoading: true
})

onMounted(() => {
    getOrgDetails()
})

async function getOrgDetails() {
    try {
        var { data } = await server.getOrgDetails();
        org.name = org.name_bk = data.org_name
        org.address = org.address_bk = data.org_address
        org.id = data.id
        org.org_id = data.org_id
        org.keyIn = false;
        org.isLoading = false
    } catch (error) {
        console.log(error);
    }
}

function cancelKeyIn() {
    org.keyIn = false;
    org.name = org.name_bk
    org.address = org.address_bk
}

async function updateOrg() {
    org.isLoading = true
    let obj = {
        id: org.id,
        org_name: org.name,
        org_address: org.address
    }
    try {
        var { data } = await server.updateOrg(obj);
        if (data == 1) {
            getOrgDetails()
        }
    } catch (error) {
        org.isLoading = false
        console.log(error);
    }
}
</script>
