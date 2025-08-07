import './bootstrap';
import Axios from 'axios';
import router from './routers/cuztomisable';
import { createApp } from 'vue';
import store from './store';
import Index from './Index.vue';
import Tablelify from './components/Tablelify.vue';
import Loading from './components/Formora/Loading.vue';
import Upload from './components/Formora/Upload.vue';
import Address from './components/Formora/Address.vue';
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
const axios = Axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    withCredentials: true,
});
export async function loadCuztomisableApp(mountId = '#cuztomisable-app') {
    const app = createApp(Index);
    const globalComponents = {
        index: Index,
        tablelify: Tablelify,
        'fm-loading': Loading,
        'fm-form': Form,
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
    };
    Object.entries(globalComponents).forEach(([name, comp]) => {
        app.component(name, comp);
    });
    app.mixin({
        data() {
            return { width: window.innerWidth, yesOrNo: [{ value: '1', text: 'Yes'}, { value: '0', text: 'No'}] };
        },
        created() { window.addEventListener('resize', this.onResize); },
        unmounted() { window.removeEventListener('resize', this.onResize); },
        methods: {
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
            goBack() { window.history.length > 1 ? window.history.back() : this.$router.push('/'); },
        },
    });
    let settings = {};
    try {
        const res = await axios.get('/cuztomisable/settings');
        settings = res.data ?? {};
    } catch (err) {
        console.warn('[Cuztomisable] Failed to load settings:', err);
    }
    store.$cuztomisable = app.config.globalProperties.$cuztomisable = settings;
    app.config.globalProperties.$url = import.meta.env.VITE_URL;
    app.config.globalProperties.$timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    app.use(store);
    await store.dispatch('checkAuth');
    router.beforeEach((to, from, next) => {
        const requiresAuth = to.meta.authentication === true;
        const requiredPerm = to.meta.permissions;
        if (requiresAuth && !store.state.authenticated) return next({ name: 'login' });
        if (requiredPerm && !store.getters.hasPermission(requiredPerm))
            return next({ name: 'message', query: { m: store.$cuztomisable.unauthorized_note } });
        next();
    });
    return { app, router, store };
}