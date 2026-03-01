import { createStore } from 'vuex';
import * as axiosModule from 'axios';

const axios = axiosModule.default ?? axiosModule;

axios.defaults.withCredentials = true;
let refreshTimeout = null;

export default createStore({
    state: function() {
        return {
            user: null,
            permissions: [],
            change_password: false,
            authenticated: false,
            ready: false,
            loading: false,
        };
    },
    getters: {
        user: function(state) {
            return state.user;
        },
        permissions: function(state) {
            return state.permissions;
        },
        hasPermission: (state) => (slug) => {
            if (!slug) return false;
            if (slug.includes('|')) {
                // OR logic
                return slug.split('|').some(p => state.permissions.includes(p.trim()));
            }
            if (slug.includes('&')) {
                // AND logic
                return slug.split('&').every(p => state.permissions.includes(p.trim()));
            }
            return state.permissions.includes(slug);
        },
        changePassword: function(state) {
            return state.change_password;
        },
        authenticated: function(state) {
            return state.authenticated;
        },
        ready: function(state) {
            return state.ready;
        },
        isLoading: function(state) {
            return state.loading;
        },
    },
    mutations: {
        SET_USER: function(state, user) {
            state.user = user;
            state.authenticated = !!user;
        },
        CLEAR_USER: function(state) {
            state.user = null;
            state.permissions = [];
            state.change_password = false;
            state.authenticated = false;
        },
        SET_READY: function(state, ready) {
            state.ready = ready;
        },
        SET_CHANGE_PASSWORD: function(state, change) {
            state.change_password = change;
        },
        SET_PERMISSIONS: function(state, permissions) {
            state.permissions = permissions;
        },
        SET_LOADING: function(state, value) {
            state.loading = value;
        },
    },
    actions: {
        async checkAuth({ commit, dispatch }) {
            commit('SET_LOADING', true);
            try {
                const response = await axios.get('/me');
                const payload = response?.data?.data ?? response?.data ?? {};
                commit('SET_USER', payload.user ?? null);
                commit('SET_PERMISSIONS', payload.permissions ?? []);
                commit('SET_CHANGE_PASSWORD', !!payload.change_password);
                dispatch('performTokenRefresh');
            } catch (error) {
                commit('CLEAR_USER');
            } finally {
                commit('SET_READY', true);
                setTimeout(() => {
                    commit('SET_LOADING', false);
                }, 500);
            }
        },
        async login({ commit, dispatch }, credentials) {
            await axios.get('/sanctum/csrf-cookie');
            let response = await axios.post('/login', credentials);
            const payload = response?.data?.data ?? response?.data ?? {};
            if (payload.multi_factor_authentication !== true) {
                commit('SET_USER', payload.user ?? null);
                commit('SET_PERMISSIONS', payload.permissions ?? []);
                commit('SET_CHANGE_PASSWORD', !!payload.change_password);
                dispatch('startTokenRefresh');
            }
            return response;
        },
        async logout({ commit, dispatch }) {
            try {
                commit('SET_LOADING', true);
                await axios.post('/logout');
            } catch (e) {
                // silently fail if already logged out
            } finally {
                dispatch('clearTokenRefresh');
                commit('CLEAR_USER');
                setTimeout(() => {
                    commit('SET_LOADING', false);
                }, 1500);
            }
        },
        startTokenRefresh: function({ dispatch }) {
            let sessionLength = this.$cuztomisable?.session_length ?? 600;
            if (refreshTimeout) {
                dispatch('clearTokenRefresh');
                clearTimeout(refreshTimeout);
            }
            const refreshDelay = Math.max((sessionLength - 30) * 1000, 60000);
            refreshTimeout = setTimeout(() => {
                dispatch('performTokenRefresh');
            }, refreshDelay);
        },
        async performTokenRefresh({ dispatch }) {
            try {
                const res = await axios.get('/refresh', { withCredentials: true });
                const expiresAt = new Date(res.data.token_expires_at);
                const now = new Date();
                const nextDelay = Math.max(expiresAt - now - 60000, 60000);
                refreshTimeout = setTimeout(() => {
                    dispatch('performTokenRefresh');
                }, nextDelay);
            } catch (error) {
                if (error?.response?.status === 401) {
                    dispatch('logout');
                }
            }
        },
        clearTokenRefresh: function() {
            if (refreshTimeout) {
                clearTimeout(refreshTimeout);
                refreshTimeout = null;
            }
        }
    },
});