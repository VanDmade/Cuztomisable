<template>
    <div id="login-page" class="page auth-page">
        <div class="auth-card">
            <div class="auth-card__header">
                <img :src="$url+'logo.png'" class="auth-card__logo">
                <h1 class="auth-card__title">Log in</h1>
                <p class="auth-card__subtitle">Welcome back to {{ appName }}</p>
            </div>
            <cz-form ref="loginForm" class="auth-card__form" :form="form"
                @save="login">
                <cz-input
                    :label="usernameLabel()"
                    v-model="form.username"
                    type="text"
                    autocomplete="username"
                    :errors="errors.username"
                    :disabled="submitting" />
                <cz-input
                    label="Password"
                    v-model="form.password"
                    type="password"
                    autocomplete="current-password"
                    :errors="errors.password"
                    :disabled="submitting" />
                <div class="auth-card__row">
                    <router-link :to="{ name: 'forgot' }" class="button--link">Forgot password?</router-link>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Log in</button>
                </div>
            </cz-form>
        </div>
        <p class="auth-card__footer" v-if="!$cuztomisable?.registration?.disabled?.web">
            New here? <router-link :to="{ name: 'registration' }" class="button--link">Create an account</router-link>
        </p>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            appName: import.meta.env.VITE_APP_NAME,
            appHome: import.meta.env.VITE_APP_HOME ?? '/portal',
            submitting: false,
            errors: [],
            form: {
                username: '',
                password: '',
            },
        }
    },
    methods: {
        login: async function() {
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
                        // Hard navigation on purpose: crossing the unauthenticated -> authenticated
                        // boundary should load a fresh app state rather than carry over SPA state.
                        window.location.href = this.appHome;
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
