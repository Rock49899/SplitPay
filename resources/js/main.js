import '../assets/main.css'
// Import Swiper styles
import 'swiper/css'
import 'swiper/css/navigation'
import 'swiper/css/pagination'
import 'jsvectormap/dist/jsvectormap.css'
import 'flatpickr/dist/flatpickr.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import { createPinia } from 'pinia'
import { applyToken } from './services/api'

const pinia = createPinia()

const app = createApp(App)
app.use(router)
app.use(pinia)

// re-apply token to axios if present (optionnel: store already does this)
const token = localStorage.getItem('api_token')
if (token) applyToken(token)

app.mount('#app')
