<script setup>
import { provide } from "vue";
import { storeToRefs } from 'pinia'
import { useColorStore } from '@/store/colorStore'
const cols = useColorStore()
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
