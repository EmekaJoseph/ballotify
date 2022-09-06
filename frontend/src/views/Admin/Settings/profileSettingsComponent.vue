<template>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">My Profile:
                <span class="float-end">
                    <div v-if="user.isLoading" class="c-loader loaderBlack"></div>
                </span>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-sm-12">
                        <label>Email:</label>
                        <div>{{  user.email  }}</div>
                        <!-- <input v-model="user.email" type="text" class="form-control w-100" disabled> -->
                    </div>
                    <div class="col-sm-6">
                        <label>Firstname:</label>
                        <div v-if="!user.keyIn">{{  user.firstname  }}</div>
                        <input v-else v-model="user.firstname" type="text" class="form-control w-100">
                    </div>

                    <div class="col-sm-6">
                        <label>Lastname:</label>
                        <div v-if="!user.keyIn">{{  user.lastname  }}</div>
                        <input v-else v-model="user.lastname" type="text" class="form-control w-100">
                    </div>
                    <!-- <div class="col-sm-6">
                        <label>Phone:</label>
                        <div>----</div>
                    </div> -->

                    <div>
                        <span class="float-end">
                            <button @click="user.keyIn = true" v-if="!user.keyIn && !user.isLoading"
                                class="btn btn-link text-success text-decoration-none me-2" style="width: 100px"><i
                                    class="bi bi-pencil"></i> edit</button>
                            <button :disabled="user.isLoading" @click="cancelKeyIn" v-if="user.keyIn"
                                class="btn btn-secondary btn-sm me-2" style="width: 80px">Cancel</button>
                            <button :disabled="user.isLoading" @click="updateUser" v-if="user.keyIn"
                                class="customBtn btn-sm" style="width: 100px">Update</button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { adminAccount } from '@/store/admin/account'
import server from '@/store/apiStore'
import { onMounted, reactive, ref } from 'vue';
const admin = adminAccount()
const user_id = admin.getData.user_id
const user = reactive({
    firstname: '',
    lastname: '',
    email: '',
    role: '',
    firstname_bk: '',
    lastname_bk: '',
    keyIn: false,
    isLoading: true
})



onMounted(() => {
    getUserDetails()
})

async function getUserDetails() {
    try {
        var { data } = await server.getUserDetails(user_id);
        user.firstname = user.firstname_bk = data.data.firstname
        user.lastname = user.lastname_bk = data.data.lastname
        user.email = data.data.email
        user.role = data.data.role
        user.keyIn = false
        user.isLoading = false
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
    user.isLoading = true
    let obj = {
        id: user_id,
        firstname: user.firstname,
        lastname: user.lastname
    }
    try {
        var { data } = await server.updateUser(obj);
        if (data == 1) {
            await getUserDetails()
            let fullName = user.firstname + ' ' + user.lastname
            admin.setNames(fullName)
        }
    } catch (error) {
        user.isLoading = false
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