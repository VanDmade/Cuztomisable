<template>
    <nav v-if="show" class="portal-navbar mb-6 shadow">
        <div class="portal-navbar-shell container-fluid">
            <div class="portal-navbar-top">
                <router-link class="navbar-brand pa-0 portal-navbar-brand" :to="brand.to || { name: 'portal' }" @click="closeMenus">
                    <slot name="brand">
                        <img v-if="brand.image" :src="brand.image" :style="brand.imageStyle || 'height: 32px;'" :alt="brand.alt || brand.text || 'Brand'">
                        <span v-else>{{ brand.text || 'Brand' }}</span>
                    </slot>
                </router-link>
                <div class="portal-navbar-mobile-controls">
                    <slot name="mobile-top" :is-mobile="true" :close-menu="closeMenus"></slot>
                    <button
                        class="portal-navbar-toggle"
                        type="button"
                        :aria-controls="navigationId"
                        :aria-expanded="menuOpen ? 'true' : 'false'"
                        aria-label="Toggle navigation"
                        @click="toggleMenu">
                        ☰
                    </button>
                </div>
                <div class="portal-navbar-desktop">
                    <div class="portal-navbar-left">
                        <template v-for="(item, index) in visibleLinks" :key="item.key || item.text || index">
                            <div v-if="!hasChildren(item)" class="portal-navbar-item">
                                <router-link
                                    v-if="item.route"
                                    class="nav-link portal-navbar-link"
                                    :class="isRouteActive(item) ? 'active' : ''"
                                    :to="{ name: item.route }"
                                    @click="closeMenus">
                                    <span v-if="item.icon" class="material-icons me-1" aria-hidden="true">{{ item.icon }}</span>
                                    <span>{{ resolveText(item) }}</span>
                                </router-link>
                                <a
                                    v-else
                                    class="nav-link portal-navbar-link"
                                    :href="item.href || '#'"
                                    :target="item.target"
                                    :rel="item.rel"
                                    @click="closeMenus">
                                    <span v-if="item.icon" class="material-icons me-1" aria-hidden="true">{{ item.icon }}</span>
                                    <span>{{ resolveText(item) }}</span>
                                </a>
                            </div>
                            <div v-else class="portal-navbar-item portal-navbar-dropdown-wrap" :class="{ open: activeDropdownKey === dropdownKey(item, index) }">
                                <a
                                    class="nav-link portal-navbar-link portal-navbar-dropdown-toggle"
                                    href="#"
                                    role="button"
                                    :aria-expanded="activeDropdownKey === dropdownKey(item, index) ? 'true' : 'false'"
                                    @click.prevent="toggleDropdown(dropdownKey(item, index))">
                                    <span v-if="item.icon" class="material-icons me-1" aria-hidden="true">{{ item.icon }}</span>
                                    <span>{{ resolveText(item) }}</span>
                                </a>
                                <div class="portal-navbar-dropdown" :class="{ show: activeDropdownKey === dropdownKey(item, index) }">
                                    <template v-for="(child, childIndex) in item.children" :key="child.key || child.text || `${index}-${childIndex}`">
                                        <hr v-if="child.divider" class="dropdown-divider">
                                        <router-link
                                            v-else-if="child.route"
                                            class="dropdown-item portal-navbar-dropdown-link"
                                            :class="isRouteActive(child) ? 'active' : ''"
                                            :to="{ name: child.route }"
                                            @click="closeMenus">
                                            <span v-if="child.icon" class="material-icons me-1" aria-hidden="true">{{ child.icon }}</span>
                                            <span>{{ resolveText(child) }}</span>
                                        </router-link>
                                        <a
                                            v-else
                                            class="dropdown-item portal-navbar-dropdown-link"
                                            :href="child.href || '#'"
                                            :target="child.target"
                                            :rel="child.rel"
                                            @click="closeMenus">
                                            <span v-if="child.icon" class="material-icons me-1" aria-hidden="true">{{ child.icon }}</span>
                                            <span>{{ resolveText(child) }}</span>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <slot name="left-extra" :is-mobile="false" :close-menu="closeMenus"></slot>
                    </div>
                    <div class="portal-navbar-right">
                        <slot name="right" :is-mobile="false" :close-menu="closeMenus"></slot>
                    </div>
                </div>
            </div>
            <div class="portal-navbar-mobile" :id="navigationId" :class="{ show: menuOpen }">
                <slot name="mobile-header" :is-mobile="true"></slot>
                <div class="portal-navbar-mobile-links">
                    <template v-for="(item, index) in visibleLinks" :key="`mobile-${item.key || item.text || index}`">
                        <div v-if="!hasChildren(item)" class="portal-navbar-mobile-item">
                            <router-link
                                v-if="item.route"
                                class="nav-link portal-navbar-link"
                                :class="isRouteActive(item) ? 'active' : ''"
                                :to="{ name: item.route }"
                                @click="closeMenus">
                                <span v-if="item.icon" class="material-icons me-1" aria-hidden="true">{{ item.icon }}</span>
                                <span>{{ resolveText(item) }}</span>
                            </router-link>
                            <a
                                v-else
                                class="nav-link portal-navbar-link"
                                :href="item.href || '#'"
                                :target="item.target"
                                :rel="item.rel"
                                @click="closeMenus">
                                <span v-if="item.icon" class="material-icons me-1" aria-hidden="true">{{ item.icon }}</span>
                                <span>{{ resolveText(item) }}</span>
                            </a>
                        </div>
                        <div v-else class="portal-navbar-mobile-item portal-navbar-mobile-section">
                            <div class="portal-navbar-mobile-section-title">
                                <span v-if="item.icon" class="material-icons me-1" aria-hidden="true">{{ item.icon }}</span>
                                <span>{{ resolveText(item) }}</span>
                            </div>
                            <div class="portal-navbar-mobile-children show">
                                <template v-for="(child, childIndex) in item.children" :key="`mobile-${child.key || child.text || `${index}-${childIndex}`}`">
                                    <hr v-if="child.divider" class="dropdown-divider">
                                    <router-link
                                        v-else-if="child.route"
                                        class="dropdown-item portal-navbar-dropdown-link"
                                        :class="isRouteActive(child) ? 'active' : ''"
                                        :to="{ name: child.route }"
                                        @click="closeMenus">
                                        <span v-if="child.icon" class="material-icons me-1" aria-hidden="true">{{ child.icon }}</span>
                                        <span>{{ resolveText(child) }}</span>
                                    </router-link>
                                    <a
                                        v-else
                                        class="dropdown-item portal-navbar-dropdown-link"
                                        :href="child.href || '#'"
                                        :target="child.target"
                                        :rel="child.rel"
                                        @click="closeMenus">
                                        <span v-if="child.icon" class="material-icons me-1" aria-hidden="true">{{ child.icon }}</span>
                                        <span>{{ resolveText(child) }}</span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>
                    <slot name="left-extra" :is-mobile="true" :close-menu="closeMenus"></slot>
                    <div class="portal-navbar-mobile-right">
                        <slot name="right" :is-mobile="true" :close-menu="closeMenus"></slot>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<script>
export default {
    data: function() {
        return {
            width: Math.max(window.innerWidth || 0, document.documentElement?.clientWidth || 0, 1200),
            menuOpen: false,
            activeDropdownKey: null,
        };
    },
    mounted: function() {
        window.addEventListener('resize', this.onResize);
        document.addEventListener('click', this.handleDocumentClick);
    },
    beforeDestroy: function() {
        window.removeEventListener('resize', this.onResize);
        document.removeEventListener('click', this.handleDocumentClick);
    },
    methods: {
        hasChildren: function(item) {
            return Array.isArray(item?.children) && item.children.some((child) => child && child.visible !== false);
        },
        dropdownKey: function(item, index) {
            return item.key || item.text || `dropdown-${index}`;
        },
        isRouteActive: function(item) {
            return !!item?.route && this.$route?.name === item.route;
        },
        resolveText: function(item) {
            return item?.text || item?.label || item?.title || item?.name || item?.route || item?.href || 'Link';
        },
        toggleMenu: function() {
            this.menuOpen = !this.menuOpen;
            if (!this.menuOpen) {
                this.activeDropdownKey = null;
            }
        },
        toggleDropdown: function(key) {
            this.activeDropdownKey = this.activeDropdownKey === key ? null : key;
        },
        closeMenus: function() {
            this.menuOpen = false;
            this.activeDropdownKey = null;
        },
        onResize: function() {
            this.width = Math.max(window.innerWidth || 0, document.documentElement?.clientWidth || 0, 1200);
            if (!this.isMobile) {
                this.closeMenus();
            }
        },
        handleDocumentClick: function(event) {
            if (!event?.target) {
                return;
            }
            if (!event.target.closest('.portal-navbar-dropdown-wrap, .portal-navbar-mobile-item')) {
                this.activeDropdownKey = null;
            }
        },
    },
    watch: {
        '$route.fullPath': {
            handler: function() {
                this.closeMenus();
            },
        },
    },
    computed: {
        isMobile: function() {
            return this.width <= this.mobileBreakpoint;
        },
        visibleLinks: function() {
            return this.links.filter((item) => item && item.visible !== false);
        },
    },
    props: {
        show: { type: Boolean, default: true },
        brand: { type: Object, default: function() { return {}; } },
        links: { type: Array, default: function() { return []; } },
        mobileBreakpoint: { type: Number, default: 767 },
        navigationId: { type: String, default: 'navigation' },
    },
}
</script>
