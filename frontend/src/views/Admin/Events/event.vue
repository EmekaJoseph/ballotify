<template>
    <div>
        <span class="large-name">{{ route.query.e }}</span>
        <span>
            <ul>
                <li><b>created:</b> {{ event.data.created }}</li>
                <li><b>Start:</b> {{ (new Date(event.data.event_start)).toLocaleString() }}</li>
                <li><b>Expiry:</b> {{ (new Date(event.data.event_expiry)).toLocaleString() }}</li>
            </ul>
        </span>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, watchEffect, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import server from '@/store/apiStore'

const route = useRoute()

const event = reactive({
    data: <any>''
})

watchEffect(async () => {
    let event_id = route.query.id;
    if (event_id != undefined) {
        try {
            var { data } = await server.getEventDetails(event_id)
            console.log(data);

            event.data = data

        } catch (error) {
            console.log(error);
        }
    }
})
</script>

<style scoped>
.large-name {
    display: flex;
    justify-content: center;
    font-weight: 900;
    font-size: 1.7rem;
    text-transform: uppercase;
    background-color: #fff;
    text-align: center;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}

@media (max-width: 991px) {
    .large-name {
        font-size: 0.8rem;

    }
}
</style>