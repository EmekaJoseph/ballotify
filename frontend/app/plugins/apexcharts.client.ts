// @ts-nocheck
import VueApexCharts from "vue3-apexcharts";

export default defineNuxtPlugin((nuxtApp) => {
    if (process.client) {
        nuxtApp.vueApp.use(VueApexCharts);
        // Sometimes the plugin registration is delayed, so we register it explicitly too
        nuxtApp.vueApp.component('apexchart', VueApexCharts);
        nuxtApp.vueApp.component('ApexChart', VueApexCharts);
    }
});
