<template>
    <div id="portal-layout" class="layout">
        <nav class="navbar navbar-expand-lg bg--primary mb-6 shadow" data-bs-theme="dark">
            <div class="container-fluid">
                <router-link class="navbar-brand" :to="{ name: 'portal' }">Cuztomisable</router-link>
                <button class="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navigation"
                    aria-controls="navigation"
                    aria-expanded="false"
                    aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navigation">
                    <div class="navbar-nav me-auto">
                        <span v-if="screenSize == 'medium'" class="navbar-text text-white h4 mb-0 mt-3">{{ user.name }}</span>
                        <hr v-if="screenSize == 'medium'">
                        <router-link class="nav-link" :class="$route.name == 'portal' ? 'active' : ''" :to="{ name: 'portal' }">Portal</router-link>
                    </div>
                    <div class="navbar-nav ms-auto" >
                        <span v-if="screenSize == 'large'" class="navbar-text text-white pr-6 mr-4" style="border-right: 1px solid #fff">{{ user.name }}</span>
                        <span class="nav-link d-inline-flex align-items-center text-white" href="#" @click="logout">
                            <span v-if="screenSize == 'large'" class="material-icons">logout</span>
                            <span v-else>Logout</span>
                        </span>
                    </div>
                </div>
            </div>
        </nav>
        <slot></slot>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            user: [],
            navbarToggle: false,
            screenSize: 'large',
        }
    },
    created: function() {
        if (this.$route.meta.require_authentication && !this.$store.state.authenticated) {
            this.$emit('layout', 'login-layout');
            this.$router.push({ name: 'login' });
        }
    },
    mounted: function() {
        this.onResize();
        this.$nextTick(() => {
            window.addEventListener('resize', this.onResize);
        })
    },
    beforeDestroy: function() { 
        window.removeEventListener('resize', this.onResize); 
    },
    methods: {
        onResize: function() {
            this.screenSize = window.innerWidth <= 992 ? 'medium' : 'large';
        },
        async logout() {
            this.$emit('loading', true);
            setTimeout(async () => {
                await this.$store.dispatch('logout');
                this.$router.push({ name: 'login' });
            }, 250);
        }
    },
}
</script>