<template>
    <div class="col-lg-12 col-xl-5">
        <div class="card" style="border: 2px solid #eee;">
            <div class="card-header">Settings</div>
            <div class="card-body">
                <div class="row g-5">
                    <div class="col-md-12">
                        <div class="p-lg-4">
                            <form>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label>Edit name</label>
                                        <input @input="watchInput" v-model="name" type="text"
                                            class="form-control form-control-lg">
                                        <small v-if="nameErr" class="text-danger">{{ nameErr }}</small>
                                    </div>
                                    <div class="col-md-12">
                                        <button @click.prevent="sendName"
                                            class="btn btn-outline-secondary btn-lg  w-100"
                                            :disabled="!isInput">Change</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="shadow-sm p-2 p-lg-3 settCard">
                            <form>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <button @click.prevent="deleteGroup"
                                            class="btn-lg btn btn-link text-danger w-100">
                                            <i class="bi bi-folder-x"></i> Delete
                                            Group</button>
                                        <!-- <div class="small text-center"><b>NB:</b> can not be undone!
                                            </div> -->
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import Swal from 'sweetalert2'
import { ref } from 'vue';

interface Props {
    name: string,
    name_bk: string
}

const prop = withDefaults(defineProps<Props>(), {
    name: 'name',
    name_bk: 'name'
})



const isInput = ref<boolean>(false)
function watchInput() {
    if ((prop.name.toLowerCase() == prop.name_bk.toLowerCase())
        || !prop.name.length) isInput.value = false
    else isInput.value = true

}

const nameErr = ref('')
const emit = defineEmits(['delete', 'rename'])

function sendName() {
    nameErr.value = ''
    if (prop.name) {
        if (prop.name.length < 3) {
            nameErr.value = 'not less than 3 characters'
            return false
        }
        else {
            emit('rename', prop.name)
        }
    }
}

function deleteGroup() {
    Swal.fire({
        title: 'Confirm Delete?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'cancel',
        confirmButtonColor: '#d33',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            emit('delete')
        }
    })
}
</script>

<style scoped>
.settCard {
    border: 3px solid #eee;
}
</style>