import './bootstrap';
import router from './router';
import { createApp } from 'vue';
import store from './store';
import Index from './Index.vue';
// Global components
import Tablelify from './components/Tablelify.vue';
import Loading from './components/Loading.vue';
import Upload from './components/Formora/Upload.vue';
import Address from './components/Formora/Address.vue';
import Phone from './components/Formora/Phone.vue';
import Input from './components/Formora/Input.vue';
import Radio from './components/Formora/Radio.vue';
import Multi from './components/Formora/Multi.vue';
import Select from './components/Formora/Select.vue';
import Checkbox from './components/Formora/Checkbox.vue';
import Textarea from './components/Formora/Textarea.vue';
import Tags from './components/Formora/Tags.vue';
import Modal from './components/Formora/Modal.vue';
import Message from './components/Message.vue';
// Creates the application to start mounting global components
var app = createApp(Index).use(router);
app.mixin({
    data: function() {
        return {
            width: window.innerWidth,
            yesOrNo: [{ value: '1', text: 'Yes'}, { value: '0', text: 'No'}],
            measurements: [
                { value: 'none', text: 'None' },
                { value: 'tsp', text: 'tsp' },
                { value: 'tbsp', text: 'tbsp' },
                { value: 'cup', text: 'cup(s)' },
                { value: 'pint', text: 'pint(s)' },
                { value: 'quart', text: 'quart(s)' },
                { value: 'gallon', text: 'gallon(s)' },
                { value: 'floz', text: 'fl oz' },
                { value: 'liter', text: 'liter(s)' },
                { value: 'ml', text: 'ml' },
                { value: 'oz', text: 'oz' },
                { value: 'lb', text: 'lb' },
                { value: 'g', text: 'g' },
                { value: 'kg', text: 'kg' },
            ],
        }
    },
    created: function() {
        window.addEventListener('resize', this.onResize);
    },
    destroyed: function() {
        window.removeEventListener('resize', this.onResize);
    },
    methods: {
        getAuthenticationToken: function() {
            return localStorage.getItem('token') ?? null;
        },
        breakpoint: function(type) {
            if (type == 'md' && this.width >= 768) {
                return true;
            } else if (type == 'sm' && this.width < 768) {
                return true;
            }
            return false;
        },
        onResize: function() {
            this.width = window.innerWidth;
        },
    },
});
// Adds the token to the axios requests IF SET
axios.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token') ?? null;
        if (token) {
            config.headers['Authorization'] = 'Bearer ' + token;
        }
        return config;
    }, (error) => {
        return Promise.reject(error);
    }
);
app.use(store);
// Global component attachments
app.component('index', Index);
app.component('tablelify', Tablelify);
app.component('loading', Loading);
app.component('fm-input', Input);
app.component('fm-address', Address);
app.component('fm-phone', Phone);
app.component('fm-radio', Radio);
app.component('fm-multi', Multi);
app.component('fm-checkbox', Checkbox);
app.component('fm-upload', Upload);
app.component('fm-select', Select);
app.component('fm-tags', Tags);
app.component('fm-textarea', Textarea);
app.component('fm-message', Message);
app.component('fm-modal', Modal);
// Gets the settings from the configuration file for cuztomisable
var response = await axios.get('/cuztomisable/settings');
app.config.globalProperties.$cuztomisable = response.data ?? null;
app.config.globalProperties.$url = 'http://localhost:8000/';
app.mount('#app');