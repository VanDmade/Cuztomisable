<template>
    <div id="login-page" class="page">
        <div class="card ma-2 pa-6">
            <div class="d-flex">
                <img :src="$url+'logo.png'" class="cz-authentication-logo">
                <div class="cz-title">
                    <h3 class="card-title">Login</h3>
                    <h6 class="card-subtitle mb-2 text-muted">Welcome to {{ appName }}!</h6>
                </div>
            </div>
            <fm-form ref="loginForm" class="mt-4" :form="form"
                @save="login">
                <fm-input
                    :label="usernameLabel()"
                    v-model="form.username"
                    type="text"
                    autocomplete="username"
                    :errors="errors.username"
                    :disabled="submitting" />
                <fm-input
                    label="Password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    :errors="errors.password"
                    :disabled="submitting" />
                <router-link :to="{ name: 'forgot' }" class="button--link">Forgot password?</router-link>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Login</button>
                </div>
            </fm-form>
        </div>
        <div class="text-center">
            <router-link :to="{ name: 'registration' }" class="button--link">New here? Create an account!</router-link>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            appName: import.meta.env.VITE_APP_NAME,
            appHome: import.meta.env.VITE_APP_HOME,
            submitting: false,
            errors: [],
            form: {
                username: '',
                password: '',
            },
        }
    },
    methods: {
        async login() {
            this.submitting = true;
            this.errors = [];
            var formData = new FormData();
            formData.append('username', this.form.username ?? '');
            formData.append('password', this.form.password ?? '');
            try {
                let response = await this.$store.dispatch('login', formData);
                const payload = response?.data?.data ?? response?.data ?? {};
                setTimeout(() => {
                    if (payload.multi_factor_authentication === true) {
                        const mfaToken = payload.token ?? payload.mfa_token ?? null;
                        if (!mfaToken) {
                            this.errors.username = [];
                            this.errors.username.push('Unable to continue to multi-factor authentication. Please try logging in again.');
                            this.submitting = false;
                            return;
                        }
                        setTimeout(() => {
                            // Redirect to the MFA page
                            this.$router.push({ path: `/mfa/${encodeURIComponent(String(mfaToken))}` });
                        }, 150);
                    } else {
                        this.$router.push({ path: this.appHome });
                    }
                }, 500);
            } catch (error) {
                if (error?.response) {
                    let response = error?.response;
                    if (response?.data?.errors) {
                        this.errors = response.data.errors;
                    } else if (response?.data?.message) {
                        this.errors.username = [];
                        this.errors.username.push(response.data.message);
                    }
                } else {
                    this.errors.username = [];
                    this.errors.username.push('The server has experienced an error. Please try again later.');
                }
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            }
        },
    }
}
</script>