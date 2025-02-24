import { createRouter, createWebHistory } from 'vue-router';
import Home from './components/Home.vue';
import Documents from './components/Documents.vue';
import Upload from './components/Upload.vue'; // Import Upload.vue

const routes = [
    { path: '/', component: Home, name: 'home' },
    { path: '/documents', component: Documents, name: 'documents' },
    { path: '/upload', component: Upload, name: 'upload' }, // Add Upload page
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

export default router;
