<template>
    <div id="login-page" class="page auth-page">
        <div class="auth-card">
            <div class="auth-card__header">
                <img :src="$url+'logo.png'" class="auth-card__logo">
                <div class="auth-card__header-text">
                    <h1 class="auth-card__title">Log in</h1>
                    <p class="auth-card__subtitle">Welcome back to {{ appName }}</p>
                </div>
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
                    :disabled="submitting"
                    :link="{ name: 'forgot' }"
                    link-text="Forgot password?" />
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Log in</button>
                </div>
            </cz-form>
        </div>
        <p class="auth-card__footer" v-if="!$cuztomisable?.registration?.disabled?.web">
            New here? <router-link :to="{ name: 'registration' }" class="button--link">Create an account</router-link>
        </p>
        <template v-if="socialProviders.length">
            <hr class="cz-auth-hr">
            <div class="cz-social-login-buttons">
                <a v-for="provider in socialProviders"
                    :key="provider.key"
                    :href="'/api/auth/'+provider.key+'/redirect'"
                    class="cz-social-login-button"
                    :class="'cz-social-login-button--'+provider.key">
                    <span v-if="provider.icon" class="cz-social-login-icon" v-html="provider.icon"></span>
                    {{ provider.label }}
                </a>
            </div>
        </template>
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
    computed: {
        socialProviders: function() {
            if (!this.$cuztomisable?.social?.enabled) {
                return [];
            }
            const icons = {
                google: '<svg viewBox="0 0 48 48"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3c-1.6 4.7-6.1 8-11.3 8-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.1 8 3l6-6C34.5 5.1 29.5 3 24 3 12.4 3 3 12.4 3 24s9.4 21 21 21 21-9.4 21-21c0-1.3-.1-2.7-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.5 16 18.9 13 24 13c3.1 0 5.8 1.1 8 3l6-6C34.5 5.1 29.5 3 24 3c-7.7 0-14.4 4.3-17.7 10.7z"/><path fill="#4CAF50" d="M24 45c5.4 0 10.3-1.8 14-4.9l-6.5-5.5c-2.1 1.5-4.8 2.4-7.5 2.4-5.2 0-9.6-3.3-11.3-8l-6.6 5.1C9.5 40.6 16.2 45 24 45z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.2 4.3-4.1 5.7l6.5 5.5C39.9 37.9 45 32.5 45 24c0-1.3-.1-2.7-.4-3.5z"/></svg>',
                facebook: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.987C18.343 21.128 22 16.991 22 12z"/></svg>',
                linkedin: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.137 1.445-2.137 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 1 1 0-4.124 2.062 2.062 0 0 1 0 4.124zM7.114 20.452H3.558V9h3.556v11.452z"/></svg>',
                twitter: '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
                instagram: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
            };
            const providers = this.$cuztomisable?.social?.providers ?? {};
            return Object.keys(icons)
                .filter(key => !!providers[key]?.enabled)
                .map(key => ({
                    key,
                    label: providers[key].label ?? key,
                    icon: providers[key].logo === false ? null : icons[key],
                }));
        },
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
