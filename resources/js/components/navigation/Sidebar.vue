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
