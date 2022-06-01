<template>
    <div class="row">
        <List @openModal="openModal = true" :data="memberList" />
        <transition name="ySlide" mode="out-in">
            <newMemberModal v-if="openModal" @closeModal="openModal = false" />
        </transition>
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import List from './membersTableComponent.vue'
import newMemberModal from './newMemberModalComponent.vue'

const openModal = ref(false)
const memberList = ref([])
</script>

<style scoped>
</style>