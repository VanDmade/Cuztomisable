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
        name: 'user.form',
        path: '/user/:id',
        meta: { require_authentication: true },
        component: () => import("./views/users/Form.vue"),
    },{
        name: 'profile',
        path: '/profile',
        meta: { require_authentication: true },
        component: () => import("./views/users/Form.vue"),
    },{
        name: 'users',
        path: '/users',
        meta: { require_authentication: true },
        component: () => import("./views/users/Table.vue"),
    },{
        name: 'invites',
        path: '/invites',
        meta: { require_authentication: true },
        component: () => import("./views/users/invites/Table.vue"),
    },{
        name: 'roles',
        path: '/roles',
        meta: { require_authentication: true },
        component: () => import("./views/roles/Table.vue"),
    },{
        name: 'permissions',
        path: '/permissions',
        meta: { require_authentication: true },
        component: () => import("./views/permissions/Table.vue"),
    },{
        name: 'message',
        path: '/message',
        meta: { require_authentication: false, layout: 'login-layout' },
        component: () => import("./views/Message.vue"),
    }
];

export default createRouter({
    history: createWebHistory(),
    routes,
});