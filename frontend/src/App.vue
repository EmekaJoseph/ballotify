<script setup>
import { provide } from "vue";
import { storeToRefs } from 'pinia'
import { useColorStore } from '@/store/colorStore'
const cols = useColorStore()
const allColors = storeToRefs(cols)
const { cc1, ccBtnH, ccThk, cc2 } = allColors
provide("c$", allColors);
</script>
<template>
  <div class="siteBody">
    <router-view />
  </div>
</template>

<style>
.siteBody {
  padding-bottom: 0.001rem;
}


.customBtn {
  background-color: v-bind(cc1);
  color: #fff;
  border: none;
}

.customBtn:hover {
  background-color: v-bind(ccBtnH);
  color: #fff;
}

.text-custom {
  color: v-bind(cc1);
}

.card-header::after {
  background: v-bind(cc1);
}

.modal-header {
  border: none;
  color: #fff;
  background-color: v-bind(ccThk);
  /* font-weight: bold; */
  border-bottom: 2px solid v-bind(cc2) !important;
  padding: 24px !important;
}
</style>
