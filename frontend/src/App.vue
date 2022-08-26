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
  background-color: v-bind(cc1) !important;
  color: #fff !important;
  border: none !important;
}

.customBtn:hover {
  background-color: v-bind(ccBtnH) !important;
  color: #fff !important;
}

.customBtnLine {
  background-color: transparent !important;
  color: v-bind(cc1) !important;
  border: 1px solid v-bind(cc1) !important;
}

.customBtnLine:hover {
  background-color: v-bind(ccBtnH) !important;
  color: #fff !important;
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

.btn-link {
  color: v-bind(cc1) !important;
  text-decoration: none !important;
}

/* .btn-link:hover { */
/* background-color: #eee; */
/* font-weight: bold; */
/* } */
</style>
