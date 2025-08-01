import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        name: 'login',
        path: '/login',
        alias: '/',
        meta: { authentication: false, layout: 'login-layout' },
        component: () => import('../views/authentication/Login.vue'),
    },{
        name: 'registration',
        path: '/registration/:code?',
        meta: { authentication: false, layout: 'login-layout' },
        component: () => import('../views/authentication/Registration.vue'),
    },{
        name: 'forgot',
        path: '/forgot',
        meta: { authentication: false, layout: 'login-layout' },
        component: () => import('../views/authentication/Forgot.vue'),
    },{
        name: 'reset',
        path: '/reset/:token',
        meta: { authentication: false, layout: 'login-layout' },
        component: () => import('../views/authentication/Reset.vue'),
    },{
        name: 'mfa',
        path: '/mfa/:token',
        meta: { authentication: false, layout: 'login-layout' },
        component: () => import('../views/authentication/MFA.vue'),
    },{
        name: 'portal',
        path: '/portal',
        meta: { authentication: true },
        component: () => import('../views/Portal.vue'),
    },{
        name: 'user.form',
        path: '/user/:id',
        meta: { authentication: true, permissions: 'view-users|manage-users' },
        component: () => import('../views/users/Form.vue'),
    },{
        name: 'profile',
        path: '/profile',
        meta: { authentication: true },
        component: () => import('../views/users/Form.vue'),
    },{
        name: 'users',
        path: '/users',
        meta: { authentication: true, permissions: 'view-users|manage-users' },
        component: () => import('../views/users/Table.vue'),
    },{
        name: 'invites',
        path: '/invites',
        meta: { authentication: true, permissions: 'invite-users' },
        component: () => import('../views/users/invites/Table.vue'),
    },{
        name: 'roles',
        path: '/roles',
        meta: { authentication: true, permissions: 'manage-roles-permissions' },
        component: () => import('../views/roles/Table.vue'),
    },{
        name: 'permissions',
        path: '/permissions',
        meta: { authentication: true, permissions: 'manage-roles-permissions' },
        component: () => import('../views/permissions/Table.vue'),
    },{
        name: 'message',
        path: '/message',
        meta: { authentication: false, layout: 'login-layout' },
        component: () => import('../views/Message.vue'),
    }
];

export default createRouter({
    history: createWebHistory(),
    routes,
});