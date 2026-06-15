import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from './router'
import { bindRouter } from './api'
import App from './App.vue'
import './style.css'

bindRouter(router)

createApp(App).use(createPinia()).use(router).mount('#app')
