import { createStore } from 'vuex';
import axios from 'axios';

axios.defaults.withCredentials = true;
let refreshInterval = null;

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
                dispatch('startTokenRefresh');
            } catch (error) {
                commit('CLEAR_USER');
            } finally {
                setTimeout(() => {
                    commit('SET_LOADING', false);
                }, 500);
            }
        },
        async login({ dispatch }, credentials) {
            await axios.get('/sanctum/csrf-cookie');
            let response = await axios.post('/login', credentials);
            if (response.data.multi_factor_authentication !== true) {
                await dispatch('checkAuth');
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
                commit('CLEAR_USER');
                setTimeout(() => {
                    commit('SET_LOADING', false);
                }, 1500);
            }
        },
        startTokenRefresh: function({ dispatch }) {
            if (refreshInterval) {
                dispatch('clearTokenRefresh');
            }
            let sessionLength = this.$cuztomisable?.session_length ?? 600;
            // Sets up the refresh interval to refresh the authentication token
            refreshInterval = setInterval(async () => {
                try {
                    await axios.get('/refresh', { withCredentials: true });
                } catch (error) {
                    if (error?.response?.status === 401) {
                        dispatch('logout');
                    } else {
                        console.error('Token refresh failed:', error);
                    }
                }
            }, (sessionLength - 15) * 1000);
        },
        clearTokenRefresh: function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
                refreshInterval = null;
            }
        }
    },
});