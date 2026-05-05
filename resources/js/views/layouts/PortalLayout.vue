<template>
    <div id="portal-layout" class="layout">
        <fm-loading :loading="$store.state.loading" message="Loading..."></fm-loading>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary portal-navbar" v-if="$store.state.authenticated">
            <div class="position-relative container-fluid">
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
                <div class="collapse navbar-collapse portal-navbar-collapse" id="navigation">
                    <div class="navbar-nav me-auto d-lg-none">
                        <div v-if="screenSize == 'medium'" class="navbar-text text-white text-center h4 mb-0 mt-3">
                            <img class="collapsed-profile-image" :src="$url+'test.png'">
                            <span class="collapsed-profile-name">{{ $store.state.user?.name }}</span>
                        </div>
                        <template v-if="navigation && navigation.length">
                            <li class="nav-item" v-for="(item, index) in navigation" :key="`${item.route || item.text || 'nav'}-${index}`">
                                <router-link class="nav-link d-inline-flex align-items-center" :class="$route.name == item.route ? 'active active-nav' : ''" :to="{ name: item.route }" @click.native="collapseNavbar">
                                    <span v-if="item.icon" class="material-icons me-1" aria-hidden="true">{{ item.icon }}</span>
                                    <span>{{ item.text }}</span>
                                </router-link>
                            </li>
                        </template>
                        <template v-if="$store.getters.hasPermission('view-users|manage-users|invite-users|manage-roles-permissions')">
                            <hr v-if="screenSize == 'medium'" class="mobile-logout-divider">
                            <li v-if="screenSize == 'medium'" class="nav-item d-flex flex-column pt-2">
                                <div class="d-inline-flex align-items-center admin-section-title" style="color: #fff !important; opacity: 1 !important;">
                                    <span class="material-icons me-1" aria-hidden="true">admin_panel_settings</span>
                                    <span>Administrator</span>
                                </div>
                                <div class="d-flex flex-column mb-2">
                                    <router-link v-if="$store.getters.hasPermission('view-users|manage-users')" class="nav-link d-inline-flex align-items-center admin-section-link" :class="$route.name == 'users' ? 'active' : ''" :to="{ name: 'users' }"><span class="material-icons me-1" aria-hidden="true">group</span><span>Users</span></router-link>
                                    <router-link v-if="$store.getters.hasPermission('invite-users')" class="nav-link d-inline-flex align-items-center admin-section-link" :class="$route.name == 'invites' ? 'active' : ''" :to="{ name: 'invites' }"><span class="material-icons me-1" aria-hidden="true">mail</span><span>Invitations</span></router-link>
                                    <router-link v-if="$store.getters.hasPermission('manage-roles-permissions')" class="nav-link d-inline-flex align-items-center admin-section-link" :class="$route.name == 'roles' ? 'active' : ''" :to="{ name: 'roles' }"><span class="material-icons me-1" aria-hidden="true">security</span><span>Roles</span></router-link>
                                    <router-link v-if="$store.getters.hasPermission('manage-roles-permissions')" class="nav-link d-inline-flex align-items-center admin-section-link" :class="$route.name == 'permissions' ? 'active' : ''" :to="{ name: 'permissions' }"><span class="material-icons me-1" aria-hidden="true">verified_user</span><span>Permissions</span></router-link>
                                </div>
                            </li>
                            <li v-else class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-inline-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="material-icons me-1" aria-hidden="true">admin_panel_settings</span>
                                    <span>Administrator</span>
                                </a>
                                <ul class="dropdown-menu bg-white text-dark">
                                    <li v-if="$store.getters.hasPermission('view-users|manage-users')"><router-link class="dropdown-item d-inline-flex align-items-center" :class="$route.name == 'users' ? 'active' : ''" :to="{ name: 'users' }"><span class="material-icons me-1" aria-hidden="true">group</span><span>Users</span></router-link></li>
                                    <li v-if="$store.getters.hasPermission('invite-users')"><router-link class="dropdown-item d-inline-flex align-items-center" :class="$route.name == 'invites' ? 'active' : ''" :to="{ name: 'invites' }"><span class="material-icons me-1" aria-hidden="true">mail</span><span>Invitations</span></router-link></li>
                                    <template v-if="$store.getters.hasPermission('manage-roles-permissions')">
                                        <li v-if="$store.getters.hasPermission('view-users|manage-users|invite-users')"><hr class="dropdown-divider"></li>
                                        <li><router-link class="dropdown-item d-inline-flex align-items-center" :class="$route.name == 'roles' ? 'active' : ''" :to="{ name: 'roles' }"><span class="material-icons me-1" aria-hidden="true">security</span><span>Roles</span></router-link></li>
                                        <li><router-link class="dropdown-item d-inline-flex align-items-center" :class="$route.name == 'permissions' ? 'active' : ''" :to="{ name: 'permissions' }"><span class="material-icons me-1" aria-hidden="true">verified_user</span><span>Permissions</span></router-link></li>
                                    </template>
                                </ul>
                            </li>
                        </template>
                    </div>
                    <div class="navbar-nav ms-auto">
                        <span v-if="screenSize == 'large'" class="navbar-text text-white d-inline-flex align-items-center pl-0 pt-0 pb-0 pr-6 mr-4" style="border-right: 1px solid #fff">
                            <img class="profile-image" :src="$url+'test.png'" @click="$router.push({ name: 'profile' })">
                            <span class="pl-2">{{ $store.state.user?.name }}</span>
                        </span>
                        <hr v-if="screenSize == 'medium'" class="mobile-logout-divider">
                        <a href="#" class="nav-link d-inline-flex align-items-center text-white" role="button" @click.prevent="logout">
                            <span class="material-icons me-1" aria-hidden="true">logout</span>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        <div class="portal-wrapper">
            <app-sidebar v-if="$store.state.authenticated && screenSize === 'large'" :navigation="navigation"></app-sidebar>
            <div class="portal-content">
                <slot></slot>
            </div>
        </div>
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
import AppSidebar from '../../components/navigation/Sidebar.vue';
import loading from '../../utils/loading.js';

export default {
    data: function() {
        return {
            screenSize: 'large',
            inactivityTimer: null,
            verifyInactivity: null,
            inactivityLimit: 5 * 60 * 1000,
            verifyInactivityLimit: 10,
            countdown: 0,
            events: ['mousemove', 'keydown', 'mousedown', 'touchstart', 'scroll'],
        }
    },
    computed: {
        navigation: function() {
            const nav = this.$cuztomisable?.navigation;
            if (Array.isArray(nav) && nav.length) {
                return nav;
            }
            return [];
        },
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
            this.$watch('$route', () => {
                this.collapseNavbar();
            });
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
        collapseNavbar: function() {
            // Always collapse immediately on link click
            const nav = document.getElementById('navigation');
            if (nav && nav.classList.contains('show')) {
                nav.classList.remove('show');
            }
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
            loading.show({ message: 'See you next time!' });
            setTimeout(async () => {
                this.removeActivityListeners();
                await this.$store.dispatch('logout');
                setTimeout(() => {
                  loading.hide();
                  this.$router.push({ name: 'login' });
                }, 1000);
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
        'app-sidebar': AppSidebar,
    }
}
</script>

<style scoped>
#portal-layout {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.portal-navbar {
    border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.portal-wrapper {
    display: flex;
    flex: 1;
    align-items: stretch;
    min-height: 0;
}

.portal-content {
    flex: 1;
    min-width: 0;
}

.portal-content :deep(.page) {
    padding-top: 1.5rem;
}
</style>
