<template>
    <div id="login-layout" class="layout">
        <div class="center-container">
            <slot></slot>
        </div>
        <pre>{{ $cuztomisable }}</pre>
    </div>
</template>
<script>
export default {
    data: function() {
        return {

        }
    },
    created: function() {
        // Checks to see if the user is logged in and needs to be directed to the portal
        if (!this.$route.meta.require_authentication && this.$store.state.authenticated) {
            this.$router.push({ name: 'portal' });
            return;
        }
    },
    methods: {

    },
    
}
</script>