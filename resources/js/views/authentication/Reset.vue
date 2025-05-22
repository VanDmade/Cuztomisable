<template>
    <div id="login-page">
        <div class="card ma-2 pa-6">
            <h3 class="card-title">Reset Password</h3>
            <h6 class="card-subtitle mb-2 text-muted">
                <span v-if="!verifiedCode">The code was sent to your email address</span>
                <span v-else>Enter your new password</span>
            </h6>
            <fm-form v-if="!verifiedCode" ref="codeForm" class="mt-4" :form="form" @save="verify(true)">
                <fm-input
                    label="Code"
                    v-model="form.code"
                    type="input"
                    :errors="errors.code"
                    :disabled="submitting" />
                <p v-if="resend" class="login-link mt-3">Haven't received the code? <a href="#" class="links" @click="send">Click here</a></p>
                <p v-else-if="resending" class="mt-3">Resending...</p>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Verify</button>
                </div>
            </fm-form>
            <fm-form v-else ref="resetForm" class="mt-4" :form="form" @save="save">
                <fm-input
                    label="Password"
                    v-model="form.password"
                    type="password"
                    :errors="errors.password"
                    :disabled="submitting" />
                <requirements :password="form.password" class="mb-6" v-on:completed="passwordComplete" />
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Change</button>
                </div>
            </fm-form>
        </div>
        <div v-if="!verifiedCode" class="text-center">
            <router-link :to="{ name: 'login' }" class="links">Remember password?</router-link>
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
            resending: false,
            doubleTimer: false,
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
            var code = verifyCode ? ('/'+this.form.code) : '';
            axios.get('/password/forgot/'+this.token+'/verify'+code).then(({ data }) => {
                if (verifyCode) {
                    this.$emit('message', { text: data.message });
                    setTimeout(() => {
                        this.verifiedCode = true;
                    }, 1500);
                } else {
                    this.setupResend();
                }
                this.loading = false;
            }).catch(({ response }) => {
                if (verifyCode) {
                    this.errors.code = [];
                    this.errors.code.push(response.data.message);
                } else if (response.data.message) {
                    this.$emit('message', { text: response.data.message, error: true });
                    this.$router.push({ name: 'forgot' });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        send: function() {
            this.resending = true;
            this.resend = false;
            this.submitting = true;
            axios.get('/password/forgot/'+this.token+'/send').then(({ data }) => {
                this.doubleTimer();
                this.setupResend();
                // Sets a success message for the MFA sending
                this.message = {
                    text: data.message,
                    error: false,
                };
            }).catch(({ response }) => {
                if (response.data.message) {
                    // Output the message about the error
                    this.$emit('message', { text: response.data.message, error: true });
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                    this.resending = false;
                }, 2000);
            });
        },
        save: function() {
            this.errors = [];
            this.submitting = true;
            var formData = new FormData();
            formData.append('code', this.form.code);
            formData.append('password', this.form.password);
            axios.post('/password/forgot/'+this.token, formData).then(({ data }) => {
                this.$emit('message', { text: data.message });
                this.$router.push({ name: 'login' });
            }).catch(({ response }) => {
                if (response.data.errors) {
                    this.errors = response.data.errors;
                }
                if (response.data.message) {
                    // Output the message about the error
                    this.$emit('message', { text: response.data.message, error: true });
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
            var resendAfter = this.$cuztomisable.passwords.resend_after * 1000;
            if (this.doubleTimer) {
                resendAfter *= 2;
            }
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