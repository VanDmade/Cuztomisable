<template>
    <div id="recent-login-form">
        <tablelify :headers="headers" :url="url" ref="userTable" disable-search wait>
            <template #header>
                <h3 class="card-title">Recent Login History</h3>
                <h6 class="card-subtitle mb-0 text-muted">See when and where this account was last accessed.</h6>
            </template>
            <template #last_used_at="{ ip_address, last_used_at }">
                <div class="tablelify-data">
                    {{ ip_address }}
                    <p class="note mb-0">Last Used: {{ last_used_at == null ? 'Never' : formatDate(last_used_at) }}</p>
                </div>
            </template>
            <template #remember_until="{ id, remember, remember_until }">
                <div v-if="remember" class="tablelify-data" :class="{ 'd-flex': !breakpoint('sm') }">
                    <div :class="{ 'display-inline': !breakpoint('sm'), 'mr-2': !breakpoint('sm') }" style="flex: 10;">{{ formatDate(remember_until) }}</div>
                    <template v-if="$store.getters.hasPermission('clear-user-logins')">
                        <button type="button"
                            v-if="typeof(forgetting[id]) == 'undefined' || !forgetting[id].active"
                            @click="setup('forget', id)"
                            class="button button--danger"
                            :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Forget</button>
                        <button type="button"
                            v-else-if="forgetting[id].active ?? false"
                            @click="proceed('forget', id)"
                            :disabled="submitting[id] ?? false"
                            class="button button--danger"
                            :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Remove</button>
                    </template>
                </div>
                <div v-else class="tablelify-data" :class="{ 'd-flex': !breakpoint('sm') }">
                    <div :class="{ 'display-inline': !breakpoint('sm'), 'mr-2': !breakpoint('sm') }" style="flex: 10;">No</div>
                    <template v-if="$store.getters.hasPermission('clear-user-logins')">
                        <button type="button"
                            v-if="typeof(deleting[id]) == 'undefined' || !deleting[id].active"
                            @click="setup('delete', id)"
                            class="button button--danger"
                            :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Delete</button>
                        <button type="button"
                            v-else-if="deleting[id].active ?? false"
                            @click="proceed('delete', id)"
                            :disabled="submitting[id] ?? false"
                            class="button button--danger"
                            :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Remove</button>
                    </template>
                </div>
            </template>
        </tablelify>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: [],
            forgetting: [],
            deleting: [],
            headers: [
                { name: 'IP Address', value: 'last_used_at', width: '225px' },
                { name: 'Remembered', value: 'remember_until' },
            ]
        };
    },
    methods: {
        reset: function() {
            this.$refs.userTable.query();
        },
        setup: function(type, id) {
            const isDelete = type == 'delete';
            const key = isDelete ? 'deleting' : 'forgetting';
            this[key][id] = {
                active: true,
                timeout: setTimeout(() => {
                    this[key][id] = false;
                }, 2000),
            };
        },
        proceed: function(type, id) {
            this.submitting[id] = true;
            const isDelete = type == 'delete';
            const key = isDelete ? 'deleting' : 'forgetting';
            // Safely clear the previous timeout if it exists
            if (this[key][id]?.timeout) {
                clearTimeout(this[key][id].timeout);
            }
            const url = `/ip/${id}` + (!isDelete ? '/forget' : '');
            axios.delete(url).then(() => {
                setTimeout(() => {
                    this.$refs.userTable.query();
                }, 1000);
            }).finally(() => {
                setTimeout(() => {
                    this.submitting[id] = false;
                    this[key][id] = false;
                }, 1000);
            });
        }
    },
    computed: {
        url: function() {
            return `/user/${this.user}/ips`;
        }
    },
    props: {
        user: { type: [String, Number] }
    }
}
</script>
<style>
    .display-inline {
        display: inline;
    }
</style>