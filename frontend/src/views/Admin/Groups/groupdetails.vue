<template>
    <div>
        <button @click="router.go(-1)" type="button" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i>
            Back</button>
    </div>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useAdminStore } from '@/store/user/admin'
import server from '@/store/apiStore'
import { onMounted, reactive } from 'vue';
const orgId = useAdminStore().getData.org_id
const route = useRoute()
const router = useRouter()


onMounted(() => {
    getGroupDetails()
})

async function getGroupDetails() {
    try {
        var { data } = await server.getGroupDetails(orgId, route.query.g);
        console.log(data);
    } catch (error) {
        console.log(error);
    }
}

</script>

<style scoped>
</style>