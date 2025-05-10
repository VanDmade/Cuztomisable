<template>
    <div id="registration-page">
        <div class="card ma-2 pa-6">
            <h3 class="card-title">Sign Up!</h3>
            <h6 class="card-subtitle mb-2 text-muted">We are always welcoming to new users!</h6>
            <fm-form ref="registrationForm" class="mt-4" :form="form"
                @save="save"
                @initialize="initialize"
                ask-before-leaving
                save-progress-before-leaving
                load-progress-on-entry>
                <fm-input
                    label="Name"
                    v-model="form.name"
                    type="text"
                    :errors="errors.name"
                    :disabled="submitting" />
                <fm-input
                    v-if="!$cuztomisable.login_with.email && !$cuztomisable.login_with.phone"
                    label="Username"
                    v-model="form.username"
                    type="text"
                    :errors="errors.username"
                    :disabled="submitting" />
                <fm-input
                    label="Email"
                    v-model="form.email"
                    type="email"
                    :errors="errors.email"
                    :disabled="submitting" />
                <fm-phone
                    label="Phone"
                    v-model="form.phone"
                    :errors="errors.phone"
                    :disabled="submitting"
                    is-mobile
                    default />
                <fm-address
                    v-if="$cuztomisable.registration.address !== false"
                    label="Address"
                    v-model="form.address"
                    :errors="errors.address"
                    :disabled="submitting"
                    :hasAddressTwo="$cuztomisable.registration.address.address_two"
                    :hasAddressThree="$cuztomisable.registration.address.address_three" />
                <fm-input
                    label="Password"
                    v-model="form.password"
                    type="password"
                    :errors="errors.password"
                    :disabled="submitting" />
                <requirements :password="form.password" v-on:completed="completed"></requirements>
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting || !passwordRequirementsMet">Sign Up</button>
                </div>
            </fm-form>
        </div>
        <div class="text-center">
            <router-link :to="{ name: 'login' }" class="links">Already have an account?</router-link>
        </div>
    </div>
</template>
<script>
import PasswordRequirements from '../../components/PasswordRequirements.vue';
export default {
    data: function() {
        return {
            submitting: false,
            registered: false,
            errors: [],
            passwordRequirementsMet: false,
            form: {
                name: '',
                username: '',
                email: '',
                phone: {
                    country_code: '',
                    number: '',
                },
                address: {
                    address: '',
                    address_two: '',
                    address_three: '',
                    city: '',
                    state_or_province: '',
                    zip_or_postal_code: '',
                    country: '',
                },
                password: '',
            }
        }
    },
    methods: {
        save: function() {
            this.submitting = true;
            this.errors = [];
            var formData = new FormData();
            formData.append('name', this.form.name ?? '');
            if (!this.$cuztomisable.login_with.email && !this.$cuztomisable.login_with.phone) {
                formData.append('username', this.form.username ?? '');
            }
            formData.append('email', this.form.email ?? '');
            formData.append('phone', this.form.phone.number ?? '');
            formData.append('country_code', this.form.phone.country_code ?? '');
            formData.append('password', this.form.password ?? '');
            if (this.$cuztomisable.registration.address !== false) {
                formData.append('address', this.form.address.address);
                formData.append('address_two', this.form.address.address_two);
                formData.append('address_three', this.form.address.address_three);
                formData.append('city', this.form.address.city);
                formData.append('state_or_province', this.form.address.state_or_province);
                formData.append('zip_or_postal_code', this.form.address.zip_or_postal_code);
                formData.append('country', this.form.address.country);
            }
            let code = typeof(this.$route.params.code) != 'undefined' ? ('/'+this.$route.params.code) : '';
            axios.post('/register'+code, formData).then(({ data }) => {
                this.registered = true;
                if (data.message) {
                    this.$emit('message', { text: data.message });
                }
                setTimeout(() => {
                    this.$router.push({ name: 'login' });
                }, 3500);
            }).catch(({ response }) => {
                if (response.data.errors) {
                    this.errors = response.data.errors;
                    this.errors['address'] = {
                        address: this.errors['address'] ?? '',
                        address_two: this.errors['address_two'] ?? '',
                        address_three: this.errors['address_three'] ?? '',
                        city: this.errors['city'] ?? '',
                        state_or_province: this.errors['state_or_province'] ?? '',
                        zip_or_postal_code: this.errors['zip_or_postal_code'] ?? '',
                        country: this.errors['country'] ?? '',
                    };
                }
                if (response.data.message) {
                    this.$emit('message', { text: response.data.message, error: true });
                }
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        initialize: function(form) {
            this.form = form;
        },
        completed: function(value) {
            this.passwordRequirementsMet = value;
        },
        getLabel: function(value) {
            return value.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase());
        },
        getErrors: function(value) {
            return typeof(this.errors[value]) !== 'undefined' ? this.errors[value] : [];
        },
    },
    beforeRouteLeave: function(to, from, next) {
        // Once registered and saved the user can leave without issue
        if (!this.registered) {
            this.$refs.registrationForm.leaving(to, from, next);
        } else {
            next();
        }
    },
    components: {
        'requirements': PasswordRequirements,
    }
}
</script>