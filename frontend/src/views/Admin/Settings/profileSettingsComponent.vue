<template>
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">Profile:
                <span class="float-end">
                    <div v-if="user.isSaving" class="c-loader loaderBlack"></div>
                </span>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-sm-12">
                        <label>Email:</label>
                        <div>{{ user.email }}</div>
                        <!-- <input v-model="user.email" type="text" class="form-control w-100" disabled> -->
                    </div>
                    <div class="col-sm-6">
                        <label>Firstname:</label>
                        <div v-if="!user.keyIn">{{ user.firstname }}</div>
                        <input v-else v-model="user.firstname" type="text" class="form-control w-100">
                    </div>

                    <div class="col-sm-6">
                        <label>Lastname:</label>
                        <div v-if="!user.keyIn">{{ user.lastname }}</div>
                        <input v-else v-model="user.lastname" type="text" class="form-control w-100">
                    </div>
                    <!-- <div class="col-sm-6">
                        <label>Phone:</label>
                        <div>----</div>
                    </div> -->

                    <div>
                        <span class="float-end">
                            <button @click="user.keyIn = true" v-if="!user.keyIn"
                                class="btn btn-link text-success text-decoration-none me-2" style="width: 100px"><i
                                    class="bi bi-pencil text-success"></i> edit</button>
                            <button :disabled="user.isSaving" @click="cancelKeyIn" v-if="user.keyIn"
                                class="btn btn-secondary btn-sm me-2" style="width: 80px">Cancel</button>
                            <button :disabled="user.isSaving" @click="updateUser" v-if="user.keyIn"
                                class="btn customBtn btn-sm" style="width: 100px">Update</button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
import { onMounted, reactive, ref } from 'vue';
const admin = useAdminStore()
const orgId = admin.getData.org_id
const id = admin.getData.id
const user = reactive({
    firstname: '',
    lastname: '',
    email: '',
    role: '',
    firstname_bk: '',
    lastname_bk: '',
    keyIn: false,
    isSaving: false
})



onMounted(() => {
    getUserDetails()
})

async function getUserDetails() {
    try {
        var { data } = await server.getUserDetails(id, orgId);
        user.firstname = user.firstname_bk = data.data.firstname
        user.lastname = user.lastname_bk = data.data.lastname
        user.email = data.data.email
        user.role = data.data.role
        user.keyIn = false
        user.isSaving = false
    } catch (error) {
        console.log(error);
    }
}

function cancelKeyIn() {
    user.keyIn = false;
    user.firstname = user.firstname_bk
    user.lastname = user.lastname_bk
}

async function updateUser() {
    user.isSaving = true
    let obj = {
        id: id,
        firstname: user.firstname,
        lastname: user.lastname
    }
    try {
        var { data } = await server.updateUser(obj);
        if (data == 1) {
            await getUserDetails()
            let newValues = {
                id: id,
                org_id: orgId,
                name: user.firstname + ' ' + user.lastname,
                role: user.role
            }
            admin.setData(newValues)
        }
    } catch (error) {
        user.isSaving = false
        console.log(error);
    }
}
</script>

<style scoped>
.org-span {
    font-weight: bold;
    /* padding: 5px 10px; */
    color: #BD2c00;
}
</style>