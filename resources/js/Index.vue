<template>
    <div id="cuztomisable-app">
        <loading v-if="loading"></loading>
        <component v-else
            :is="layout"
            :settings="settings">
            <fm-message :message="{ id: message.id, text: message.text, error: message.error }" :length="message.timeout_length"></fm-message>
            <router-view v-slot="{ component, route }" v-on:message="setMessage"></router-view>
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
    created: function() {
        
    },
    methods: {
        setMessage: function(message) {
            this.message.id = this.message.id++;
            this.message.text = message.text;
            this.message.error = message.error ?? false;
        }
    },
    watch: {
        '$store.state.authenticated': {
            immediate: true,
            handler: function(value) {
                this.layout = value ? 'portal-layout' : 'login-layout';
                if (!value && this.$route.meta.require_authentication) {
                    this.$router.push({ name: 'login' });
                }
            },
            deep: true,
        },
        '$route.name': {
            immediate: true,
            handler: function(name) {
                if (this.$store.state.authenticated) {
                    if (!this.$route.meta.require_authentication) {
                        this.$router.push({ name: 'portal' });
                    }
                } else if (this.$route.meta.require_authentication) {
                    this.$router.push({ name: 'login' });
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