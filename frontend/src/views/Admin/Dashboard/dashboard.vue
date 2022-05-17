<template>
    <div>
        dashboard
        <!-- <v-select :searchable="false" :options="options"></v-select> -->

    </div>
</template>

<script setup lang="ts">
import { onMounted, ref, inject } from 'vue'
import apiCall from '@/store/apiStore.js'
const { cc1, cc2, ccThk, ccBg, ccBtnH }: any = inject("c$");


const date = ref()
const options = ref(['rmmm', 'hhhh'])
// onMounted(() => {
//     const startDate = new Date();
//     const endDate = new Date(new Date().setDate(startDate.getDate() + 7));
//     date.value = [startDate, endDate];
// })


// async function checkApi() {
//     try {
//         var { data } = await apiCall.testApi("test-string");
//         str.value = data
//         console.log(data);
//     } catch (error) {
//         console.log(error)
//     }
// }
</script>

<style scoped>
</style>