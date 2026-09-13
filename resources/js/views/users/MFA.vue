<template>
    <div id="mfa-form">
        <div class="cz-account-row">
            <div>
                <h6 class="card-title mb-1">Multi-Factor Auth</h6>
                <p class="note mb-0">Add two-step verification for extra security.</p>
            </div>
            <div class="form-check form-switch cz-mfa-toggle">
                <input
                    id="mfa-toggle"
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    :checked="value"
                    :disabled="submitting"
                    @change="toggle(!value)">
            </div>
        </div>
    </div>
</template>
<script>
function initialize() {
    return {
        submitting: false,
    };
}
export default {
    data: function() {
        return {
            ...initialize(),
        };
    },
    methods: {
        reset: function() {
            Object.assign(this.$data, initialize());
        },
        toggle: function(value) {
            this.submitting = true;
            axios.patch(`/user/${this.user}/mfa`).then(({ data }) => {
                this.$message.success(data?.message || 'Multi-factor authentication updated successfully.');
                this.value = value;
            }).catch(({ response }) => {
                if (response?.data?.message) {
                    this.$message.danger(response.data.message);
                    return;
                }
                this.$message.danger('Unable to update multi-factor authentication right now.');
            }).finally(() => {
                setTimeout(() => {
                    this.submitting = false;
                }, 1000);
            });
        }
    },
    computed: {
        value: {
            get: function () {
                return this.modelValue;
            },
            set: function (value) {
                this.$emit('update:modelValue', value);
            }
        }
    },
    props: {
        modelValue: { type: Boolean, default: false },
        user: { type: [String, Number] }
    }
}
</script>