import './bootstrap';
import '../sass/app.scss';
import '../sass/formora.scss';
import '../sass/tablelify.scss';
import * as axiosModule from 'axios';
import { createApp, h, reactive, defineComponent, Fragment } from 'vue';
import { createInertiaApp, Link, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import store from './store';
import Tablelify from './components/Tablelify.vue';
import Loading from './components/Formora/Loading.vue';
import Upload from './components/Formora/Upload.vue';
import Address from './components/Formora/Address.vue';
import Delete from './components/Formora/Delete.vue';
import Carousel from './components/Formora/Carousel.vue';
import Phone from './components/Formora/Phone.vue';
import Input from './components/Formora/Input.vue';
import Radio from './components/Formora/Radio.vue';
import Multi from './components/Formora/Multi.vue';
import Image from './components/Formora/Image.vue';
import Select from './components/Formora/Select.vue';
import Checkbox from './components/Formora/Checkbox.vue';
import Textarea from './components/Formora/Textarea.vue';
import Tags from './components/Formora/Tags.vue';
import Form from './components/Formora/Form.vue';
import Autofill from './components/Formora/Autofill.vue';
import Modal from './components/Formora/Modal.vue';
import Message from './components/Formora/Message.vue';
import notify from './utils/notify';
import loading from './utils/loading';
import LoginLayout from './views/layouts/LoginLayout.vue';
import PortalLayout from './views/layouts/PortalLayout.vue';

const Axios = axiosModule.default ?? axiosModule;

const LOGIN_LAYOUT_PAGES = new Set([
    'authentication/Login',
    'authentication/Registration',
    'authentication/Forgot',
    'authentication/Reset',
    'authentication/MFA',
    'Message',
]);

const AUTH_GUEST_ROUTE_NAMES = new Set([
    'login',
    'registration',
    'forgot',
    'reset',
    'mfa',
]);

const ROUTES = {
    login: '/',
    registration: '/registration/:code?',
    forgot: '/forgot',
    reset: '/reset/:token',
    mfa: '/mfa/:token',
    portal: '/portal',
    'user.form': '/user/:id',
    profile: '/profile',
    users: '/users',
    invites: '/invites',
    roles: '/roles',
    permissions: '/permissions',
    message: '/message',
};

const ROUTE_META = {
    login: { authentication: false, layout: 'login-layout' },
    registration: { authentication: false, layout: 'login-layout' },
    forgot: { authentication: false, layout: 'login-layout' },
    reset: { authentication: false, layout: 'login-layout' },
    mfa: { authentication: false, layout: 'login-layout' },
    portal: { authentication: true },
    'user.form': { authentication: true, permissions: 'view-users|manage-users' },
    profile: { authentication: true },
    users: { authentication: true, permissions: 'view-users|manage-users' },
    invites: { authentication: true, permissions: 'invite-users' },
    roles: { authentication: true, permissions: 'manage-roles-permissions' },
    permissions: { authentication: true, permissions: 'manage-roles-permissions' },
    message: { authentication: false, layout: 'login-layout' },
};

function normalizePath(pathname = '/') {
    if (!pathname) return '/';
    if (pathname.length > 1 && pathname.endsWith('/')) {
        return pathname.slice(0, -1);
    }
    return pathname;
}

function normalizeBaseUrl(url = '/') {
    if (!url) {
        return '/';
    }
    return url.endsWith('/') ? url : `${url}/`;
}

function appendQuery(path, query = {}) {
    const params = new URLSearchParams();
    Object.entries(query ?? {}).forEach(([key, value]) => {
        if (value === null || typeof value === 'undefined' || value === '') {
            return;
        }
        params.set(key, String(value));
    });
    const queryString = params.toString();
    return queryString ? `${path}?${queryString}` : path;
}

function buildPath(name, params = {}) {
    switch (name) {
        case 'login': return '/';
        case 'registration': return params.code ? `/registration/${params.code}` : '/registration';
        case 'forgot': return '/forgot';
        case 'reset': return `/reset/${params.token ?? ''}`;
        case 'mfa': return `/mfa/${params.token ?? ''}`;
        case 'portal': return '/portal';
        case 'user.form': return `/user/${params.id ?? ''}`;
        case 'profile': return '/profile';
        case 'users': return '/users';
        case 'invites': return '/invites';
        case 'roles': return '/roles';
        case 'permissions': return '/permissions';
        case 'message': return '/message';
        default: return ROUTES[name] ?? '/';
    }
}

function resolveToHref(to) {
    if (typeof to === 'string') {
        return to;
    }
    if (to?.path) {
        return appendQuery(to.path, to.query);
    }
    if (to?.name) {
        return appendQuery(buildPath(to.name, to.params ?? {}), to.query);
    }
    return '/';
}

function parseRouteFromUrl(url) {
    const parsed = new URL(url, window.location.origin);
    const path = normalizePath(parsed.pathname);
    const query = Object.fromEntries(parsed.searchParams.entries());
    const route = {
        name: null,
        path,
        params: {},
        query,
        meta: {},
    };

    if (path === '/' || path === '/login') {
        route.name = 'login';
    } else if (path.startsWith('/registration')) {
        route.name = 'registration';
        const code = path.split('/')[2];
        if (code) route.params.code = code;
    } else if (path === '/forgot') {
        route.name = 'forgot';
    } else if (path.startsWith('/reset/')) {
        route.name = 'reset';
        route.params.token = path.split('/')[2] ?? null;
    } else if (path.startsWith('/mfa/')) {
        route.name = 'mfa';
        route.params.token = path.split('/')[2] ?? null;
    } else if (path === '/portal') {
        route.name = 'portal';
    } else if (path.startsWith('/user/')) {
        route.name = 'user.form';
        route.params.id = path.split('/')[2] ?? null;
    } else if (path === '/profile') {
        route.name = 'profile';
    } else if (path === '/users') {
        route.name = 'users';
    } else if (path === '/invites') {
        route.name = 'invites';
    } else if (path === '/roles') {
        route.name = 'roles';
    } else if (path === '/permissions') {
        route.name = 'permissions';
    } else if (path === '/message') {
        route.name = 'message';
    }

    route.meta = route.name ? (ROUTE_META[route.name] ?? {}) : {};
    return route;
}

function visit(to, replace = false) {
    const href = resolveToHref(to);
    return router.visit(href, { replace });
}

const routeState = reactive(parseRouteFromUrl(window.location.pathname + window.location.search));

let routeTransitionLoading = false;

router.on('start', (event) => {
    const currentRoute = parseRouteFromUrl(window.location.pathname + window.location.search);
    const targetUrl = event?.detail?.visit?.url ?? currentRoute.path;
    const nextRoute = parseRouteFromUrl(targetUrl);

    const isPortalToPortal = !!currentRoute?.meta?.authentication && !!nextRoute?.meta?.authentication;
    if (!isPortalToPortal) {
        routeTransitionLoading = false;
        return;
    }

    routeTransitionLoading = true;
    store.commit('SET_LOADING', true);
});

router.on('finish', () => {
    if (!routeTransitionLoading) {
        return;
    }

    routeTransitionLoading = false;
    setTimeout(() => {
        store.commit('SET_LOADING', false);
    }, 120);
});

router.on('navigate', (event) => {
    Object.assign(routeState, parseRouteFromUrl(event.detail.page.url));
});

const RouterLink = defineComponent({
    name: 'RouterLinkCompat',
    props: {
        to: {
            type: [String, Object],
            required: true,
        },
    },
    setup(props, { slots, attrs }) {
        return () => h(
            Link,
            { ...attrs, href: resolveToHref(props.to) },
            {
                default: () => (slots.default ? slots.default() : []),
            },
        );
    },
});

const axios = Axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
});
export async function loadCuztomisableApp() {
    let settings = {};

    try {
        console.log(axios.defaults.baseURL);
        const res = await axios.get('/cuztomisable/settings');
        settings = res.data ?? {};
    } catch (err) {
        console.warn('[Cuztomisable] Failed to load settings:', err);
    }

    await createInertiaApp({
        resolve: async (name) => {
            const page = await resolvePageComponent(
                `./views/${name}.vue`,
                import.meta.glob('./views/**/*.vue'),
            );

            if (!page.default.layout) {
                page.default.layout = LOGIN_LAYOUT_PAGES.has(name) ? LoginLayout : PortalLayout;
            }

            return page;
        },
        async setup({ el, App, props, plugin }) {
            Object.assign(routeState, parseRouteFromUrl(props.initialPage.url));

            const app = createApp({
                render: () => h(Fragment, [
                    h(Message),
                    h(App, props),
                ]),
            });

            app.use(plugin);
            app.use(store);

    const globalComponents = {
        tablelify: Tablelify,
        'fm-loading': Loading,
        'fm-form': Form,
        'fm-carousel': Carousel,
        'fm-input': Input,
        'fm-address': Address,
        'fm-phone': Phone,
        'fm-radio': Radio,
        'fm-multi': Multi,
        'fm-checkbox': Checkbox,
        'fm-upload': Upload,
        'fm-select': Select,
        'fm-tags': Tags,
        'fm-textarea': Textarea,
        'fm-message': Message,
        'fm-modal': Modal,
        'fm-image': Image,
        'fm-autofill': Autofill,
        'fm-delete': Delete,
    };
    Object.entries(globalComponents).forEach(([name, comp]) => {
        app.component(name, comp);
    });

    app.component('router-link', RouterLink);

    Object.defineProperty(app.config.globalProperties, '$route', {
        get() {
            return routeState;
        },
    });

    app.config.globalProperties.$router = {
        push(to) {
            return visit(to, false);
        },
        replace(to) {
            return visit(to, true);
        },
        back() {
            if (window.history.length > 1) {
                window.history.back();
                return;
            }
            visit('/');
        },
    };

    app.config.globalProperties.$notify = notify;
    app.config.globalProperties.$message = notify;
    app.provide('notify', notify);
    app.provide('message', notify);
    app.config.globalProperties.$loading = loading;
    app.provide('loading', loading);

    store.$cuztomisable = app.config.globalProperties.$cuztomisable = settings;
    app.config.globalProperties.$url = normalizeBaseUrl(import.meta.env.VITE_URL);
    app.config.globalProperties.$timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    app.mixin({
        data() {
            return { width: window.innerWidth, yesOrNo: [{ value: '1', text: 'Yes'}, { value: '0', text: 'No'}] };
        },
        created() { window.addEventListener('resize', this.onResize); },
        unmounted() { window.removeEventListener('resize', this.onResize); },
        methods: {
            copyToClipboard: function(text) {
                navigator.clipboard.writeText(text).then(() => {
                    console.log('Copied to clipboard:', text)
                }).catch(err => {
                    console.error('Failed to copy:', err)
                });
            },
            breakpoint(type) {
                return (type === 'lg' && this.width >= 992)
                    || (type === 'md' && this.width >= 768)
                    || (type === 'sm' && this.width < 768);
            },
            onResize() { this.width = window.innerWidth; },
            clone(value) { return JSON.parse(JSON.stringify(value)); },
            usernameLabel() {
                try {
                    const loginWith = this.$cuztomisable?.login_with ?? [];
                    return loginWith.phone
                        ? (loginWith.email ? 'Email Address or Phone Number' : 'Phone Number')
                        : (loginWith.email ? 'Email Address' : 'Username');
                } catch { return 'Email Address'; }
            },
            formatPhone(value, code = '') {
                if (code === '' && value?.includes('+')) {
                    const parts = value.split(' ');
                    code = parts[0].replace('+', '');
                    value = parts[1];
                }
                const format = '(XXX) XXX-XXXX';
                if (!value) return value;
                const digits = value.replace(/\D/g, '');
                let formatted = '', i = 0;
                for (const c of format) {
                    formatted += c === 'X' ? (digits[i++] ?? '') : c;
                    if (i >= digits.length) break;
                }
                return (code ? `+${code} ` : '') + formatted;
            },
            formatDate(input, type = 'en-US', options = {}) {
                if (!input) return '';
                const normalized = input.replace(' ', 'T').replace('Z', '') + 'Z';
                const date = new Date(normalized);
                if (isNaN(date.getTime())) return input;
                const hasTime = /\d{2}:\d{2}/.test(input);
                const defaults = hasTime
                    ? { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', timeZoneName: 'short' }
                    : { year: 'numeric', month: 'short', day: 'numeric' };
                return new Intl.DateTimeFormat(type, { ...defaults, ...options }).format(date);
            },
            appendIndexedValues(fd, list, field) {
                let i = 0;
                for (const [key, val] of Object.entries(list)) {
                    if (val !== false) fd.append(`${field}[${i++}]`, key);
                }
                return fd;
            },
            cleanFormData(fd) {
                const cleaned = new FormData();
                for (const [k, v] of fd.entries()) {
                    const val = (typeof v === 'string' && v.toLowerCase() === 'null') || v == null ? '' : v;
                    cleaned.append(k, val);
                }
                return cleaned;
            },
            goBack() { window.history.length > 1 ? window.history.back() : visit('/'); },
        },
    });

    await store.dispatch('checkAuth');

    if (store.state.authenticated && AUTH_GUEST_ROUTE_NAMES.has(routeState.name)) {
        await visit('/portal', true);
    }

    app.mount(el);
    return { app, store };
        },
    });
}

loadCuztomisableApp();