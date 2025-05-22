import { createStore } from 'vuex';
import axios from 'axios';

axios.defaults.withCredentials = true;

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
        async checkAuth({ commit }) {
            commit('SET_LOADING', true);
            try {
                const response = await axios.get('/me');
                commit('SET_USER', response.data);
            } catch (error) {
                commit('CLEAR_USER');
            } finally {
                commit('SET_LOADING', false);
            }
        },
        async login({ dispatch }, credentials) {
            await axios.get('/sanctum/csrf-cookie');
            let response = await axios.post('/login', credentials);
            if (response.data.multi_factor_authentication === true) {
                setTimeout(() => { 
                    // Redirect to the MFA page
                    this.$router.push({ name: 'mfa', params: { token: response.data.token }});
                }, 1500);
            } else {
                await dispatch('checkAuth');
            }
        },
        async logout({ commit }) {
            try {
                await axios.post('/logout');
            } catch (e) {
                // silently fail if already logged out
            }
            commit('CLEAR_USER');
        },
    },
});