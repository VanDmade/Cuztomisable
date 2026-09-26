<template>
    <div id="portal-layout" class="layout">
        <cz-loading :loading="$store.state.loading" message="Loading..."></cz-loading>
        <app-navbar
            v-if="$store.state.authenticated"
            class="navbar-dark bg-primary"
            :show="$store.state.authenticated"
            :brand="{ image: $url + 'banner-white.png', to: { path: appHome } }"
            :links="navLinks"
            :mobile-breakpoint="992">
            <template #right="{ isMobile, closeMenu }">
                <div v-if="!isMobile" class="navbar-text text-white d-inline-flex align-items-center pl-0 pt-0 pb-0 pr-6 mr-4" style="border-right: 1px solid #fff">
                    <img class="profile-image" :src="$store.state.user?.image || $url+'profile.png'" @click="$router.push({ name: 'profile' })">
                    <span class="pl-2">{{ $store.state.user?.name }}</span>
                </div>
                <div v-else class="navbar-text text-white text-center h4 mb-0 mt-3">
                    <img class="collapsed-profile-image" style="cursor: pointer;" :src="$store.state.user?.image || $url+'profile.png'" @click="closeMenu(); $router.push({ name: 'profile' })">
                    <span class="collapsed-profile-name">{{ $store.state.user?.name }}</span>
                </div>
                <hr v-if="isMobile" class="mobile-logout-divider">
                <a href="#" class="nav-link d-inline-flex align-items-center text-white" role="button" aria-label="Logout" @click.prevent="logout(); closeMenu()">
                    <span class="material-icons" aria-hidden="true">logout</span>
                </a>
            </template>
        </app-navbar>
        <div class="portal-wrapper">
            <app-sidebar v-if="$store.state.authenticated && screenSize === 'large'" :navigation="navigation"></app-sidebar>
            <div class="portal-content">
                <slot></slot>
            </div>
        </div>
        <cz-modal ref="inactivityModal" modal-width="275px" static>
            <h3 class="card-title mb-4">Are you still here?</h3>
            <h1 class="text-center mb-4">{{ countdown }}</h1>
            <button type="button"
                @click="cancelLogout()"
                class="button button--primary button--block">I'm Still Here</button>
        </cz-modal>
        <cz-modal ref="changePasswordModal" modal-width="450px" static>
            <force-change-password-form v-on:close="handleChangePasswordClose"/>
        </cz-modal>
    </div>
</template>
<script>
import ForceChangePasswordForm from '../../components/ChangePassword.vue';
import AppNavbar from '../../components/navigation/Navbar.vue';
import AppSidebar from '../../components/navigation/Sidebar.vue';
import loading from '../../utils/loading.js';

export default {
    data: function() {
        return {
            appHome: import.meta.env.VITE_APP_HOME ?? '/portal',
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
        logout: async function() {
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
    computed: {
        navigation: function() {
            const nav = this.$cuztomisable?.navigation;
            if (Array.isArray(nav) && nav.length) {
                return nav;
            }
            return [];
        },
        navLinks: function() {
            // Desktop shows these in the sidebar - only the mobile menu needs them in the navbar
            const links = this.navigation.map((item) => ({
                text: item.text,
                icon: item.icon,
                route: item.route,
                href: item.path,
                mobileOnly: true,
            }));
            const canManageRoles = this.$store.getters.hasPermission('manage-roles-permissions');
            const canSeeUsers = this.$store.getters.hasPermission('view-users|manage-users');
            const canInvite = this.$store.getters.hasPermission('invite-users');
            const adminChildren = [];
            if (canSeeUsers) {
                adminChildren.push({ route: 'users', text: 'Users', icon: 'group' });
            }
            if (canInvite) {
                adminChildren.push({ route: 'invites', text: 'Invitations', icon: 'mail' });
            }
            if (canManageRoles) {
                if (adminChildren.length) {
                    adminChildren.push({ divider: true });
                }
                adminChildren.push({ route: 'roles', text: 'Roles', icon: 'security' });
                adminChildren.push({ route: 'permissions', text: 'Permissions', icon: 'verified_user' });
            }
            if (adminChildren.length) {
                // Desktop already has this in the sidebar - only show it here in the mobile menu.
                links.push({ text: 'Administrator', icon: 'admin_panel_settings', children: adminChildren, mobileOnly: true });
            }
            return links;
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
        'app-navbar': AppNavbar,
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
/* Flex children default to min-width:auto, which refuses to shrink below content's
   natural width - without this, a long name + profile image + logout icon blows out
   past the navbar (and page) width instead of shrinking/wrapping like the rest of it. */
.portal-navbar :deep(.portal-navbar-shell),
.portal-navbar :deep(.portal-navbar-top),
.portal-navbar :deep(.portal-navbar-desktop),
.portal-navbar :deep(.portal-navbar-right) {
    min-width: 0;
}
.portal-navbar :deep(.portal-navbar-right) {
    flex-shrink: 1;
}
.portal-navbar :deep(.portal-navbar-right .navbar-text) {
    min-width: 0;
    overflow: hidden;
}
.portal-navbar :deep(.portal-navbar-right .navbar-text span:last-child) {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 160px;
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
