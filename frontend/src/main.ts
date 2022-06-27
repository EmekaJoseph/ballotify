import { createApp } from 'vue'
import { createPinia } from "pinia";
import App from './App.vue'
import './registerServiceWorker'
import router from './router'

import 'bootstrap-icons/font/bootstrap-icons.css'
import 'bootstrap/dist/css/bootstrap.min.css'
import 'bootstrap/dist/js/bootstrap.min.js'

import './assets/css/root.css'
const mainApp = createApp(App)

// npm install vue-writer
import VueWriter from "vue-writer";

import HeaderComponent from '@/components/HeaderComponent.vue'
mainApp.component('HeaderComponent', HeaderComponent);

import FooterComponent from '@/components/FooterComponent.vue'
mainApp.component('FooterComponent', FooterComponent);

import notify from "@/components/toastNotify.vue";
mainApp.component('notify', notify);

//npm install @vuepic/vue-datepicker
import '@vuepic/vue-datepicker/dist/main.css';
import Datepicker from '@vuepic/vue-datepicker';
mainApp.component('Datepicker', Datepicker);

//npm install vue-select-3
import vSelect from "vue-select";
import "vue-select/dist/vue-select.css";
mainApp.component('vSelect', vSelect);

// https://vcalendar.io/vue-3.html
// npm install v-calendar@next
import VCalendar from 'v-calendar';
import 'v-calendar/dist/style.css';


// npm install -S vue-sweetalert2
import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';


mainApp.use(VueSweetalert2);
mainApp.use(router)
mainApp.use(createPinia())
mainApp.use(VueWriter)
mainApp.use(VCalendar, {})
mainApp.mount('#app')
