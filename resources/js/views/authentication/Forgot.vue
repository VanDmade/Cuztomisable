<template>
    <div class="page">
        <vm-message :message="message"></vm-message>
        <h3>Forgot Password</h3>
        <h6 class="sub-title mb-6">Enter the email address associated with your account</h6>
        <form @submit.prevent="save">
            <vm-input
                :label="usernameLabel"
                v-model="form.username"
                type="input"
                :errors="errors.username"
                :disabled="submitting" />
            <router-link class="login-link mb-5" :to="{ name: 'login' }">Remember password?</router-link>
            <button type="submit" class="btn btn-primary btn-sm-full-width" :disabled="submitting">Send</button>
        </form>
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
            },
            message: {
                text: '',
                error: false,
            },
        }
    },
    methods: {
        save: function() {
            this.submitting = true;
            this.errors = [];
            var formData = new FormData();
            formData.append('username', this.form.username ?? '');
            axios.post('/password/forgot', formData).then(({ data }) => {
                this.message = {
                    text: data.message,
                    error: false,
                };
                setTimeout(() => {
                    this.$router.push({ name: 'reset', params: { token: data.token }})
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
            })
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