<template>
    <div id="recent-login-form">
        <cz-table :headers="headers" :url="url" ref="userTable" disable-search>
            <template #header>
                <h3 class="card-title">Recent Login History</h3>
                <h6 class="card-subtitle mb-0 text-muted">See when and where this account was last accessed.</h6>
            </template>
            <template #last_used_at="{ ip_address, last_used_at }">
                <div class="cz-table-data">
                    {{ ip_address }}
                    <p class="note mb-0">Last Used: {{ last_used_at == null ? 'Never' : formatDate(last_used_at) }}</p>
                </div>
            </template>
            <template #remember_until="{ id, remember, remember_until }">
                <div v-if="remember" class="cz-table-data" :class="{ 'd-flex': !breakpoint('sm') }">
                    <div :class="{ 'display-inline': !breakpoint('sm'), 'mr-2': !breakpoint('sm') }" style="flex: 10;">{{ formatDate(remember_until) }}</div>
                    <template v-if="$store.getters.hasPermission('clear-user-logins')">
                        <button type="button"
                            v-if="typeof(forgetting[id]) == 'undefined' || !forgetting[id].active"
                            @click="setup(id)"
                            class="button button--danger"
                            :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Forget</button>
                        <button type="button"
                            v-else-if="forgetting[id].active ?? false"
                            @click="proceed(id)"
                            :disabled="submitting[id] ?? false"
                            class="button button--danger"
                            :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Remove</button>
                    </template>
                </div>
                <div v-else class="cz-table-data">No</div>
            </template>
        </cz-table>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: [],
            forgetting: [],
            headers: [
                { name: 'IP Address', value: 'last_used_at', width: '320px' },
                { name: 'Remembered', value: 'remember_until' },
            ]
        };
    },
    methods: {
        reset: function() {
            this.$refs.userTable.query();
        },
        setup: function(id) {
            this.forgetting[id] = {
                active: true,
                timeout: setTimeout(() => {
                    this.forgetting[id] = false;
                }, 2000),
            };
        },
        proceed: function(id) {
            this.submitting[id] = true;
            // Safely clear the previous timeout if it exists
            if (this.forgetting[id]?.timeout) {
                clearTimeout(this.forgetting[id].timeout);
            }
            axios.delete(`/ip/${id}/forget`).then(() => {
                setTimeout(() => {
                    this.$refs.userTable.query();
                }, 1000);
            }).finally(() => {
                setTimeout(() => {
                    this.submitting[id] = false;
                    this.forgetting[id] = false;
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