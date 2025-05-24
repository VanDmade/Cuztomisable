<template>
    <div id="mfa-page" class="page">
        <div class="card ma-2 pa-6">
            <h3 class="card-title">Multi-Factor Authentication</h3>
            <h6 class="card-subtitle mb-2 text-muted">
                <span v-if="!sent">Enter the email address associated with your account.</span>
                <span v-else>The code was sent! Please enter it below once you receive it.</span>
            </h6>
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
            </fm-form>
            <fm-form v-else ref="mfaForm" class="mt-4" :form="form" @save="save">
                <fm-input
                    label="Code"
                    v-model="form.code"
                    type="input"
                    :errors="errors.code"
                    :disabled="submitting" />
                <fm-checkbox
                    label="Remember device?"
                    v-model="form.remember"
                    type="checkbox"
                    :errors="errors.remember"
                    hide-details
                    :disabled="submitting" />
                <p v-if="resend" class="login-link mt-3">Haven't received the code? <a href="#" class="links" @click="send">Click here</a></p>
                <p v-else-if="resending" class="mt-3">Resending...</p>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Verify</button>
                </div>
            </fm-form>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: false,
            resend: false,
            errors: [],
            token: this.$route.params.token,
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
        this.verify();
    },
    methods: {
        send: function() {
            var resending = this.resend;
            this.resend = false;
            this.submitting = true;
            var formData = new FormData();
            if (!this.form.email && !this.form.phone && !resending) {
                this.submitting = false;
                return;
            }
            formData.append('type', this.form.email ? 'email' : (this.form.phone ? 'phone' : 'resend'));
            axios.post(`/login/mfa/${this.token}/send`, formData).then(({ data }) => {
                this.sent = true;
                this.setupResend();
                // Sets a success message for the MFA sending
                this.$emit('message', { text: data.message });
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    // Output the message about the error
                    this.$emit('message', { text: response.data.message, error: true });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        async save() {
            this.submitting = true;
            try {
                const formData = new FormData();
                formData.append('code', this.form.code);
                console.log(this.form.remember);
                formData.append('remember', this.form.remember ? '1' : '0');
                const { data } = await axios.post(`/login/mfa/${this.token}`, formData);
                await this.$store.dispatch('checkAuth');
                this.$emit('message', { text: data.message });
                this.$emit('loading', true);
                setTimeout(() => {
                    this.$router.push({ name: 'portal' });
                }, 150);
            } catch (error) {
                const response = error.response;
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                }
                if (response?.data?.message) {
                    this.$emit('message', { text: response.data.message, error: true });
                }
            } finally {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            }
        },
        verify: function() {
            axios.get(`/login/mfa/${this.token}/verify`).then(({ data }) => {
                // Determines if the user just refreshed and if the code was already sent
                if (data.sent == true) {
                    this.sent = true;
                    this.form.type = data.sent_via;
                    this.setupResend();
                    return;
                }
                // Sets the send via parameters for the checkbox
                this.send_via = {
                    phone: data.phone,
                    email: data.email,
                };
                // Determines if the type should be set because there isn't more than one option
                if (this.send_via.phone == null) {
                    this.form.email = true;
                } else if (this.send_via.email == null) {
                    this.form.phone == true;
                }
                // Checks to see if there is only one possible locaiton to send the code to
                if (this.form.email || this.form.phone) {
                    this.sent = true;
                    this.send();
                } else {
                    this.$emit('message', { text: data.message });
                }
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                } else if (response?.data?.message) {
                    this.$emit('message', { text: response.data.message, error: true });
                    setTimeout(() => {
                        this.$router.push({ name: 'login' });
                    }, 1500);
                }
            }).finally(() => {
                this.$emit('loading', false);
            });
        },
        setupResend: function() {
            var resendAfter = this.$cuztomisable.multi_factor_authentication.resend_after * 1000;
            setTimeout(() => {
                this.resend = true;
            }, resendAfter);
        },
    },
    watch: {
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