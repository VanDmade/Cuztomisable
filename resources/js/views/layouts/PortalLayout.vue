<template>
    <div id="portal-layout" class="layout">
        <fm-loading :loading="$store.state.loading" message="Loading..."></fm-loading>
        <nav class="navbar navbar-expand-lg bg--primary mb-6 shadow" v-if="navbar">
            <div :class="{ 'container': breakpoint('lg'), 'container-fluid': breakpoint('md') || breakpoint('sm') }">
                <router-link class="navbar-brand pa-0" :to="{ name: 'portal' }"><img :src="$url+'cuztomisable/logo.png'" style="height: 32px;"></router-link>
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
                        <template v-if="navigation && navigation.length">
                            <li class="nav-item" v-for="(item, index) in navigation">
                                <router-link class="nav-link" :class="$route.name == item.route ? 'active' : ''" :to="{ name: item.route }">{{ item.text }}</router-link>
                            </li>
                        </template>
                        <template v-if="$store.getters.hasPermission('view-users|manage-users|invite-users|manage-roles-permissions')">
                            <hr v-if="screenSize == 'medium'">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Administrator</a>
                                <ul class="dropdown-menu bg-white text-dark">
                                    <li v-if="$store.getters.hasPermission('view-users|manage-users')"><router-link class="dropdown-item" :class="$route.name == 'users' ? 'active' : ''" :to="{ name: 'users' }">Users</router-link></li>
                                    <li v-if="$store.getters.hasPermission('invite-users')"><router-link class="dropdown-item" :class="$route.name == 'invites' ? 'active' : ''" :to="{ name: 'invites' }">Invitations</router-link></li>
                                    <template v-if="$store.getters.hasPermission('manage-roles-permissions')">
                                        <li v-if="$store.getters.hasPermission('view-users|manage-users|invite-users')"><hr class="dropdown-divider"></li>
                                        <li><router-link class="dropdown-item" :class="$route.name == 'roles' ? 'active' : ''" :to="{ name: 'roles' }">Roles</router-link></li>
                                        <li><router-link class="dropdown-item" :class="$route.name == 'permissions' ? 'active' : ''" :to="{ name: 'permissions' }">Permissions</router-link></li>
                                    </template>
                                </ul>
                            </li>
                        </template>
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
        <fm-modal ref="inactivityModal" modal-width="275px" static>
            <h3 class="card-title mb-4">Are you still here?</h3>
            <h1 class="text-center mb-4">{{ countdown }}</h1>
            <button type="button"
                @click="cancelLogout()"
                class="button button--primary button--block">I'm Still Here</button>
        </fm-modal>
        <fm-modal ref="changePasswordModal" modal-width="450px" static>
            <force-change-password-form v-on:close="handleChangePasswordClose"/>
        </fm-modal>
    </div>
</template>
<script>
import ForceChangePasswordForm from '../../components/ChangePassword.vue';

export default {
    data: function() {
        return {
            navbar: this.$store.state.authenticated,
            navbarToggle: false,
            screenSize: 'large',
            inactivityTimer: null,
            verifyInactivity: null,
            inactivityLimit: 5 * 60 * 1000,
            verifyInactivityLimit: 10,
            countdown: 0,
            events: ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'],
        }
    },
    mounted: function() {
        this.onResize();
        if (this.$route.meta.authentication && !this.$store.state.authenticated) {
            this.$router.push({ name: 'login' });
        }
        if (this.$store.state.change_password) {
            this.$nextTick(() => {
                this.$refs['changePasswordModal']?.open();
            });
        }
        this.$nextTick(() => {
            window.addEventListener('resize', this.onResize);
        })
    },
    beforeDestroy: function() { 
        this.removeActivityListeners();
        window.removeEventListener('resize', this.onResize); 
    },
    methods: {
        handleChangePasswordClose: function() {
            if (this.$store.state.change_password) {
                this.$nextTick(() => {
                    this.$refs['changePasswordModal']?.open();
                });
                return;
            }

            this.$refs['changePasswordModal']?.close();
        },
        onResize: function() {
            this.screenSize = window.innerWidth <= 992 ? 'medium' : 'large';
        },
        startInactivityWatcher: function() {
            this.resetInactivityTimer();
            this.events.forEach(event => window.addEventListener(event, this.resetInactivityTimer));
        },
        removeActivityListeners: function() {
            clearTimeout(this.inactivityTimer);
            clearTimeout(this.verifyInactivity);
            const backdrop = document.querySelector('.modal-backdrop.fade.show');
            if (backdrop) {
                backdrop.remove();
                document.body.classList.remove('modal-open');
            }
            this.events.forEach(event => window.removeEventListener(event, this.resetInactivityTimer));
        },
        resetInactivityTimer: function() {
            clearTimeout(this.inactivityTimer);
            this.inactivityTimer = setTimeout(() => {
                this.$refs['inactivityModal'].open();
                clearTimeout(this.verifyInactivity);
                this.startCountdown();
                this.verifyInactivity = setTimeout(async () => {
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
            clearTimeout(this.verifyInactivity);
            this.$refs['inactivityModal'].close();
            this.resetInactivityTimer();
        },
        async logout() {
            this.$loading.show({ message: 'See you next time!' });
            setTimeout(async () => {
                this.removeActivityListeners();
                await this.$store.dispatch('logout');
                this.$router.push({ name: 'login' });
            }, 250);
        },
    },
    watch: {
        '$store.state.authenticated': {
            immediate: true,
            handler: function(value) {
                if (value) {
                    this.startInactivityWatcher();
                } else {
                    this.removeActivityListeners();
                }
            },
            deep: true,
        },
        '$store.state.change_password': {
            immediate: true,
            handler: function(value) {
                this.$nextTick(() => {
                    if (value) {
                        this.$refs['changePasswordModal']?.open();
                    } else {
                        this.$refs['changePasswordModal']?.close();
                    }
                });
            },
            deep: true,
        },
    },
    components: {
        'force-change-password-form': ForceChangePasswordForm,
    }
}
</script>