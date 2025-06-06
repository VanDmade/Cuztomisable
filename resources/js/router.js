import { createRouter, createWebHistory } from "vue-router";

const routes = [
    {
        name: 'login',
        path: '/login',
        alias: '/',
        meta: { require_authentication: false, layout: 'login-layout' },
        component: () => import("./views/authentication/Login.vue"),
    },{
        name: 'registration',
        path: '/registration/:code?',
        meta: { require_authentication: false, layout: 'login-layout' },
        component: () => import("./views/authentication/Registration.vue"),
    },{
        name: 'forgot',
        path: '/forgot',
        meta: { require_authentication: false, layout: 'login-layout' },
        component: () => import("./views/authentication/Forgot.vue"),
    },{
        name: 'reset',
        path: '/reset/:token',
        meta: { require_authentication: false, layout: 'login-layout' },
        component: () => import("./views/authentication/Reset.vue"),
    },{
        name: 'mfa',
        path: '/mfa/:token',
        meta: { require_authentication: false, layout: 'login-layout' },
        component: () => import("./views/authentication/MFA.vue"),
    },{
        name: 'portal',
        path: '/portal',
        meta: { require_authentication: true },
        component: () => import("./views/Portal.vue"),
    },{
        name: 'profile',
        path: '/user/:id?',
        alias: '/profile',
        meta: { require_authentication: true },
        component: () => import("./views/users/Form.vue"),
    },{
        name: 'users',
        path: '/users',
        meta: { require_authentication: true },
        component: () => import("./views/users/Table.vue"),
    },{
        name: 'message',
        path: '/message',
        meta: { require_authentication: false },
        component: () => import("./views/Message.vue"),
    }
];

export default createRouter({
    history: createWebHistory(),
    routes,
});