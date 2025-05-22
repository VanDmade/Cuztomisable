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
                this.loading = true;
                this.layout = value ? 'portal-layout' : 'login-layout';
                setTimeout(() => {
                    this.loading = false;
                }, 500);
            },
            deep: true,
        },
    },
    components: {
        'login-layout': LoginLayout,
        'portal-layout': PortalLayout,
    }
}
</script>