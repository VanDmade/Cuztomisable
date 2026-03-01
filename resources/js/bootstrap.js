import * as axiosModule from 'axios';

const axios = axiosModule.default ?? axiosModule;
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.baseURL = import.meta.env.VITE_API_URL ?? '/api';
