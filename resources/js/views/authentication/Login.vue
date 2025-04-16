<template>
    <div id="login-page">
        <div class="card ma-2 pa-6">
            <vm-message :message="message"></vm-message>
            <h3 class="card-title">Login</h3>
            <h6 class="card-subtitle mb-2 text-muted">Welcome to Cuztomisable!</h6>
            <form @submit.prevent="login" class="mt-4">
                <fm-input
                    :label="usernameLabel"
                    v-model="form.username"
                    type="input"
                    :errors="errors.username"
                    :disabled="submitting" />
                <fm-input
                    label="Password"
                    v-model="form.password"
                    type="password"
                    :errors="errors.password"
                    :disabled="submitting" />
                <router-link :to="{ name: 'forgot' }" class="links">Forgot password?</router-link>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Login</button>
                </div>
            </form>
        </div>
        <div class="text-center">
            <router-link :to="{ name: 'registration' }" class="links">New here? Create an account!</router-link>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            submitting: false,
            errors: [],
            form: {
                username: 'michaelvanderwerkerllc@gmail.com',
                password: 'HelloHello1!',
            },
            message: {
                text: '',
                error: false,
            },
        }
    },
    created: function() {
        const query = Object.assign({}, this.$route.query);
        if (typeof(query.message) !== 'undefined') {
            this.message = {
                text: query.message,
                error: query.type == 'error',
            }
            // Removes the message from the site
            this.$router.replace({ 'query': null });
        }
    },
    methods: {
        login: function() {
            this.submitting = true;
            this.errors = [];
            var formData = new FormData();
            formData.append('username', this.form.username ?? '');
            formData.append('password', this.form.password ?? '');
            axios.post('/login', formData).then(({ data }) => {
                if (data.multi_factor_authentication !== true) {
                    this.$store.commit('login', data.token);
                    this.message = {
                        text: data.message,
                        error: false,
                    };
                }
                if (data.multi_factor_authentication === true) {
                    setTimeout(() => {
                        // Redirect to the MFA page
                        this.$router.push({ name: 'mfa', params: { token: data.token }});
                    }, 1500);
                }
            }).catch(({ response }) => {
                if (response.data.errors) {
                    this.errors = response.data.errors;
                } else if (response.data.message) {
                    this.errors.username = [];
                    this.errors.username.push(response.data.message);
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
    },
    computed: {
        usernameLabel: function() {
            try {
                var loginWith = this.$cuztomisables.login_with ?? [];
                return loginWith.phone ?
                    (loginWith.email ? 'Email Address or Phone Number' : 'Phone Number') :
                    (loginWith.email ? 'Email Address' : 'Username');
            } catch (error) {
                return 'Email Address';
            }
        }
    }
}
</script>