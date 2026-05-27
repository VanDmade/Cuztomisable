<template>
    <div id="mfa-page" class="page">
        <fm-loading :loading="verifying" :full="true" :large="true" message="Loading..."></fm-loading>
        <div class="card ma-2 pa-6">
            <div class="d-flex">
                <img :src="$url+'logo.png'" class="cz-authentication-logo">
                <div class="cz-title">
                    <h3 class="card-title">Multi-Factor Authentication</h3>
                    <h6 class="card-subtitle mb-2 text-muted">
                        <span v-if="!sent">Enter the email address associated with your account.</span>
                        <span v-else>The code was sent! Please enter it below once you receive it.</span>
                    </h6>
                </div>
            </div>
            <fm-form v-if="!sent" ref="mfaSelectForm" class="mt-4" :form="form" @save="send">
                <div class="mfa-email" :class="send_via.phone != null ? 'mb-3' : 'mb-6'" v-if="send_via.email != null">
                    <fm-checkbox
                        :label="send_via.email"
                        v-model="form.email"
                        type="radio"
                        :errors="errors.email"
                        :disabled="submitting" />
                </div>
                <div class="mfa-phone mb-6" v-if="send_via.phone != null">
                    <fm-checkbox
                        :label="send_via.phone"
                        v-model="form.phone"
                        type="radio"
                        :errors="errors.phone"
                        :disabled="submitting" />
                </div>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting || (!form.email && !form.phone && !resending)">Send</button>
                </div>
                <p v-if="sent && expiresIn !== null" class="text-center text-muted mt-2 text-xs">Code expires in {{ formattedExpiresIn }}</p>
            </fm-form>
            <fm-form v-else ref="mfaForm" class="mt-4" :form="form" @save="save">
                <fm-input
                    label="Code"
                    v-model="form.code"
                    type="input"
                    :errors="errors.code"
                    :disabled="submitting" />
                <fm-checkbox
                    label="Remember device? (Do not use on a public or shared device)"
                    v-model="form.remember"
                    type="checkbox"
                    :errors="errors.remember"
                    hide-details
                    :disabled="submitting" />
                <p v-if="resend" class="login-link mt-3">Haven't received the code? <a href="#" class="button--link" @click="send">Click here</a></p>
                <p v-else-if="resending" class="mt-3">Resending...</p>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Verify</button>
                </div>
                <p v-if="sent && expiresIn !== null" class="text-center text-muted mt-2 text-xs">Code expires in {{ formattedExpiresIn }}</p>
            </fm-form>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            appHome: import.meta.env.VITE_APP_HOME ?? '/portal',
            verifying: true,
            autoSending: false,
            submitting: false,
            resend: false,
            errors: [],
            token: '',
            expiresIn: null,
            expiresTimer: null,
            expiryRedirected: false,
            sent: false,
            resending: false,
            send_via: {
                phone: null,
                email: null,
            },
            form: {
                email: false,
                phone: false,
                remember: false,
                code: '',
            },
        }
    },
    created: function() {
        this.setToken();
        if (!this.token) {
            this.verifying = false;
            this.$notify.error('Your MFA session token is missing. Please log in again.');
            this.$loading.hide();
            this.$router.push({ name: 'login' });
            return;
        }
        this.verify();
    },
    unmounted: function() {
        this.clearExpiryCountdown();
    },
    methods: {
        startExpiryCountdown: function(seconds) {
            this.clearExpiryCountdown();
            const normalized = Number.isFinite(Number(seconds)) ? Math.max(0, Math.floor(Number(seconds))) : 0;
            this.expiresIn = normalized;
            this.expiryRedirected = false;
            if (normalized <= 0) {
                this.handleCodeExpired();
                return;
            }
            this.expiresTimer = setInterval(() => {
                if (this.expiresIn === null || this.expiresIn <= 0) {
                    this.handleCodeExpired();
                    return;
                }
                this.expiresIn = this.expiresIn - 1;
                if (this.expiresIn <= 0) {
                    this.handleCodeExpired();
                }
            }, 1000);
        },
        clearExpiryCountdown: function() {
            if (this.expiresTimer) {
                clearInterval(this.expiresTimer);
                this.expiresTimer = null;
            }
        },
        handleCodeExpired: function() {
            if (this.expiryRedirected) {
                return;
            }
            this.expiryRedirected = true;
            this.expiresIn = 0;
            this.clearExpiryCountdown();
            this.$notify.error('Your code has expired. Please try again.');
            this.$router.push({ name: 'login' });
        },
        resolveToken: function() {
            const routeToken = this.$route?.params?.token;
            const pageToken = this.$page?.props?.token;
            const value = routeToken ?? pageToken ?? null;
            return value ? String(value).trim() : '';
        },
        setToken: function() {
            this.token = this.resolveToken();
        },
        send: function() {
            var resending = this.resend;
            this.resend = false;
            this.submitting = true;
            const switchingToCode = !this.sent;
            this.setToken();
            if (!this.token) {
                this.submitting = false;
                if (switchingToCode) {
                    this.verifying = false;
                    this.autoSending = false;
                }
                this.$notify.error('Your MFA session token is missing. Please log in again.');
                this.$router.push({ name: 'login' });
                return;
            }
            var formData = new FormData();
            if (!this.form.email && !this.form.phone && !resending) {
                this.submitting = false;
                if (switchingToCode) {
                    this.verifying = false;
                    this.autoSending = false;
                }
                return;
            }
            formData.append('type', this.form.email ? 'email' : (this.form.phone ? 'phone' : 'resend'));
            return axios.post(`/login/mfa/${this.token}/send`, formData).then(({ data }) => {
                if (switchingToCode) {
                    this.verifying = false;
                    setTimeout(() => {
                        this.sent = true;
                    }, 1000);
                } else {
                    this.sent = true;
                }
                this.setupResend();
                this.refreshExpiry();
                // Sets a success message for the MFA sending
                this.$notify.success(data.message);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    // Output the message about the error
                    this.$notify.error(response.data.message);
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                    if (switchingToCode) {
                        this.verifying = false;
                        this.autoSending = false;
                    }
                }, 1500);
            });
        },
        async save() {
            this.submitting = true;
            this.setToken();
            if (!this.token) {
                this.submitting = false;
                this.$notify.error('Your MFA session token is missing. Please log in again.');
                this.$router.push({ name: 'login' });
                return;
            }
            try {
                const formData = new FormData();
                formData.append('code', this.form.code);
                formData.append('remember', this.form.remember ? '1' : '0');
                const { data } = await axios.post(`/login/mfa/${this.token}`, formData);
                await this.$store.dispatch('checkAuth');
                this.$notify.success(data.message);
                setTimeout(() => {
                    this.$router.push({ path: this.appHome });
                }, 150);
            } catch (error) {
                const response = error.response;
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                } else if (response?.data?.message) {
                    this.$notify.error(response.data.message);
                }
            } finally {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            }
        },
        verify: function() {
            this.setToken();
            if (!this.token) {
                this.verifying = false;
                this.$notify.error('Your MFA session token is missing. Please log in again.');
                this.$loading.hide();
                this.$router.push({ name: 'login' });
                return;
            }
            this.verifying = true;
            this.$loading.show();
            axios.get(`/login/mfa/${this.token}/verify`).then(({ data }) => {
                // Determines if the user just refreshed and if the code was already sent
                if (data.sent == true) {
                    this.sent = true;
                    this.form.type = data.sent_via;
                    this.startExpiryCountdown(data.expires_in ?? 0);
                    this.setupResend();
                    return;
                }
                this.expiresIn = null;
                this.clearExpiryCountdown();
                // Sets the send via parameters for the checkbox
                this.send_via = {
                    phone: data.phone,
                    email: data.email,
                };
                // Defaults to email when available, otherwise phone
                this.form.email = !!this.send_via.email;
                this.form.phone = !this.form.email && !!this.send_via.phone;
                const availableMethods = [this.send_via.email, this.send_via.phone].filter(v => !!v).length;
                if (availableMethods === 1) {
                    this.autoSending = true;
                    this.send();
                    return;
                }
                this.$notify.success(data.message);
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                } else if (response?.data?.message) {
                    this.$notify.error(response.data.message);
                    setTimeout(() => {
                        this.$router.push({ name: 'login' });
                    }, 1500);
                }
            }).finally(() => {
                if (!this.autoSending) {
                    this.verifying = false;
                }
                this.$loading.hide();
            });
        },
        setupResend: function() {
            var resendAfter = this.$cuztomisable.multi_factor_authentication.resend_after * 1000;
            setTimeout(() => {
                this.resend = true;
            }, resendAfter);
        },
        refreshExpiry: function() {
            axios.get(`/login/mfa/${this.token}/verify`).then(({ data }) => {
                if (data?.sent === true) {
                    this.startExpiryCountdown(data.expires_in ?? 0);
                }
            }).catch(() => {
                this.expiryRedirected = false;
                this.expiresIn = null;
                this.clearExpiryCountdown();
            });
        },
    },
    computed: {
        formattedExpiresIn: function() {
            if (this.expiresIn === null) {
                return '';
            }
            const totalSeconds = Math.max(0, Number(this.expiresIn) || 0);
            const minutes = Math.floor(totalSeconds / 60);
            const seconds = totalSeconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        },
    },
    watch: {
        '$route.path': {
            handler: function() {
                this.setToken();
            }
        },
        'form.email': {
            handler: function(value) {
                if (value == true) {
                    this.form.phone = false;
                }
            }
        },
        'form.phone': {
            handler: function(value) {
                if (value == true) {
                    this.form.email = false;
                }
            }
        },
    }
}
</script>