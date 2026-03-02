<template>
    <nav v-if="show" class="portal-navbar mb-6 shadow">
        <div :class="containerClasses" class="portal-navbar-shell">
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
    props: {
        show: {
            type: Boolean,
            default: true,
        },
        brand: {
            type: Object,
            default: function() {
                return {};
            },
        },
        links: {
            type: Array,
            default: function() {
                return [];
            },
        },
        mobileBreakpoint: {
            type: Number,
            default: 767,
        },
        navigationId: {
            type: String,
            default: 'navigation',
        },
    },
    data: function() {
        return {
            width: Math.max(window.innerWidth || 0, document.documentElement?.clientWidth || 0, 1200),
            menuOpen: false,
            activeDropdownKey: null,
        };
    },
    computed: {
        isMobile: function() {
            return this.width <= this.mobileBreakpoint;
        },
        containerClasses: function() {
            return {
                container: !this.isMobile,
                'container-fluid': this.isMobile,
            };
        },
        visibleLinks: function() {
            return this.links.filter((item) => item && item.visible !== false);
        },
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
            if (!event?.target) return;
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
}
</script>

<style scoped>
.portal-navbar {
    position: relative;
    background: #fff;
}

.portal-navbar :deep(.navbar-brand),
.portal-navbar :deep(.nav-link),
.portal-navbar :deep(.dropdown-item),
.portal-navbar :deep(.navbar-text) {
    color: var(--color-text) !important;
}

.portal-navbar :deep(.nav-link .material-icons),
.portal-navbar :deep(.dropdown-item .material-icons) {
    color: inherit !important;
}

.portal-navbar :deep(.nav-link.active),
.portal-navbar :deep(.dropdown-item.active) {
    color: #fff !important;
}

.portal-navbar :deep(.nav-link.active .material-icons),
.portal-navbar :deep(.dropdown-item.active .material-icons) {
    color: #fff !important;
}


.portal-navbar :deep(a),
.portal-navbar :deep(button),
.portal-navbar :deep(.nav-link),
.portal-navbar :deep(.dropdown-item),
.portal-navbar :deep([role='button']) {
    cursor: pointer;
}

.portal-navbar-shell {
    width: 100%;
}

.portal-navbar-top {
    min-height: 56px;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.portal-navbar-brand {
    flex: 0 0 auto;
}

.portal-navbar-toggle {
    display: none;
    background: transparent;
    border: 1px solid var(--color-primary);
    color: var(--color-primary);
    border-radius: 4px;
    padding: 0;
    line-height: 1;
    font-size: 1.2rem;
    width: 36px;
    height: 36px;
    align-items: center;
    justify-content: center;
}

.portal-navbar-desktop {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex: 1 1 auto;
    min-width: 0;
    gap: 1rem;
}

.portal-navbar-mobile-controls {
    display: none;
    align-items: center;
    gap: 0.5rem;
    margin-left: auto;
}

.portal-navbar-mobile-controls .profile-image {
    margin: 0;
    max-width: 32px;
    max-height: 32px;
}

.portal-navbar-left,
.portal-navbar-right {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    min-width: 0;
}

.portal-navbar-left {
    flex: 1 1 auto;
    flex-wrap: wrap;
}

.portal-navbar-right {
    flex: 0 0 auto;
}

.portal-navbar-item {
    position: relative;
    flex: 0 0 auto;
}

.portal-navbar-link,
.portal-navbar-dropdown-link {
    display: inline-flex;
    align-items: center;
    min-width: max-content !important;
    width: auto !important;
    white-space: nowrap;
    font-size: 1rem;
    line-height: 1.25;
    color: var(--color-text) !important;
}

.portal-navbar-dropdown .portal-navbar-dropdown-link,
.portal-navbar-mobile-children .portal-navbar-dropdown-link {
    display: flex !important;
    width: 100% !important;
    min-width: 0 !important;
}

.portal-navbar-link .material-icons,
.portal-navbar-dropdown-link .material-icons {
    color: inherit !important;
}

.portal-navbar-dropdown-wrap {
    position: relative;
}

.portal-navbar-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 2px);
    left: 0;
    background: #fff;
    color: #2b2b2b;
    border-radius: 4px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
    padding: 0.25rem 0;
    min-width: 13rem;
    z-index: 20;
}

.portal-navbar-dropdown .portal-navbar-dropdown-link:hover,
.portal-navbar-dropdown .portal-navbar-dropdown-link:focus,
.portal-navbar-mobile .portal-navbar-link:hover,
.portal-navbar-mobile .portal-navbar-link:focus,
.portal-navbar-mobile .portal-navbar-link:active,
.portal-navbar-mobile .portal-navbar-dropdown-link:hover,
.portal-navbar-mobile .portal-navbar-dropdown-link:focus,
.portal-navbar-mobile .portal-navbar-dropdown-link:active {
    background-color: rgba(0, 0, 0, 0.10) !important;
    color: #fff;
}

.dropdown-divider {
    border-top: 2px solid var(--color-primary);
    margin: 1rem 0 1.25rem;
    opacity: 1;
}

.portal-navbar.bg--primary .dropdown-divider {
    border-top-color: #fff;
    opacity: 1;
}

.portal-navbar-dropdown.show {
    display: block;
}

.portal-navbar-mobile {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1000;
    background: #fff;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    padding: 0.5rem 0.75rem 0.75rem;
}

.portal-navbar-mobile.show {
    display: block;
}

.portal-navbar-mobile-links,
.portal-navbar-mobile-item,
.portal-navbar-mobile-right {
    width: 100%;
}

.portal-navbar-mobile-children {
    display: none;
    padding-left: 0;
}

.portal-navbar-mobile-children.show {
    display: block;
}

.portal-navbar-mobile-section-title {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.5rem 0;
    color: var(--color-text) !important;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: default !important;
}

@media (min-width: 768px) {
    .portal-navbar-mobile {
        display: none !important;
    }
}

@media (max-width: 767px) {
    .portal-navbar-top {
        min-height: 56px;
        padding-bottom: 0;
        margin-bottom: 0.5rem;
        align-items: center;
    }

    .portal-navbar-mobile-controls {
        display: inline-flex;
    }

    .portal-navbar-toggle {
        display: inline-flex;
    }

    .portal-navbar-desktop {
        display: none;
    }

    .portal-navbar-link,
    .portal-navbar-dropdown-link {
        width: 100% !important;
        min-width: 0 !important;
        color: var(--color-text) !important;
    }

    .portal-navbar-mobile .portal-navbar-dropdown-link {
        padding: 0 !important;
    }

    .portal-navbar-mobile .nav-link,
    .portal-navbar-mobile .dropdown-item,
    .portal-navbar-mobile .navbar-text {
        color: var(--color-text) !important;
    }

    .portal-navbar-mobile .nav-link .material-icons,
    .portal-navbar-mobile .dropdown-item .material-icons {
        color: inherit !important;
    }

    .portal-navbar-dropdown {
        position: static;
        box-shadow: none;
        min-width: 100%;
    }
}
</style>