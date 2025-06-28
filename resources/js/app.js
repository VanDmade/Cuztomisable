import './bootstrap';
import router from './router';
import { createApp } from 'vue';
import store from './store';
import Index from './Index.vue';
// Global components
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
import Modal from './components/Formora/Modal.vue';
import Message from './components/Formora/Message.vue';
// Creates the application to start mounting global components
var app = createApp(Index).use(router);
app.mixin({
    data: function() {
        return {
            width: window.innerWidth,
            yesOrNo: [{ value: '1', text: 'Yes'}, { value: '0', text: 'No'}],
        }
    },
    created: function() {
        window.addEventListener('resize', this.onResize);
    },
    destroyed: function() {
        window.removeEventListener('resize', this.onResize);
    },
    methods: {
        breakpoint: function(type) {
            if (type == 'lg' && this.width >= 992) {
                return true;
            } else if (type == 'md' && this.width >= 768) {
                return true;
            } else if (type == 'sm' && this.width < 768) {
                return true;
            }
            return false;
        },
        onResize: function() {
            this.width = window.innerWidth;
        },
        clone: function(value) {
            return JSON.parse(JSON.stringify(value));
        },
        usernameLabel: function() {
            try {
                var loginWith = this.$cuztomisable.login_with ?? [];
                return loginWith.phone ?
                    (loginWith.email ? 'Email Address or Phone Number' : 'Phone Number') :
                    (loginWith.email ? 'Email Address' : 'Username');
            } catch (error) {
                return 'Email Address';
            }
        },
        formatPhone: function(value, code = '') {
            if (code == '' & value.indexOf('+') !== false) {
                value = value.split(' ');
                code = value[0].replace('+', '');
                value = value[1];
            }
            let format = '(XXX) XXX-XXXX';
            if (!value) {
                return value;
            }
            const digits = ('' + value).replace(/\D/g, '');
            let formatted = '';
            let digitIndex = 0;
            for (let char of format) {
                if (char === 'X') {
                    if (digitIndex < digits.length) {
                        formatted += digits[digitIndex++];
                    } else {
                        break;
                    }
                } else {
                    formatted += char;
                }
            }
            return (code == '' ? '' : ('+'+code+' '))+formatted;
        },
        formatDate: function(input, type='en-US', options = {}) {
            if (!input) return '';
            const normalized = input.replace(' ', 'T').replace('Z', '') + 'Z';
            const date = new Date(normalized);
            if (isNaN(date.getTime())) return input;
            const hasTime = /\d{2}:\d{2}/.test(input);
            const defaultOptions = hasTime
                ? { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', timeZoneName: 'short' }
                : { year: 'numeric', month: 'short', day: 'numeric' };
            const formatter = new Intl.DateTimeFormat(type, { ...defaultOptions, ...options });
            return formatter.format(date);
        },
        cleanFormData: function(formData) {
            const cleanedFormData = new FormData();
            for (const [key, value] of formData.entries()) {
                const cleanedValue = (value.toLowerCase() == 'null' || value == null) ? '' : value;
                cleanedFormData.append(key, cleanedValue);
            }
            return cleanedFormData;
        },
        goBack: function() {
            window.history.length > 1 ? window.history.back() : this.$router.push('/');
        },
    },
});
axios.defaults.withCredentials = true;
axios.defaults.baseURL = import.meta.env.VITE_API_URL;
app.config.globalProperties.$url = import.meta.env.VITE_URL;
// Gets the settings from the configuration file for cuztomisable
var response = await axios.get('/cuztomisable/settings');
store.$cuztomisable = app.config.globalProperties.$cuztomisable = response.data ?? null;
app.config.globalProperties.$timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
app.use(store);
// Global component attachments
app.component('index', Index);
app.component('tablelify', Tablelify);
app.component('fm-loading', Loading);
app.component('fm-form', Form);
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
app.component('fm-image', Image);
app.mount('#app');