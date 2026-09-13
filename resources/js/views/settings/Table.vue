<template>
    <div class="page table-page container-fluid">
        <h3 class="card-title">Settings</h3>
        <h6 class="card-subtitle mb-6 text-muted">
            Manage the app's editable key/value settings. To add a new one, add its key to the
            <code>settings</code> array in <code>config/cuztomisable.php</code> - it'll show up here
            automatically. By default, managing any setting requires the <code>manage-settings</code>
            permission; to restrict a specific key to its own permission instead, create one named
            <code>settings-{key}</code> (e.g. <code>settings-cookie_message</code>).
        </h6>
        <cz-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <div v-else class="cz-settings-list">
            <div v-if="settings.length === 0" class="text-center note">No settings are registered yet.</div>
            <div v-for="setting in settings" :key="setting.key" class="cz-settings-row">
                <div class="cz-settings-row-info">
                    <h6 class="card-title mb-1">{{ label(setting.key) }}</h6>
                    <p class="note mb-0">{{ setting.value || 'Not set yet' }}</p>
                </div>
                <button type="button"
                    class="button button--secondary"
                    :disabled="!setting.can_manage"
                    @click="edit(setting.key)">Edit</button>
            </div>
        </div>
        <cz-modal ref="settingModal" modal-width="700px">
            <settings-form :setting-key="activeKey" v-on:close="$refs.settingModal.close()" v-on:message="setMessage" v-on:redraw="load" />
        </cz-modal>
    </div>
</template>
<script>
import SettingsForm from './Form.vue';
export default {
    data: function() {
        return {
            settings: [],
            loading: true,
            activeKey: null,
        };
    },
    created: function() {
        this.load();
    },
    methods: {
        load: function() {
            this.loading = true;
            axios.get('/settings').then(({ data }) => {
                this.settings = data.settings ?? [];
            }).finally(() => {
                this.loading = false;
            });
        },
        edit: function(key) {
            this.activeKey = key;
            this.$refs.settingModal.open();
        },
        label: function(key) {
            return key.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
        },
        setMessage: function(message) {
            this.$message.push({ ...(message ?? {}), color: message?.error ? 'danger' : message?.color });
        },
    },
    components: {
        'settings-form': SettingsForm,
    },
}
</script>
