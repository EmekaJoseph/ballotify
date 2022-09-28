<template>
    <div class="generalBody page-not-home">
        <HeaderComponentVotingVue :scrolled="y" />
        <!-- ======= Contact Section ======= -->
        <section class="main">
            <transition name="xSlide">
                <div v-if="pageIsLoading" class="page-loader">
                    <div class="loading"></div>
                </div>

                <div v-else class="container">
                    <div class="name-span">
                        <span class="large-name ms-3">Name Of event </span>
                    </div>
                </div>
            </transition>
        </section>
        <!-- End Contact Section -->
        <FooterComponent />
    </div>
</template>

<script setup>
import { onMounted, ref, inject } from 'vue';
import { useWindowScroll } from '@vueuse/core';
import HeaderComponentVotingVue from '@/components/HeaderComponentVoting.vue';
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");
const { x, y } = useWindowScroll()
onMounted(() => {
    window.scrollTo(0, 0);
    setTimeout(() => {
        pageIsLoading.value = false
    }, 500);
})

const pageIsLoading = ref(true)
</script>

<style scoped>
.name-span {
    display: flex;
    justify-content: center;
    background-color: v-bind(ccBg);
    padding: 10px;
    margin-bottom: 20px;
    border-bottom: 1px solid v-bind(cc1);
}

.large-name {
    font-weight: 900;
    font-size: 1.7rem;
    color: v-bind(cc1);
    text-transform: uppercase;
    text-align: center;
}

@media (max-width: 991px) {
    .large-name {
        font-size: 0.8rem;

    }

    .dropdown-toggle .bi {
        font-size: 0.8rem;
    }
}



.loading:before {
    content: "";
    display: inline-block;
    border-radius: 50%;
    width: 100px;
    height: 100px;
    margin: 0 10px -6px 0;
    border: 3px solid v-bind(cc1);
    border-top-color: var(--bs-white);
    -webkit-animation: animate-loading 1s linear infinite;
    animation: animate-loading 1s linear infinite;
}

@-webkit-keyframes animate-loading {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}

@keyframes animate-loading {
    0% {
        transform: rotate(0deg);
    }

    100% {
        transform: rotate(360deg);
    }
}
</style>