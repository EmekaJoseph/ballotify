<script setup>
import { provide } from "vue";
import { storeToRefs } from 'pinia'
import { useColorStore } from '@/store/colorStore.js'
const cols = useColorStore()
const { cc1, ccBtnH } = cols
provide("c$", storeToRefs(cols));
</script>
<template>
  <div class="bodySection">
    <router-view />
  </div>
</template>

<style scoped>
.bodySection {
  padding-bottom: 0.001rem;
}
</style>
