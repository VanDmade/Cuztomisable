<template>
    <div id="profile-page" class="page">
        <div class="card pa-6" style="max-width: 600px; margin: auto;">
            <div class="d-flex align-items-center position-relative">
                <label for="cz-image-upload" class="position-relative me-3 cz-image-input" title="Change photo">
                    <cz-image :src="preview || user?.image || $url+'profile.png'"
                        alt="User Photo"
                        img-class="rounded-circle cz-image" />
                    <span class="cz-image-toggle material-icons" aria-hidden="true">edit</span>
                    <input
                        type="file"
                        id="cz-image-upload"
                        class="d-none"
                        accept="image/*"
                        @change="handleChange" />
                </label>
                <div>
                    <h4 class="mb-0">{{ user?.name }}</h4>
                    <small class="text-muted">{{ user?.email }}</small>
                </div>
            </div>
            <cz-form ref="profileForm"
                :form="form"
                class="mt-4"
                @save="save"
                ask-before-leaving>
                <cz-input
                    label="Name"
                    v-model="form.name"
                    type="text"
                    :errors="errors.name"
                    :disabled="submitting" />
                <cz-input
                    v-if="!$cuztomisable.login_with.email && !$cuztomisable.login_with.phone"
                    label="Username"
                    v-model="form.username"
                    type="text"
                    :errors="errors.username"
                    :disabled="submitting" />
                <cz-input
                    label="Email"
                    v-model="form.email"
                    type="email"
                    :errors="errors.email"
                    :disabled="submitting" />
                <cz-phone
                    label="Phone"
                    v-model="form.phone"
                    :errors="errors.phone"
                    :disabled="submitting"
                    is-mobile
                    default />
                <cz-address
                    v-if="$cuztomisable.registration.address !== false"
                    label="Address"
                    v-model="form.address"
                    :errors="errors.address"
                    :disabled="submitting"
                    :hasAddressTwo="$cuztomisable.registration.address.address_two"
                    :hasAddressThree="$cuztomisable.registration.address.address_three" />
                <cz-checkbox
                    label="Multi-Factor Authentication"
                    v-model="form.mfa"
                    type="checkbox"
                    class="mt-3"
                    :errors="errors.mfa"
                    hide-details
                    :disabled="submitting" />
                <div class="form-buttons">
                    <button type="submit" class="button button--primary button--block" :disabled="submitting">Save Changes</button>
                </div>
            </cz-form>
        </div>
    </div>
</template>
<script>
const ADDRESS_FIELDS = ['address', 'address_two', 'address_three', 'city', 'state_or_province', 'zip_or_postal_code', 'country'];

// Anything missing on the saved user (including old rows saved as the word "null") becomes an empty string, so no
// field can ever show "null" (or send it back)
const text = (value) => value === null || value === undefined || value === 'null' || value === 'undefined' ? '' : String(value);

export default {
    data: function() {
        return {
            submitting: false,
            errors: [],
            // Only set once a new photo is picked - until then the saved one (or the placeholder) shows
            preview: null,
            user: this.$store.state.user,
            form: {},
        }
    },
    methods: {
        formFrom: function(user) {
            const address = {};
            for (const field of ADDRESS_FIELDS) {
                address[field] = text(user.address?.[field]);
            }
            return {
                name: text(user.name),
                username: text(user.username),
                email: text(user.email),
                phone: { ...(user.phone ?? {}), number: text(user.phone?.number), country_code: text(user.phone?.country_code) },
                address: address,
                mfa: !!user.mfa,
                image: null,
            };
        },
        save: function() {
            let formData = new FormData();
            formData.append('name', text(this.form.name));
            // Only a newly picked file is sent - the saved photo is a URL and stays as it is
            if (this.form.image instanceof File) {
                formData.append('image', this.form.image);
            }
            formData.append('mfa', this.form.mfa ? '1' : '0');
            if (!this.$cuztomisable.login_with.email && !this.$cuztomisable.login_with.phone) {
                formData.append('username', text(this.form.username));
            }
            formData.append('email', text(this.form.email));
            formData.append('phone', text(this.form.phone?.number));
            formData.append('country_code', text(this.form.phone?.country_code));
            if (this.$cuztomisable.registration.address !== false) {
                for (const field of ADDRESS_FIELDS) {
                    formData.append(field, text(this.form.address?.[field]));
                }
            }
            this.submitting = true;
            axios.post('/profile', formData).then(({ data }) => {
                this.$message.success(data.message);
                this.errors = [];
                if (data.user) {
                    this.$store.commit('SET_USER', { ...this.$store.state.user, ...data.user });
                    this.preview = null;
                }
            }).catch(({ response }) => {
                if (response?.data?.errors) {
                    this.errors = response.data.errors;
                    this.errors['address'] = {};
                    for (const field of ADDRESS_FIELDS) {
                        this.errors['address'][field] = this.errors[field] ?? '';
                    }
                }
                if (response?.data?.message) {
                    this.$message.error(response.data.message);
                }
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1500);
            });
        },
        handleChange: function(event) {
            const file = event.target.files[0]
            if (file) {
                this.form.image = file;
                this.preview = URL.createObjectURL(file);
            }
        }
    },
    watch: {
        '$store.state.user': {
            immediate: true,
            handler: function(user) {
                this.user = user;
                if (user == null) {
                    return;
                }
                this.form = this.formFrom(user);
            },
            deep: true,
        }
    }
}
</script>
