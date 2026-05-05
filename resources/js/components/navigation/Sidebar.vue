<template>
    <aside class="app-sidebar">
        <nav class="sidebar-nav">
            <template v-if="navigation && navigation.length">
                <router-link
                    v-for="(item, index) in navigation"
                    :key="index"
                    :to="item.path || { name: item.route }"
                    class="sidebar-link"
                    :class="{ 'sidebar-link--active': $route.name === item.route }"
                    :data-tooltip="item.text"
                    @click.native="$emit('navigate')">
                    <span class="material-icons" aria-hidden="true">{{ item.icon }}</span>
                </router-link>
            </template>
            <template v-if="$store.getters.hasPermission('view-users|manage-users|invite-users|manage-roles-permissions')">
                <div class="sidebar-divider"></div>
                <router-link
                    v-if="$store.getters.hasPermission('view-users|manage-users')"
                    :to="{ name: 'users' }"
                    class="sidebar-link"
                    :class="{ 'sidebar-link--active': $route.name === 'users' }"
                    data-tooltip="Users">
                    <span class="material-icons" aria-hidden="true">group</span>
                </router-link>
                <router-link
                    v-if="$store.getters.hasPermission('invite-users')"
                    :to="{ name: 'invites' }"
                    class="sidebar-link"
                    :class="{ 'sidebar-link--active': $route.name === 'invites' }"
                    data-tooltip="Invitations">
                    <span class="material-icons" aria-hidden="true">mail</span>
                </router-link>
                <router-link
                    v-if="$store.getters.hasPermission('manage-roles-permissions')"
                    :to="{ name: 'roles' }"
                    class="sidebar-link"
                    :class="{ 'sidebar-link--active': $route.name === 'roles' }"
                    data-tooltip="Roles">
                    <span class="material-icons" aria-hidden="true">security</span>
                </router-link>
                <router-link
                    v-if="$store.getters.hasPermission('manage-roles-permissions')"
                    :to="{ name: 'permissions' }"
                    class="sidebar-link"
                    :class="{ 'sidebar-link--active': $route.name === 'permissions' }"
                    data-tooltip="Permissions">
                    <span class="material-icons" aria-hidden="true">verified_user</span>
                </router-link>
            </template>
        </nav>
    </aside>
</template>

<script>
export default {
    props: {
        navigation: {
            type: Array,
            default: () => [],
        },
    },
}
</script>

<style scoped>
.app-sidebar {
    width: 56px;
    background: #fff;
    border-right: 1px solid #e8e8e8;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 8px 0;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 100%;
    gap: 2px;
    padding: 0 8px;
}

.sidebar-link {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    margin: 4px 0;
    color: #6b7280;
    text-decoration: none;
    transition: background 0.15s, color 0.15s;
    flex-shrink: 0;
}

.sidebar-link:hover {
    background: var(--color-primary-ring);
    color: var(--color-primary);
}

.sidebar-link--active {
    background: var(--color-primary-ring);
    color: var(--color-primary);
}

.sidebar-link .material-icons {
    font-size: 22px;
}

/* Tooltip */
.sidebar-link::after {
    content: attr(data-tooltip);
    position: absolute;
    left: calc(100% + 10px);
    top: 50%;
    transform: translateY(-50%);
    background: #111827;
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    white-space: nowrap;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.15s;
    z-index: 9999;
}

.sidebar-link:hover::after {
    opacity: 1;
}

.sidebar-divider {
    width: 32px;
    height: 1px;
    background: #e5e7eb;
    margin: 4px 0;
}
</style>
