<template>
    <div id="cuztomisable-app">
        <fm-loading :loading="loading || $store.state.loading" :message="loadingMessage"></fm-loading>
        <fm-message :message="message" :length="message.timeout_length"></fm-message>
        <component
            v-if="!loading && !$store.state.loading"
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
        <fm-modal ref="inactivityModal" modal-width="275px" static>
            <h3 class="card-title mb-4">Are you still here?</h3>
            <h1 class="text-center mb-4">{{ countdown }}</h1>
            <button type="button"
                @click="cancelLogout()"
                class="button button--primary button--block">I'm Still Here</button>
        </fm-modal>
        <fm-modal ref="changePasswordModal" modal-width="450px" static>
            <force-change-password-form
                v-on:message="setMessage"
                v-on:close="$refs['changePasswordModal'].close()"/>
        </fm-modal>
    </div>
</template>
<script>
import LoginLayout from './views/layouts/LoginLayout.vue';
import PortalLayout from './views/layouts/PortalLayout.vue';
import ForceChangePasswordForm from './components/ChangePassword.vue';
export default {
    data: function() {
        return {
            loading: false,
            loadingMessage: 'Loading...',
            defaultLoadingMessage: 'Loading...',
            inactivityTimer: null,
            // Will trigger inactivity logout sequence when 5 minutes has passed and zero movement of the mouse
            inactivityLimit: 5 * 60 * 1000,
            verifyInactivity: null,
            // Once the inactivity occurrs, 10 seconds will count down allowing the user to say they are still there.
            verifyInactivityLimit: 10,
            countdown: 0,
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
            !this.$route.meta.require_authentication &&
            this.$route.name != 'message') {
            this.$router.push({ name: 'portal' });
        } else if (!this.$store.state.authenticated &&
            this.$route.meta.require_authentication) {
            this.$router.push({ name: 'login' });
        }
        if (this.$store.state.change_password) {
            this.$refs['changePasswordModal'].open();
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
        removeActivityListeners: function() {
            clearTimeout(this.inactivityTimer);
            clearTimeout(this.verifiyInactivity);
            // This is for the modals that are potentially open and need to be closed
            const backdrop = document.querySelector('.modal-backdrop.fade.show');
            if (backdrop) {
                // Cleanly removes the backdrop
                backdrop.remove();
                // Prevents scroll lock
                document.body.classList.remove('modal-open');
            }
            this.events.forEach(event => window.removeEventListener(event, this.resetInactivityTimer));
        },
        resetInactivityTimer: function() {
            clearTimeout(this.inactivityTimer);
            this.inactivityTimer = setTimeout(() => {
                this.$refs['inactivityModal'].open();
                clearTimeout(this.verifiyInactivity);
                this.startCountdown();
                this.verifiyInactivity = setTimeout(async () => {
                    this.$refs['inactivityModal'].close();
                    this.logout();
                }, this.verifyInactivityLimit * 1000);
            }, this.inactivityLimit);
        },
        startCountdown: function() {
            this.countdown = this.verifyInactivityLimit;
            setTimeout(() => {
                for (let i = 0; i < this.countdown; i++) {
                    setTimeout(() => {
                        this.countdown--;
                    }, i * 1000);
                }
            }, 500);
        },
        cancelLogout: function() {
            clearTimeout(this.verifiyInactivity);
            this.$refs['inactivityModal'].close();
            this.resetInactivityTimer();
        },
        logout: function() {
            this.loadingMessage = 'See you next time!';
            setTimeout(async () => {
                this.removeActivityListeners();
                await this.$store.dispatch('logout');
                this.$router.push({ name: 'login' });
            }, 250);
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
        },
        '$store.state.change_password': {
            immediate: true,
            handler: function(value) {
                if (value) {
                    this.$refs['changePasswordModal'].open();
                }
            },
            deep: true,
        }
    },
    components: {
        'login-layout': LoginLayout,
        'portal-layout': PortalLayout,
        'force-change-password-form': ForceChangePasswordForm,
    }
}
</script>