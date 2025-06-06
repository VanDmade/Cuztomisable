import { createStore } from 'vuex';
import axios from 'axios';

axios.defaults.withCredentials = true;
let refreshTimeout = null;

export default createStore({
    state: function() {
        return {
            user: null,
            authenticated: false,
            loading: false,
        };
    },
    getters: {
        user: function(state) {
            return state.user;
        },
        authenticated: function(state) {
            return state.authenticated;
        },
        isLoading: function(state) {
            return state.loading;
        },
    },
    mutations: {
        SET_USER: function(state, user) {
            state.user = user;
            state.authenticated = true;
        },
        CLEAR_USER: function(state) {
            state.user = null;
            state.authenticated = false;
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
                commit('SET_USER', response.data.user);
                dispatch('performTokenRefresh');
            } catch (error) {
                commit('CLEAR_USER');
            } finally {
                setTimeout(() => {
                    commit('SET_LOADING', false);
                }, 500);
            }
        },
        async login({ commit, dispatch }, credentials) {
            await axios.get('/sanctum/csrf-cookie');
            let response = await axios.post('/login', credentials);
            if (response.data.multi_factor_authentication !== true) {
                commit('SET_USER', response.data.user);
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