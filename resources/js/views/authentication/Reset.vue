<template>
    <div class="page">
        <loading v-if="loading"></loading>
        <div id="reset-password-page" v-else>
            <vm-message :message="message"></vm-message>
            <h3>Reset Password</h3>
            <form v-if="!verifiedCode" @submit.prevent="verify(true)">
                <p>The code was sent to your email address</p>
                <vm-input
                    label="Code"
                    v-model="form.code"
                    type="input"
                    :errors="errors.code"
                    :disabled="submitting" />
                <router-link class="login-link mb-5" :to="{ name: 'login' }">Remember password?</router-link>
                <button type="submit" class="btn btn-primary btn-sm-full-width" :disabled="submitting">Verify</button>
                <p v-if="resend" class="login-link mt-3">Haven't received the code? <a href="#" @click="send">Click here</a></p>
            </form>
            <form v-else @submit.prevent="save">
                <p>Enter your new password</p>
                <vm-input
                    label="Password"
                    v-model="form.password"
                    type="password"
                    :errors="errors.password"
                    :disabled="submitting" />
                <requirements :password="form.password" class="mb-6" v-on:completed="passwordComplete" />
                <button type="submit" :disabled="disableSubmit || submitting" class="btn btn-primary btn-sm-full-width">Change</button>
            </form>
        </div>
    </div>
</template>
<script>
import PasswordRequirements from '../../components/PasswordRequirements.vue';
export default {
    data: function() {
        return {
            loading: false,
            submitting: false,
            disableSubmit: true,
            errors: [],
            token: this.$route.params.token,
            verifiedCode: false,
            resend: false,
            form: {
                code: '',
                password: '',
            },
            message: {
                text: '',
                error: false,
            },
        }
    },
    created: function() {
        this.verify(false);
    },
    methods: {
        verify: function(verifyCode) {
            if (verifyCode) {
                if (this.form.code == '') {
                    this.errors.code = [];
                    this.errors.code.push('The code is required.');
                    return;
                }
                this.submitting = true;
            }
            var code = verifyCode ? ('/' + this.form.code) : '';
            axios.get('/password/forgot/' + this.token + '/verify' + code).then(({ data }) => {
                if (verifyCode) {
                    this.verifiedCode = true;
                    this.message = {
                        text: data.message,
                        error: false,
                    };
                    this.submitting = false;
                } else {
                    this.setupResend();
                }
                this.loading = false;
            }).catch(({ response }) => {
                if (verifyCode) {
                    this.errors.code = [];
                    this.errors.code.push(response.data.message);
                    setTimeout(() => {
                        this.submitting = false;
                    }, 1500);
                } else if (response.data.message) {
                    this.$router.push({ name: 'login', query: {  message: response.data.message, type: 'error' } });
                }
            });
        },
        send: function() {
            var resending = this.resend;
            this.resend = false;
            this.submitting = true;
            axios.get('/password/forgot/' + this.token + '/send').then(({ data }) => {
                this.setupResend();
                // Sets a success message for the MFA sending
                this.message = {
                    text: data.message,
                    error: false,
                };
            }).catch(({ response }) => {
                if (response.data.message) {
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
            this.errors = [];
            this.submitting = true;
            var formData = new FormData();
            formData.append('code', this.form.code);
            formData.append('password', this.form.password);
            axios.post('/password/forgot/' + this.token, formData).then(({ data }) => {
                this.$router.push({ name: 'login', query: {  message: data.message, type: 'success' } });
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
        passwordComplete: function(complete) {
            this.disableSubmit = !complete;
        },
        setupResend: function() {
            var resendAfter = this.$cuztomisables.passwords.resend_after * 1000;
            setTimeout(() => {
                this.resend = true;
            }, resendAfter);
        },
    },
    components: {
        'requirements': PasswordRequirements,
    }
}
</script>