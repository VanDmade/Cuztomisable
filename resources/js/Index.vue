<template>
    <div id="cuztomisable-app">
        <loading v-if="loading"></loading>
        <component v-else
            :is="layout"
            :settings="settings">
            <router-view v-slot="{ component, route }"></router-view>
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
            settings: [],
            value: [],
        }
    },
    created: function() {
        
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