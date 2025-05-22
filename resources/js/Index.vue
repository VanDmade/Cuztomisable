<template>
    <div id="cuztomisable-app">
        <fm-loading :loading="loading || !initialized"></fm-loading>
        <fm-message :message="message" :length="message.timeout_length"></fm-message>
        <component
            v-show="!loading && initialized"
            :is="layout"
            :settings="settings"
            v-on:message="setMessage"
            v-on:loading="setLoading"
            v-on:layout="setLayout">
            <router-view v-slot="{ component, route }" v-on:message="setMessage" v-on:loading="setLoading" v-on:layout="setLayout"></router-view>
        </component>
    </div>
</template>
<script>
import LoginLayout from './views/layouts/LoginLayout.vue';
import PortalLayout from './views/layouts/PortalLayout.vue';
export default {
    data: function() {
        return {
            initialized: false,
            loading: true,
            layout: 'login-layout',
            message: {
                id: 0,
                text: '',
                error: false,
                timeout_length: 5000,
            },
            settings: [],
            value: [],
        }
    },
    async created() {
        // Checks the authentication of the user
        const response = await this.$store.dispatch('checkAuth');
        setTimeout(() => {
            this.initialized = true;
        }, 150);
        if (this.$store.state.authenticated && this.layout == 'login-layout') {
            this.$router.push({ name: 'portal' });
        }
    },
    methods: {
        setLoading: function(loading) {
            this.loading = loading;
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
        }
    },
    watch: {

    },
    components: {
        'login-layout': LoginLayout,
        'portal-layout': PortalLayout,
    }
}
</script>