<template>
    <div class="page table-page container-fluid">
        <cz-table :headers="headers" :url="url" ref="inviteTable">
            <template #header>
                <button
                    @click="add()"
                    class="button button--primary"
                    :class="{ 'button--block mb-4': breakpoint('sm') }">Invite User</button>
            </template>
            <template #name="{ name, email, phone }">
                <div class="cz-table-data">
                    {{ name }}
                    <p class="note mb-0">
                        <a v-if="email != null" class="color--link" :href="'mailto:'+email">{{ email }}</a>
                        <span v-else>{{ formatPhone(phone) }}</span>
                    </p>
                </div>
            </template>
            <template #creator="{ creator, creator_email}">
                <div class="cz-table-data">
                    {{ creator }}
                    <p class="note mb-0">
                        <a class="color--link" :href="'mailto:'+creator_email">{{ creator_email }}</a>
                    </p>
                </div>
            </template>
            <template #expires_at="{ expires_at, expired_ago }">
                <div class="cz-table-data">
                    <span v-if="expired_ago != null">{{ expired_ago }}</span>
                    <span v-else>{{ expires_at == null ? 'Never' : formatDate(expires_at) }}</span>
                </div>
            </template>
            <template #sent_at="{ sent_at }">
                <div class="cz-table-data">
                    {{ sent_at == null ? 'Never' : formatDate(sent_at) }}
                </div>
            </template>
            <template #used="{ used_at }">
                <div class="cz-table-data">
                    <div v-if="used_at == null">No</div>
                    <div v-else>
                        Yes
                        <p class="note mb-0">{{ formatDate(used_at) }}</p>
                    </div>
                </div>
            </template>
            <template #actions="item">
                <div class="cz-table-data text-center">
                    <div :class="{ 'row': breakpoint('sm') }">
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="send(item.id)"
                                class="button button--secondary"
                                :disabled="isSending(item.id)"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small mr-1': !breakpoint('sm') }">{{ buttonLabel(item.id, typeof(sending[item.id]) == 'undefined' ? item.resend_in : null) }}</button>
                        </div>
                        <div :class="{ 'col col-sm-6': breakpoint('sm'), 'display-inline': !breakpoint('sm') }">
                            <button
                                @click="id = item.id; $refs.deleteInvitationModal.open();"
                                class="button button--danger"
                                :disabled="submitting"
                                :class="{ 'button--block': breakpoint('sm'), 'button--small ml-1': !breakpoint('sm') }">Delete</button>
                        </div>
                    </div>
                </div>
            </template>
        </cz-table>
        <cz-modal ref="inviteModal" modal-width="425px">
            <invite-form v-on:close="$refs.inviteModal.close()" v-on:message="setMessage" v-on:redraw="$refs.inviteTable.query()" />
        </cz-modal>
        <cz-modal ref="deleteInvitationModal" modal-width="380px">
            <h3 class="card-title">Delete Registration Link?</h3>
            <h6 class="card-subtitle mb-6 text-muted">Cancel this invite and prevent registration.</h6>
            <div class="row">
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--primary button--block mb-0"
                        :disabled="submitting"
                        @click="remove">Yes</button>
                </div>
                <div class="col col-md-6 col-12">
                    <button type="button"
                        class="button button--secondary button--block mb-0"
                        :disabled="submitting"
                        @click="$refs.deleteInvitationModal.close()">No</button>
                </div>
            </div>
        </cz-modal>
    </div>
</template>
<script>
import InviteForm from './Form.vue';
export default {
    data: function() {
        return {
            id: null,
            submitting: false,
            sending: [],
            cooldowns: [],
            countdownTimers: [],
            url: '/invites',
            headers: [
                { name: 'Name', value: 'name' },
                { name: 'Created By', value: 'creator' },
                { name: 'Expires At', value: 'expires_at', width: '250px' },
                { name: 'Used', value: 'used', width: '200px' },
                { name: '', value: 'actions', sortable: false, width: '200px' },
            ]
        };
    },
    methods: {
        add: function() {
            this.$refs.inviteModal.open();
        },
        send: function(id) {
            this.sending[id] = true;
            axios.post(`/invite/${id}/send`).then(({ data }) => {
                this.$refs.inviteTable.query();
                this.$message.push({ text: data.message });
                this.countdown(id, data.resend_in);
            }).catch((error) => {
                setTimeout(() => {
                    this.sending[id] = false;
                }, 500);
            });
        },
        remove: function() {
            this.submitting = true;
            axios.delete(`/invite/${this.id}`).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.$refs.inviteTable.query();
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.$refs.deleteInvitationModal.close();
                }, 250);
                setTimeout(() => {
                    this.submitting = false;
                }, 500);
            });
        },
        isSending: function(id) {
            return typeof(this.sending[id]) !== false ? this.sending[id] : false;
        },
        countdown: function(id, timeLeft) {
            clearInterval(this.countdownTimers[id]);
            this.sending[id] = true;
            this.cooldowns[id] = timeLeft;
            // Start countdown
            this.countdownTimers[id] = setInterval(() => {
                if (this.cooldowns[id] > 0) { 
                    this.cooldowns[id]--;
                } else {
                    clearInterval(this.countdownTimers[id]);
                    this.sending[id] = false;
                    delete this.countdownTimers[id];
                }
            }, 1000);
        },
        buttonLabel: function(id, timeLeft = null) {
            if (timeLeft != null && !this.isSending(id) && timeLeft > 0) {
                this.countdown(id, timeLeft);
            }
            return this.sending[id] ?
                (typeof(this.cooldowns[id]) == 'undefined' ?
                    'Resending...' : `${this.cooldowns[id]} seconds`) : 'Resend';
        },
        setMessage: function(message) {
            this.$message.push({ ...(message ?? {}), color: message?.error ? 'danger' : message?.color });
        },
    },
    beforeDestroy: function() {
        Object.values(this.countdownTimers).forEach(clearInterval);
    },
    components: {
        'invite-form': InviteForm,
    },
}
</script>
