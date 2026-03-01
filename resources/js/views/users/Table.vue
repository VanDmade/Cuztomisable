<template>
    <div class="page" :class="{ 'container': breakpoint('lg'), 'container-fluid': breakpoint('md') || breakpoint('sm') }">
        <tablelify :headers="headers" :url="url" ref="userTable">
            <template #phone="{ country_code, phone, phone_verified_at }">
                <div class="tablelify-data">
                    <div v-if="phone == null" class="not-available">N/A</div>
                    <span v-else>
                        <i v-if="phone_verified_at != null" class="material-icons mr-1 color--success fm-phone-verified">verified</i>{{ formatPhone(phone, country_code) }}
                    </span>
                </div>
            </template>
            <template #last_used_at="{ last_used_at }">
                <div class="tablelify-data">
                    {{ last_used_at == null ? 'Never' : formatDate(last_used_at) }}
                </div>
            </template>
            <template #name_with_email="{ name, email, email_verified_at }">
                <div class="tablelify-data d-flex">
                    <img :src="$url+'cuztomisable/profile.png'" style="text-align: center; border-radius: 50px;">
                    <div class="ml-2">
                        <div>{{ name }}</div>
                        <p class="note mb-0">
                            <i v-if="email_verified_at != null" class="material-icons mr-1 color--success">verified</i>
                            <a class="color--link" :href="'mailto:'+email">{{ email }}</a>
                        </p>
                    </div>
                </div>
            </template>
            <template #status="item">
                <div class="tablelify-data enabled-overflow" :class="{ 'text-center': !breakpoint('sm') }" style="cursor: help;">
                    <span v-if="item.admin" class="tooltip-wrapper ml-1 mr-1">
                        <i class="material-icons">admin_panel_settings</i>
                        <span class="tooltip-text">Admin</span>
                    </span>
                    <span v-else class="tooltip-wrapper ml-1 mr-1">
                        <i class="material-icons">person</i>
                        <span class="tooltip-text">Basic User</span>
                    </span>
                    <span v-if="item.locked" class="tooltip-wrapper ml-1 mr-1">
                        <i class="material-icons">lock</i>
                        <span class="tooltip-text">Locked</span>
                    </span>
                    <span v-if="item.mfa" class="tooltip-wrapper ml-1 mr-1">
                        <i class="material-icons">verified_user</i>
                        <span class="tooltip-text">MFA Enabled</span>
                    </span>
                </div>
            </template>
            <template #actions="item">
                <div class="tablelify-data text-center">
                    <div :class="{ 'row': breakpoint('sm') }">
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="edit(item.id)"
                                class="button button--secondary"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">Edit</button>
                        </div>
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="id = item.id; $refs.deleteUserModal.open();"
                                class="button button--danger"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small ml-1': !breakpoint('sm') }">Delete</button>
                        </div>
                    </div>
                </div>
            </template>
        </tablelify>
        <fm-modal ref="deleteUserModal" modal-width="380px">
            <h3 class="card-title">Delete User?</h3>
            <h6 class="card-subtitle mb-6 text-muted">Send this user on a one-way trip to Deletionville.</h6>
            <div class="row">
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--danger button--block mb-0"
                        :disabled="submitting"
                        @click="remove">Yes</button>
                </div>
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--secondary button--block mb-0"
                        :disabled="submitting"
                        @click="$refs.deleteUserModal.close()">No</button>
                </div>
            </div>
        </fm-modal>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            id: null,
            submitting: false,
            url: '/users',
            headers: [
                { name: 'Name', value: 'name_with_email', width: '340px' },
                { name: 'Phone', value: 'phone', sortable: false, width: '20%' },
                { name: 'Last Accessed', value: 'last_used_at', width: '20%' },
                { name: 'Status', value: 'status', sortable: false, width: '140px' },
                { name: '', value: 'actions', sortable: false, width: '150px' },
            ]
        }
    },
    created: function() {
        if (!this.$cuztomisable.login_with.email && !this.$cuztomisable.login_with.phone) {
            const nameIndex = this.headers.findIndex(h => h.value === 'name');
            if (nameIndex !== -1) {
                this.headers.splice(nameIndex + 1, 0, { name: 'Username', value: 'username' });
            }
        }
    },
    methods: {
        edit: function(id) {
            this.$router.push({ name: 'user.form', params: { id: id }});
        },
        remove: function() {
            this.submitting = true;
            axios.delete(`/user/${this.id}`).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.$refs.userTable.query();
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.$refs.deleteUserModal.close();
                }, 250);
                setTimeout(() => {
                    this.submitting = false;
                }, 500);
            });
        },
        setMessage: function(message) {
            this.$message.push({ ...(message ?? {}), color: message?.error ? 'danger' : message?.color });
        },
    },
}
</script>