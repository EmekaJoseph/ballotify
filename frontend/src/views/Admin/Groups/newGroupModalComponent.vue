<template>
    <div>
        <div class="modal fade show" data-backdrop="static" data-keyboard="false" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header"> New Group
                        <span class="float-end">
                            <button @click="emit('closeModal')" class="btn btn-close btn-close-white"></button>
                        </span>
                    </div>
                    <div class="modal-body p-sm-4">
                        <form>
                            <div class="row gy-3">
                                <div class="col-md-12">
                                    <label>Group Name:</label>
                                    <input v-model="group.name" type="text" class="form-control w-100">
                                    <small v-if="group.error" class="text-danger">{{ group.error }}</small>
                                </div>

                                <div class="col-md-12">
                                    <button v-if="!group.isSaving" @click.prevent="checkForm"
                                        class="customBtn w-100 btn-lg">
                                        Save</button>
                                    <button v-else class="customBtn w-100 btn-lg" disabled>
                                        Saving...</button>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
const emit = defineEmits(["closeModal", "added"]);


const orgId = useAdminStore().getData.org_id

const group = reactive({
    name: '',
    error: '',
    isSaving: false
})
function checkForm() {
    group.error = ''
    if (!group.name || group.name.length < 3) {
        group.error = 'not less than 3 characters!'
        return
    }
    saveName()
}

async function saveName() {
    group.isSaving = true
    let obj = {
        group_name: group.name,
        org_id: orgId,
        created: new Date()
    }
    try {
        var { data } = await server.saveNewGroup(obj)
        if (data == 1) {
            group.isSaving = false
            emit('added')
            emit('closeModal')

        }
        else {
            group.isSaving = false
            group.error = 'name already exists'
            return
        }
    } catch (error) {
        console.log(error);
        group.isSaving = false
    }
}
</script>

<style>
.modal {
    display: block;
    background-color: #11111144;
}
</style>