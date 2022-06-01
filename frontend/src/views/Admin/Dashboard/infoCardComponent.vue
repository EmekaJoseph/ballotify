<template>
    <div class="col-xl-3 col-sm-6">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-4 sticker"><i class="bi" :class="props.icon"></i></div>
                    <div class="col-8">
                        <div class="digit">{{ props.rate }}</div>
                        <div>{{ props.name }}</div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { inject } from 'vue'
const { cc1, cc2, ccThk, ccBg, ccBtnH } = inject("c$");

const props = defineProps({
    name: String,
    rate: Number,
    icon: String,
    color: String
})

const color = props.color
const bgColor = props.color + '09'


</script>

<style scoped>
.sticker {
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 2rem;
    color: v-bind(color);
}

.bi {
    background-color: v-bind(bgColor);
    padding: 10px 17px;
    border-radius: 20%;
}

.digit {
    font-size: 1.6rem;
    /* font-weight: bold; */
}

.card {
    border-right: 2px solid v-bind(color);
}
</style>