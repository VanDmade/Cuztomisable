<template>
    <div id="portal-layout" class="layout">
        <nav class="navbar navbar-expand-lg bg--secondary mb-6 shadow">
            <div :class="{ 'container': breakpoint('lg'), 'container-fluid': breakpoint('md') || breakpoint('sm') }">
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
                        <div v-if="screenSize == 'medium'" class="navbar-text text-white text-center h4 mb-0 mt-3">
                            <img class="collapsed-profile-image" :src="$url+'test.png'">
                            <span class="collapsed-profile-name">{{ $store.state.user?.name }}</span>
                        </div>
                        <hr v-if="screenSize == 'medium'">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Administrator</a>
                            <ul class="dropdown-menu bg-white text-dark">
                                <li><router-link class="dropdown-item" :class="$route.name == 'users' ? 'active' : ''" :to="{ name: 'users' }">Users</router-link></li>
                                <li><router-link class="dropdown-item" :class="$route.name == 'invites' ? 'active' : ''" :to="{ name: 'invites' }">Invitations</router-link></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><router-link class="dropdown-item" :class="$route.name == 'roles' ? 'active' : ''" :to="{ name: 'roles' }">Roles</router-link></li>
                                <li><router-link class="dropdown-item" :class="$route.name == 'permissions' ? 'active' : ''" :to="{ name: 'permissions' }">Permissions</router-link></li>
                            </ul>
                        </li>
                    </div>
                    <div class="navbar-nav ms-auto" >
                        <span v-if="screenSize == 'large'" class="navbar-text text-white pl-0 pt-0 pb-0 pr-6 mr-4" style="border-right: 1px solid #fff">
                            <img class="profile-image" :src="$url+'test.png'" @click="$router.push({ name: 'profile' })">
                        </span>
                        <span class="nav-link d-inline-flex align-items-center text-white" @click="logout">
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
            navbarToggle: false,
            screenSize: 'large',
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
            this.$emit('loadingMessage', 'See you next time!');
            setTimeout(async () => {
                await this.$store.dispatch('logout');
                this.$router.push({ name: 'login' });
            }, 250);
        }
    },
}
</script>