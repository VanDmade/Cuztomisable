<template>
    <div class="page container-fluid">
        <cz-loading :loading="loading || $store.state.loading"></cz-loading>
        <template v-if="!loading">
            <div class="card pa-6 mb-4 cz-user-summary">
                <cz-image
                    :src="imageUrl"
                    alt="User Image"
                    img-class="cz-image cz-user-summary-image"
                    v-model="form.image"
                    uploader />
                <div class="cz-user-summary-info">
                    <div class="cz-user-summary-header">
                        <div class="cz-user-summary-header-text">
                            <h5 class="card-title mb-1 cz-user-summary-name">
                                {{ form.name || 'New User' }}
                                <span v-if="form.locked" class="cz-user-badge cz-user-badge--danger">Locked</span>
                            </h5>
                            <div class="cz-user-summary-contacts">
                                <span v-if="form.email" class="cz-user-contact-wrap">
                                    <span v-if="form.disable_emails" class="material-icons cz-user-contact-icon cz-user-contact-icon--danger" title="Emails disabled">block</span>
                                    <a :href="'mailto:'+form.email"
                                        class="cz-user-contact-link"
                                        :class="{ 'cz-user-contact-link--danger': form.disable_emails }"
                                        :title="form.disable_emails ? 'Emails disabled' : null">{{ form.email }}</a>
                                    <button v-if="form.disable_emails && canManageContactPrefs"
                                        type="button"
                                        class="cz-user-contact-reset"
                                        @click="openResetContactModal('email')">Reset</button>
                                </span>
                                <span v-if="form.phone?.number" class="cz-user-contact-wrap">
                                    <span v-if="form.phone?.disable_messages" class="material-icons cz-user-contact-icon cz-user-contact-icon--danger" title="Texts disabled">block</span>
                                    <a :href="'tel:'+form.phone.number"
                                        class="cz-user-contact-link"
                                        :class="{ 'cz-user-contact-link--danger': form.phone?.disable_messages }"
                                        :title="form.phone?.disable_messages ? 'Texts disabled' : null">{{ form.phone.number }}</a>
                                    <button v-if="form.phone?.disable_messages && canManageContactPrefs"
                                        type="button"
                                        class="cz-user-contact-reset"
                                        @click="openResetContactModal('phone')">Reset</button>
                                </span>
                                <span v-if="!form.email && !form.phone?.number" class="note">—</span>
                            </div>
                        </div>
                        <button v-if="canToggleLock"
                            type="button"
                            class="button button--secondary"
                            :disabled="lockToggling"
                            @click="toggleLocked">
                            <span class="material-icons">{{ form.locked ? 'lock_open' : 'lock' }}</span>
                            {{ form.locked ? 'Unlock Account' : 'Lock Account' }}
                        </button>
                    </div>
                    <div class="cz-user-summary-meta">
                        <span v-if="form.mfa" class="cz-user-badge cz-user-badge--success">MFA Enabled</span>
                    </div>
                    <p class="note mb-0 cz-user-summary-line">Member since {{ form.created_at ? formatDate(form.created_at) : '—' }}</p>
                    <p class="note mb-0 cz-user-summary-line">Last login: {{ form.last_login_at ? formatDate(form.last_login_at) : 'Never' }}</p>
                </div>
            </div>
            <cz-modal ref="resetContactModal" modal-width="400px" @close="resetContactType = null">
                <h3 class="card-title">Re-enable {{ resetContactType === 'phone' ? 'Text Messages' : 'Emails' }}</h3>
                <h6 class="card-subtitle mb-6 text-muted">
                    This will re-enable {{ resetContactType === 'phone' ? 'text messages' : 'emails' }} for this account.
                </h6>
                <button type="button"
                    class="button button--primary button--block"
                    :disabled="resettingContact"
                    @click="confirmResetContact">Confirm</button>
            </cz-modal>
            <div class="cz-tabs-row mb-4">
                <div class="cz-tabs">
                    <button type="button"
                        class="cz-tab"
                        :class="{ active: tab === 'details' }"
                        @click="tab = 'details'">Details<span v-if="detailsDirty" class="cz-tab-dirty" title="Unsaved changes"></span></button>
                    <button type="button"
                        v-if="securityTabVisible"
                        class="cz-tab"
                        :class="{ active: tab === 'security' }"
                        @click="tab = 'security'">Security<span v-if="securityDirty || accessDirty" class="cz-tab-dirty" title="Unsaved changes"></span></button>
                    <button type="button"
                        v-if="isMineOrHasPermission('view-user-logins')"
                        class="cz-tab"
                        :class="{ active: tab === 'logins' }"
                        @click="tab = 'logins'">Login History</button>
                    <button type="button"
                        v-if="logsTabVisible"
                        class="cz-tab"
                        :class="{ active: tab === 'logs' }"
                        @click="tab = 'logs'">Logs</button>
                </div>
                <button type="button" class="cz-tabs-go-back" @click="goBack()">
                    <span class="material-icons">arrow_back</span>
                    Back
                </button>
            </div>
            <div v-show="tab === 'details'" class="card pa-6">
                <cz-form ref="userForm" :form="form" @save="save">
                    <h5 class="card-title">User Details</h5>
                    <h6 class="card-subtitle mb-6 text-muted">Basic information and account settings.</h6>
                    <cz-input
                        label="Name"
                        v-model="form.name"
                        type="text"
                        :errors="errors.name"
                        :disabled="submitting" />
                    <cz-input
                        v-if="!$cuztomisable.login_with.email && !$cuztomisable.login_with.phone"
                        label="Username"
                        v-model="form.username"
                        type="text"
                        :errors="errors.username"
                        :disabled="submitting" />
                    <cz-input
                        label="Email"
                        v-model="form.email"
                        type="email"
                        :errors="errors.email"
                        :disabled="submitting" />
                    <cz-phone
                        label="Phone"
                        v-model="form.phone"
                        :errors="errors.phone"
                        :disabled="submitting"
                        is-mobile
                        default />
                    <cz-address
                        v-if="$cuztomisable.registration.address !== false"
                        label="Address"
                        v-model="form.address"
                        :errors="errors.address"
                        :disabled="submitting"
                        :hasAddressTwo="$cuztomisable.registration.address.address_two"
                        :hasAddressThree="$cuztomisable.registration.address.address_three" />
                    <hr class="mt-2 mb-4">
                    <cz-checkbox
                        label="Automatically detect timezone"
                        subtitle="Uses whatever timezone this device reports. Turn off to set one manually."
                        v-model="form.timezone_auto"
                        type="checkbox"
                        hide-details
                        :disabled="submitting"
                        class="mb-4" />
                    <cz-select
                        v-if="!form.timezone_auto"
                        label="Timezone"
                        v-model="form.timezone"
                        :items="timezoneOptions"
                        :errors="errors.timezone"
                        :required="false"
                        :auto-select-first="false"
                        :disabled="submitting"
                        class="mb-4" />
                    <div class="form-buttons">
                        <button v-if="isMineOrHasPermission('manage-users')"
                            type="submit"
                            class="button button--primary"
                            :class="{ 'button--block': breakpoint('sm'), 'button-width': !breakpoint('sm') }"
                            :disabled="submitting">Save Changes</button>
                    </div>
                </cz-form>
            </div>
            <div v-show="tab === 'security'" class="card pa-6">
                <component
                    v-if="mfaSectionVisible"
                    is="user-mfa-form"
                    v-model="form.mfa"
                    v-on:message="message"
                    :user="form?.id"></component>
                <hr v-if="mfaSectionVisible && passwordSectionVisible" class="mt-4 mb-4">
                <component
                    v-if="passwordSectionVisible"
                    is="user-password-form"
                    ref="user-password-form"
                    v-on:message="message"
                    v-on:reload="get"
                    :user="form?.id"
                    :admin="$store.state.user?.admin"
                    :change-password-sent-at="form.change_password_sent_at"
                    :email-verified="!!form.email_verified_at"
                    :phone-verified="!!form.phone?.verified_at"
                    :has-phone="!!form.phone?.number"></component>
                <hr v-if="(mfaSectionVisible || passwordSectionVisible) && accessSectionVisible" class="mt-4 mb-4">
                <component
                    v-if="accessSectionVisible"
                    is="user-security-form"
                    ref="user-security-form"
                    v-on:message="message"
                    :user="form?.id"></component>
            </div>
            <div v-show="tab === 'logins'" class="card pa-6">
                <component
                    v-if="isMineOrHasPermission('view-user-logins') && form.id"
                    is="user-login-form"
                    ref="user-login-form"
                    v-on:message="message"
                    :user="form?.id"
                    :admin="$store.state.user?.admin"></component>
            </div>
            <div v-show="tab === 'logs'" class="card pa-6">
                <component
                    v-if="logsTabVisible && form.id"
                    is="user-logs-form"
                    ref="user-logs-form"
                    :user="form?.id"></component>
            </div>
        </template>
    </div>
</template>
<script>
import Password from './Password.vue';
import MFA from './MFA.vue';
import RecentLogin from './RecentLogin.vue';
import Security from './Security.vue';
import Logs from './Logs.vue';
export default {
    data: function() {
        return {
            loading: false,
            submitting: false,
            errors: [],
            imageUrl: this.$url+'profile.png',
            tab: 'details',
            form: {},
            // Snapshot of the Details fields right after load, so editing them (without saving)
            // can be detected and flagged on the tab.
            detailsSnapshot: null,
            lockToggling: false,
            resetContactType: null,
            resettingContact: false,
        };
    },
    methods: {
        openResetContactModal: function(type) {
            this.resetContactType = type;
            this.$refs.resetContactModal.open();
        },
        confirmResetContact: function() {
            this.resettingContact = true;
            const url = this.resetContactType === 'phone'
                ? `/user/${this.form.id}/messages`
                : `/user/${this.form.id}/emails`;
            axios.patch(url).then(({ data }) => {
                if (this.resetContactType === 'phone') {
                    this.form.phone.disable_messages = false;
                } else {
                    this.form.disable_emails = false;
                }
                this.$message.push({ text: data.message });
                this.$refs.resetContactModal.close();
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.resettingContact = false;
                }, 500);
            });
        },
        toggleLocked: function() {
            this.lockToggling = true;
            axios.patch(`/user/${this.form.id}/locked`).then(({ data }) => {
                this.form.locked = !data.locked;
                this.$message.push({ text: data.message });
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.lockToggling = false;
                }, 500);
            });
        },
        get: function() {
            let id = this.$route.params.id;
            axios.get(`/user/${id}`).then(({ data }) => {
                this.form = this.clone(data.user);
            }).catch((error) => {

            }).finally(() => {
                setTimeout(() => {
                    this.loading = false;
                    // Waits for the Details tab
                    this.$nextTick(() => this.snapshotDetails());
                }, 1000);
            });
        },
        snapshotDetails: function() {
            const { name, username, email, phone, address, timezone, timezone_auto } = this.form;
            this.detailsSnapshot = JSON.stringify({ name, username, email, phone, address, timezone, timezone_auto });
        },
        save: function() {
            let id = this.$route.params.id;
            let formData = new FormData();
            formData.append('name', this.form.name ?? '');
            formData.append('image', this.form.image ?? '');
            if (!this.$cuztomisable.login_with.email && !this.$cuztomisable.login_with.phone) {
                formData.append('username', this.form.username ?? '');
            }
            formData.append('email', this.form.email ?? '');
            formData.append('phone', this.form.phone?.number ?? '');
            formData.append('country_code', this.form.phone?.country_code ?? '');
            formData.append('timezone_auto', this.form.timezone_auto === false ? '0' : '1');
            formData.append('timezone', this.form.timezone_auto === false ? (this.form.timezone ?? '') : '');
            if (this.$cuztomisable.registration.address !== false) {
                formData.append('address', this.form.address?.address);
                formData.append('address_two', this.form.address?.address_two);
                formData.append('address_three', this.form.address?.address_three);
                formData.append('city', this.form.address?.city);
                formData.append('state_or_province', this.form.address?.state_or_province);
                formData.append('zip_or_postal_code', this.form.address?.zip_or_postal_code);
                formData.append('country', this.form.address?.country);
            }
            this.submitting = true;
            formData = this.cleanFormData(formData);
            axios.post(`/user/${id}`, formData).then(({ data }) => {
                this.$message.push({ text: data.message });
                this.snapshotDetails();
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                    this.errors['address'] = {
                        address: this.errors['address'] ?? '',
                        address_two: this.errors['address_two'] ?? '',
                        address_three: this.errors['address_three'] ?? '',
                        city: this.errors['city'] ?? '',
                        state_or_province: this.errors['state_or_province'] ?? '',
                        zip_or_postal_code: this.errors['zip_or_postal_code'] ?? '',
                        country: this.errors['country'] ?? '',
                    };
                }
                if (response?.data?.message) {
                    this.$message.push({ text: response.data.message, color: 'danger' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        message: function(message) {
            this.$message.push({ ...(message ?? {}), color: message?.error ? 'danger' : message?.color });
        },
        handleUserChange: function(user) {
            if (typeof(this.form.id) == 'undefined') {
                let id = this.$route.params.id;
                if (id == '' || typeof(id) == 'undefined') {
                    this.form = user ?? {};
                    setTimeout(() => {
                        this.loading = false;
                        this.$nextTick(() => this.snapshotDetails());
                    }, 1000);
                } else {
                    this.get();
                }
            }
        },
        isMineOrHasPermission: function(slug) {
            return this.$store.getters.hasPermission(slug) || typeof(this.$route.params.id) == 'undefined';
        }
    },
    computed: {
        detailsDirty: function() {
            if (!this.detailsSnapshot) {
                return false;
            }
            const { name, username, email, phone, address, timezone, timezone_auto } = this.form;
            return JSON.stringify({ name, username, email, phone, address, timezone, timezone_auto }) !== this.detailsSnapshot;
        },
        timezoneOptions: function() {
            const zones = typeof Intl.supportedValuesOf === 'function'
                ? Intl.supportedValuesOf('timeZone')
                : [this.form.timezone].filter(Boolean);
            return zones.map(zone => ({ value: zone, text: zone.replace(/_/g, ' ') }));
        },
        mfaSectionVisible: function() {
            return this.isMineOrHasPermission('toggle-user-mfa') && this.$cuztomisable.multi_factor_authentication.enabled;
        },
        passwordSectionVisible: function() {
            if (!this.isMineOrHasPermission('reset-user-passwords')) {
                return false;
            }
            const isSelf = this.form.id == this.$store.state.user?.id;
            return !!this.$store.state.user?.admin || isSelf;
        },
        accessSectionVisible: function() {
            return this.$store.getters.hasPermission('manage-user-roles-permissions') && !!this.form.id;
        },
        canManageContactPrefs: function() {
            return this.$store.getters.hasPermission('manage-users');
        },
        securityTabVisible: function() {
            return this.mfaSectionVisible || this.passwordSectionVisible || this.accessSectionVisible;
        },
        logsTabVisible: function() {
            return !!this.$store.state.user?.admin;
        },
        securityDirty: function() {
            return this.$refs['user-password-form']?.dirty ?? false;
        },
        accessDirty: function() {
            return this.$refs['user-security-form']?.dirty ?? false;
        },
        canToggleLock: function() {
            return !!this.form.id
                && this.form.id != this.$store.state.user?.id
                && this.$store.getters.hasPermission('manage-users');
        },
    },
    watch: {
        '$store.state.user': {
            handler: function(user) {
                this.handleUserChange(user);
            }
        },
        '$route.params.id': {
            immediate: true,
            handler: function(id) {
                this.errors = [];
                this.loading = true;
                let user = {};
                if (this.form.id != id) {
                    this.form = {};
                }
                if (typeof(id) == 'undefined') {
                    user = this.$store.state.user;
                } else {
                    this.form = {};
                }
                this.handleUserChange(user);
            }
        }
    },
    props: {
        id: { type: [String, Number], default: null },
    },
    components: {
        'user-login-form': RecentLogin,
        'user-security-form': Security,
        'user-mfa-form': MFA,
        'user-password-form': Password,
        'user-logs-form': Logs,
    }
};
</script>
