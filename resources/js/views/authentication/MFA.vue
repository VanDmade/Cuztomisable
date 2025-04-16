<template>
    <div class="page">
        <loading v-if="loading"></loading>
        <div v-else>
            <vm-message :message="message"></vm-message>
            <h4>Multi-Factor Authentication</h4>
            <form v-if="!sent" class="send-mfa-form" @submit.prevent="send">
                <h6 class="sub-title mb-6">Please select how you want the code sent</h6>
                <div class="mfa-email" :class="send_via.phone != null ? 'mb-3' : 'mb-6'" v-if="send_via.email != null">
                    <input class="form-check-input"
                        id="send-via-email"
                        type="radio"
                        value="email"
                        v-model="form.type"
                        :disabled="submitting">
                    <label class="form-check-label ml-4" for="send-via-email">{{ send_via.email }}</label>
                </div>
                <div class="mfa-phone mb-6" v-if="send_via.phone != null">
                    <input class="form-check-input"
                        id="send-via-phone"
                        type="radio"
                        value="phone"
                        v-model="form.type"
                        :disabled="submitting">
                    <label class="form-check-label ml-4" for="send-via-phone">{{ send_via.phone }}</label>
                </div>
                <button type="submit" :disabled="submitting" class="btn btn-primary btn-sm-full-width">Send</button>
            </form>
            <form v-else class="mfa-code-form" @submit.prevent="save">
                <h6 class="sub-title mb-6">The code was sent! Please enter it below once you receive it.</h6>
                <vm-input
                    label="Code"
                    v-model="form.code"
                    type="text"
                    :errors="errors.code"
                    :disabled="submitting"/>
                <input class="form-check-input"
                    id="remember"
                    type="checkbox"
                    value="1"
                    v-model="form.remember"
                    :disabled="submitting">
                <label class="form-check-label ml-4" for="remember">Remember device?</label>
                <p class="form-notes mb-5">Do not remember this device if it's a public device.</p>
                <button type="submit" :disabled="submitting" class="btn btn-primary btn-sm-full-width">Verify</button>
                <p v-if="resend" class="login-link mt-3">Haven't received the code? <a href="#" @click="send">Click here</a></p>
            </form>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            loading: true,
            submitting: false,
            resend: false,
            errors: [],
            token: this.$route.params.token,
            sent: false,
            send_via: {
                phone: null,
                email: null,
            },
            form: {
                type: '',
                remember: '0',
                code: '',
            },
            message: {
                text: '',
                error: false,
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
            formData.append('type', this.form.type);
            axios.post('/login/mfa/' + this.token + '/send', formData).then(({ data }) => {
                this.sent = true;
                this.setupResend();
                // Sets a success message for the MFA sending
                this.message = {
                    text: data.message,
                    error: false,
                };
            }).catch(({ response }) => {
                if (response.data.errors) {
                    this.errors = response.data.errors;
                } else if (response.data.message) {
                    // Output the message about the error
                    this.message = {
                        text: response.data.message,
                        error: true,
                    };
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        save: function() {
            this.submitting = true;
            var formData = new FormData();
            formData.append('code', this.form.code);
            formData.append('remember', this.form.remember == '1' ? '1' : '0');
            axios.post('/login/mfa/' + this.token, formData).then(({ data }) => {
                this.message = {
                    text: data.message,
                    error: false,
                };
                this.$store.commit('login', data.token);
                setTimeout(() => {
                    // Sets a success message for the MFA sending
                    this.$router.push({ name: 'portal' });
                }, 1500);
            }).catch(({ response }) => {
                if (response.data.errors) {
                    this.errors = response.data.errors;
                } else if (response.data.message) {
                    // Output the message about the error
                    this.message = {
                        text: response.data.message,
                        error: true,
                    };
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        verify: function() {
            axios.get('/login/mfa/' + this.token + '/verify').then(({ data }) => {
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
                    this.form.type = 'email';
                } else if (this.send_via.email == null) {
                    this.form.type == 'phone';
                }
                // Checks to see if there is only one possible locaiton to send the code to
                if (this.form.type != '') {
                    this.sent = true;
                    this.send();
                } else {
                    this.message = {
                        text: data.message,
                        error: false,
                    };
                }
            }).catch(({ response }) => {
                if (response.data.errors) {
                    this.errors = response.data.errors;
                } else if (response.data.message) {
                    this.$router.push({ name: 'login', query: { message: response.data.message, type: 'error' } });
                }
            }).finally(() => {
                this.loading = false;
            });
        },
        setupResend: function() {
            var resendAfter = this.$cuztomisables.multi_factor_authentication.resend_after * 1000;
            setTimeout(() => {
                this.resend = true;
            }, resendAfter);
        },
    }
}
</script>