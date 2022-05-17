import { createApp } from 'vue'
import { createPinia } from "pinia";
import App from './App.vue'
import './registerServiceWorker'
import router from './router'

import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.min.js'

import './assets/css/root.css'

//npm install @vuepic/vue-datepicker
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

//npm install vue-select-3
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";


const mainApp = createApp(App)

// npm install vue-writer
import VueWriter from "vue-writer";

import HeaderComponent from '@/components/HeaderComponent.vue'
mainApp.component('HeaderComponent', HeaderComponent);

import FooterComponent from '@/components/FooterComponent.vue'
mainApp.component('FooterComponent', FooterComponent);

mainApp.component('Datepicker', Datepicker);

mainApp.component('vSelect', vSelect);


mainApp.use(router)
mainApp.use(createPinia())
mainApp.use(VueWriter)
mainApp.mount('#app')
