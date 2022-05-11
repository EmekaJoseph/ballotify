import { createApp } from 'vue'
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


mainApp.use(router)
mainApp.use(VueWriter)
mainApp.mount('#app')
