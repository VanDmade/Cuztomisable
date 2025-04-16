<template>
    <div id="registration-page">
        <div class="card ma-2 pa-6">
            <h3 class="card-title">Sign Up!</h3>
            <h6 class="card-subtitle mb-2 text-muted">We are always welcoming to new users!</h6>
            <form @submit.prevent="save" class="mt-4">
                <div v-for="(field, name) in $cuztomisable.registration.fields" class="registration-field-container">
                    <div class="registration-field" v-if="field !== false">
                        <component :is="getType(name)"
                            :label="getLabel(name)"
                            v-model="form[name]"
                            :items="getList(name)"
                            :required="getRequired(name)"
                            :errors="getErrors(name)"
                            :disabled="submitting"></component>
                    </div>
                </div>
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
            </form>
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
            errors: [],
            passwordRequirementsMet: false,
            formKeys: Object.keys(this.$cuztomisable.registration.fields),
            form: {
                name: '',
                first_name: '',
                middle_name: '',
                last_name: '',
                suffix: '',
                title: '',
                address: '',
                gender: '',
                username: '',
                email: '',
                phone: '',
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
            formData.append('first_name', this.form.first_name ?? '');
            formData.append('middle_name', this.form.middle_name ?? '');
            formData.append('last_name', this.form.last_name ?? '');
            formData.append('suffix', this.form.suffix ?? '');
            formData.append('title', this.form.title ?? '');
            formData.append('address', this.form.address ?? '');
            formData.append('gender', this.form.gender ?? '');
            formData.append('username', this.form.username ?? '');
            formData.append('email', this.form.email ?? '');
            formData.append('phone', this.form.phone ?? '');
            formData.append('password', this.form.password ?? '');
            axios.post('/register', formData).then(({ data }) => {

            }).catch(({ response }) => {
                if (response.data.errors) {
                    this.errors = response.data.errors;
                } else if (response.data.message) {

                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
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
        getRequired: function(value) {
            return typeof(this.$cuztomisable.registration.fields[value]) !== 'undefined' &&
                this.$cuztomisable.registration.fields[value] == true ? true : false; 
        },
        getList: function(value) {
            return this.$cuztomisable.registration.fields[value].list ?? [];
        },
        getType: function(value) {
            let type = this.$cuztomisable.registration.fields[value].type ?? 'input';
            if (typeof(this.$cuztomisable.registration.fields[value].list) != 'undefined') {
                type = 'select';
            }
            return 'fm-'+type;
        }
    },
    components: {
        'requirements': PasswordRequirements,
    }
}
</script>