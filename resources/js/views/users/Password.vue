<template>
    <div id="change-password-form">
        <template v-if="admin && !isSelf">
            <div id="password-admin-controls" class="cz-account-row">
                <div>
                    <h6 class="card-title mb-1">Admin Controls</h6>
                    <p class="note mb-0">Sends the user a temporary password by email, which they'll be required to change on their next login.</p>
                </div>
                <button type="button"
                    @click="send"
                    class="button button--primary"
                    :disabled="submitting || sendCooldownRemaining > 0">{{ sendCooldownRemaining > 0 ? `Sent - retry in ${formatCooldown(sendCooldownRemaining)}` : 'Send' }}</button>
            </div>
            <div class="cz-account-row mt-8">
                <div>
                    <h6 class="card-title mb-1">Reset Login Attempts</h6>
                    <p class="note mb-0">Clears failed login attempts and unlocks the account.</p>
                </div>
                <button type="button" class="button button--primary" :disabled="resettingAttempts" @click="resetAttempts">Reset Attempts</button>
            </div>
            <div v-if="!emailVerified" class="cz-account-row mt-8">
                <div>
                    <h6 class="card-title mb-1">Resend Email Verification</h6>
                    <p class="note mb-0">This user hasn't verified their email address yet.</p>
                </div>
                <button type="button" class="button button--primary" :disabled="resendingEmail" @click="resendEmailVerification">Resend</button>
            </div>
            <div v-if="hasPhone && !phoneVerified" class="cz-account-row mt-8">
                <div>
                    <h6 class="card-title mb-1">Resend Phone Verification</h6>
                    <p class="note mb-0">This user hasn't verified their phone number yet.</p>
                </div>
                <button type="button" class="button button--primary" :disabled="resendingPhone" @click="resendPhoneVerification">Resend</button>
            </div>
        </template>
        <div v-if="isSelf" id="password-controls" class="cz-account-row">
            <div>
                <h6 class="card-title mb-1">Change Password</h6>
                <p class="note mb-0">Set a new password for this account.</p>
            </div>
            <button type="button" class="button button--primary" @click="$refs.changePasswordModal.open()">Change Password</button>
        </div>
        <cz-modal ref="changePasswordModal" modal-width="450px" @close="reset">
            <h3 class="card-title">Change Password</h3>
            <h6 class="card-subtitle mb-6 text-muted">Set a new password for this account.</h6>
            <cz-form ref="changePasswordForm" :form="form" @save="change">
                <cz-input
                    label="Current Password"
                    v-model="form.current"
                    type="password"
                    autocomplete="current-password"
                    :errors="errors.current"
                    :disabled="submitting" />
                <cz-input
                    label="New Password"
                    v-model="form.new"
                    type="password"
                    autocomplete="new-password"
                    :errors="errors.new"
                    :disabled="submitting" />
                <requirements :password="form.new" v-on:completed="completed" class="mb-4"></requirements>
                <button type="submit"
                    class="button button--primary button--block"
                    :disabled="submitting || !passwordRequirementsMet">Change</button>
            </cz-form>
        </cz-modal>
    </div>
</template>
<script>
import PasswordRequirements from '../../components/PasswordRequirements.vue';
function initialize() {
    return {
        submitting: false,
        passwordRequirementsMet: false,
        errors: [],
        form: {
            current: '',
            new: '',
        },
    };
}
export default {
    data: function() {
        return {
            ...initialize(),
            // Local override so the cooldown starts immediately after a successful send rather
            // than waiting on the parent to refetch the user. Server-driven (change_password_sent_at
            // + config resend_after) so a page refresh doesn't reset it either.
            sentAtOverride: null,
            now: Date.now(),
            tick: null,
            resettingAttempts: false,
            resendingEmail: false,
            resendingPhone: false,
        };
    },
    mounted: function() {
        this.tick = setInterval(() => { this.now = Date.now(); }, 1000);
    },
    beforeUnmount: function() {
        clearInterval(this.tick);
    },
    methods: {
        reset: function() {
            Object.assign(this.$data, initialize());
        },
        send: function() {
            this.submitting = true;
            axios.post(`/user/${this.user}/send/password`).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.sentAtOverride = new Date().toISOString();
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1000);
            });
        },
        change: function() {
            this.submitting = true;
            this.errors = [];
            let formData = new FormData();
            formData.append('current', this.form.current ?? '');
            formData.append('new', this.form.new ?? '');
            axios.post('/user/change/password', formData).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.reset();
                this.$refs.changePasswordModal.close();
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1000);
            });
        },
        resetAttempts: function() {
            this.resettingAttempts = true;
            axios.patch(`/user/${this.user}/attempts`).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.$emit('reload');
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.resettingAttempts = false;
                }, 500);
            });
        },
        resendEmailVerification: function() {
            this.resendingEmail = true;
            axios.post(`/user/${this.user}/verify/email`).then(({ data }) => {
                this.$message.push({ text: data.message });
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.resendingEmail = false;
                }, 500);
            });
        },
        resendPhoneVerification: function() {
            this.resendingPhone = true;
            axios.post(`/user/${this.user}/verify/phone`).then(({ data }) => {
                this.$message.push({ text: data.message });
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.resendingPhone = false;
                }, 500);
            });
        },
        completed: function(value) {
            this.passwordRequirementsMet = value;
        },
        formatCooldown: function(seconds) {
            const m = Math.floor(seconds / 60);
            const s = seconds % 60;
            return m > 0 ? `${m}m ${s}s` : `${s}s`;
        },
    },
    computed: {
        isSelf: function() {
            return this.user == this.$store.state.user.id;
        },
        dirty: function() {
            return !!(this.form.current || this.form.new);
        },
        sendCooldownRemaining: function() {
            const sentAt = this.sentAtOverride ?? this.changePasswordSentAt;
            if (!sentAt) {
                return 0;
            }
            const resendAfter = this.$cuztomisable?.administrator?.temporary_password?.resend_after ?? 300;
            const elapsed = Math.floor((this.now - new Date(sentAt).getTime()) / 1000);
            return Math.max(0, resendAfter - elapsed);
        },
    },
    props: {
        admin: { type: Boolean, default: false },
        user: { type: [Number, String], default: null },
        changePasswordSentAt: { type: String, default: null },
        emailVerified: { type: Boolean, default: true },
        phoneVerified: { type: Boolean, default: true },
        hasPhone: { type: Boolean, default: false },
    },
    components: {
        'requirements': PasswordRequirements,
    }
}
</script>