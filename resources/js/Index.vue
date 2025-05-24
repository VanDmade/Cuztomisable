<template>
    <div id="cuztomisable-app">
        <fm-loading :loading="loading || $store.state.loading" :message="loadingMessage"></fm-loading>
        <fm-message :message="message" :length="message.timeout_length"></fm-message>
        <component
            v-show="!loading && !$store.state.loading"
            :is="typeof($route.meta.layout) != 'undefined' ? $route.meta.layout : 'portal-layout'"
            v-on:message="setMessage"
            v-on:loading="setLoading"
            v-on:loading-message="setLoadingMessage"
            v-on:layout="setLayout">
            <router-view v-slot="{ Component, route }">
                <transition name="fade" mode="out-in" appear>
                    <component
                        :is="Component" 
                        v-on:message="setMessage"
                        v-on:loading="setLoading"
                        v-on:loading-message="setLoadingMessage"
                        v-on:layout="setLayout" />
                </transition>
            </router-view>
        </component>
    </div>
</template>
<script>
import LoginLayout from './views/layouts/LoginLayout.vue';
import PortalLayout from './views/layouts/PortalLayout.vue';
export default {
    data: function() {
        return {
            loading: false,
            loadingMessage: 'Loading...',
            defaultLoadingMessage: 'Loading...',
            inactivityTimer: null,
            inactivityLimit: 5 * 60 * 1000,
            events: ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'],
            layout: 'login-layout',
            message: {
                id: 0,
                text: '',
                error: false,
                timeout_length: 5000,
            },
        }
    },
    async created() {
        // Checks the authentication of the user
        const response = await this.$store.dispatch('checkAuth');
        if (this.$store.state.authenticated &&
            !this.$route.meta.require_authentication) {
            this.$router.push({ name: 'portal' });
        } else if (!this.$store.state.authenticated &&
            this.$route.meta.require_authentication) {
            this.$router.push({ name: 'login' });
        }
    },
    methods: {
        setLoading: function(loading) {
            this.loading = loading;
            if (loading == false) {
                setTimeout(() => {
                    this.loadingMessage = this.clone(this.defaultLoadingMessage);
                }, 500);
            }
        },
        setLoadingMessage: function(message) {
            this.loadingMessage = message;
        },
        setLayout: function(layout) {
            if (this.layout == layout) {
                setTimeout(() => {
                    this.loading = false;
                }, 250);
                return false;
            }
            this.loading = true;
            setTimeout(() => {
                this.layout = layout;
            }, 250);
            setTimeout(() => {
                this.loading = false;
            }, 1150);
        },
        setMessage: function(message) {
            this.message.id = this.message.id + 1;
            this.message.text = message.text;
            this.message.error = message.error ?? false;
        },
        startInactivityWatcher: function() {
            this.resetInactivityTimer();
            this.events.forEach(event => window.addEventListener(event, this.resetInactivityTimer));
        },
        removeActivityListeners() {
            this.events.forEach(event => window.removeEventListener(event, this.resetInactivityTimer));
        },
        resetInactivityTimer() {
            clearTimeout(this.inactivityTimer);
            this.inactivityTimer = setTimeout(() => {
                this.loadingMessage = 'See you next time!';
                setTimeout(async () => {
                    await this.$store.dispatch('logout');
                    this.$router.push({ name: 'login' });
                }, 250);
            }, this.inactivityLimit);
        }
    },
    watch: {
        '$store.state.authenticated': {
            handler: function(value) {
                if (value) {
                    this.startInactivityWatcher();
                } else {
                    this.removeActivityListeners();
                }
            },
            deep: true,
        },
        '$store.state.loading': {
            handler: function(value) {
                if (!value) {
                    setTimeout(() => {
                        this.loadingMessage = this.clone(this.defaultLoadingMessage);
                    }, 500);
                }
            },
            deep: true,
        }
    },
    components: {
        'login-layout': LoginLayout,
        'portal-layout': PortalLayout,
    }
}
</script>