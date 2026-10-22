<!--
    Adds or edits one questionnaire question - what kind of answer it takes, its text (plain or language keys), its
    choices, and which column the answer is copied onto.
-->
<template>
    <div class="questionnaire-form">
        <cz-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <form v-else @submit.prevent="save">
            <h3 class="card-title">{{ id ? 'Edit' : 'Add' }} Question</h3>
            <h6 class="card-subtitle mb-6 text-muted">Write the text as it should appear. To translate it, use a language key instead (e.g. questionnaire.favorite_drink.title).</h6>
            <div class="row">
                <div class="col-md-8 col-12">
                    <cz-input label="Key" v-model="form.key" :errors="errors.key" :disabled="submitting" />
                </div>
                <div class="col-md-4 col-12">
                    <cz-select label="Answer type" v-model="form.type" :items="typeItems" :errors="errors.type" :disabled="submitting" />
                </div>
            </div>
            <div class="row">
                <div class="col-md-8 col-12">
                    <cz-input label="Title" v-model="form.title" :errors="errors.title" :disabled="submitting" />
                    <cz-input label="Subtitle" v-model="form.subtitle" :errors="errors.subtitle" :disabled="submitting" />
                    <cz-textarea label="Description" v-model="form.description" :errors="errors.description" :disabled="submitting" rows="4" />
                </div>
                <div class="col-md-4 col-12">
                    <cz-upload
                        v-model="form.image"
                        :errors="errors.image_file ?? errors.image"
                        accept="image/png,image/jpeg,image/webp"
                        description="Shown above the question."
                        square
                        single
                        image
                        thumbnail />
                </div>
            </div>

            <!-- Choices -->
            <template v-if="hasChoices">
                <hr class="mt-4 mb-4">
                <h5 class="card-title mb-3">{{ form.type === 'boolean' ? 'Yes / no labels' : 'Choices' }}</h5>
                <cz-select
                    v-if="form.type !== 'boolean' && choices.sources.length"
                    label="Choices come from"
                    v-model="form.source"
                    :items="sourceItems"
                    :errors="errors.source"
                    :disabled="submitting" />
                <!-- Yes / no always answers true or false - only what each button says changes -->
                <div v-if="form.type === 'boolean'" class="row">
                    <div class="col-md-6 col-12">
                        <cz-input label="Yes label" v-model="form.options[0].label" :errors="errors['options.0.label']" :disabled="submitting" />
                    </div>
                    <div class="col-md-6 col-12">
                        <cz-input label="No label" v-model="form.options[1].label" :errors="errors['options.1.label']" :disabled="submitting" />
                    </div>
                </div>
                <template v-else-if="form.source === LIST">
                    <cz-multi
                        v-model="form.options"
                        add-label="Add Choice"
                        :template="{ value: '', label: '' }"
                        :disabled="submitting"
                        hide-details>
                        <template v-slot:entry="item">
                            <cz-input v-model="form.options[item.index].value" label="Value" :disabled="submitting" hide-details />
                            <cz-input v-model="form.options[item.index].label" label="Label" :disabled="submitting" hide-details />
                        </template>
                    </cz-multi>
                    <ul v-if="optionErrors.length" class="form-errors cz-form-errors mb-2">
                        <li v-for="(error, index) in optionErrors" :key="index" class="form-error cz-form-error">{{ error }}</li>
                    </ul>
                </template>
                <template v-if="form.type === 'select'">
                    <cz-checkbox label='Offer an "I can&apos;t find it" choice' v-model="form.allow_other" :disabled="submitting" hide-details />
                    <cz-input v-if="form.allow_other" class="mt-3" label='"I can&apos;t find it" label' v-model="form.other_label" :errors="errors.other_label" :disabled="submitting" />
                </template>
            </template>

            <!-- Typed answers, and the search box for choices that come from the app -->
            <template v-if="!hasChoices || (form.type !== 'boolean' && form.source !== LIST)">
                <hr class="mt-4 mb-4">
                <h5 class="card-title mb-3">Answer box</h5>
                <cz-input label="Placeholder" v-model="form.placeholder" :errors="errors.placeholder" :disabled="submitting" />
                <div v-if="form.type === 'number' || form.type === 'text'" class="row">
                    <div class="col-md-6 col-12">
                        <cz-input :label="form.type === 'text' ? 'Shortest length' : 'Lowest number'" v-model="form.min" type="number" :errors="errors.min" :disabled="submitting" />
                    </div>
                    <div class="col-md-6 col-12">
                        <cz-input :label="form.type === 'text' ? 'Longest length' : 'Highest number'" v-model="form.max" type="number" :errors="errors.max" :disabled="submitting" />
                    </div>
                </div>
            </template>

            <!-- Where the answer goes -->
            <hr class="mt-4 mb-4">
            <h5 class="card-title mb-1">Save the answer to</h5>
            <h6 class="card-subtitle mb-4 text-muted">The answer is kept either way - pick a column to also copy it onto, so the app can use it and change it later.</h6>
            <div class="row">
                <div class="col-md-4 col-12">
                    <cz-select label="Table" v-model="form.target_table" :items="tableItems" :errors="errors.target_table" :disabled="submitting" />
                </div>
                <div class="col-md-4 col-12">
                    <cz-select
                        label="Column"
                        v-model="form.target_column"
                        :items="columnItems"
                        :errors="errors.target_column"
                        :disabled="submitting || form.target_table === NONE" />
                </div>
                <div class="col-md-4 col-12">
                    <cz-input label="ID" v-model="form.target_key" :errors="errors.target_key" :disabled="submitting || form.target_table === NONE" />
                </div>
            </div>

            <!-- No order box - questions are put in order by dragging them on the list -->
            <hr class="mt-4 mb-4">
            <cz-checkbox label="Required" v-model="form.required" :disabled="submitting" hide-details />
            <cz-checkbox label="Active" v-model="form.active" :disabled="submitting" hide-details />

            <div class="mt-6">
                <button type="submit"
                    class="button button--primary"
                    :class="{ 'mb-2 button--block': breakpoint('sm'), 'mb-0 button-width': !breakpoint('sm') }"
                    :disabled="submitting">Save</button>
                <button type="button"
                    class="button button--secondary"
                    :class="{ 'mb-2 button--block': breakpoint('sm'), 'ml-4 mb-0 button-width': !breakpoint('sm') }"
                    :disabled="submitting"
                    @click="close">Nevermind</button>
            </div>
        </form>
    </div>
</template>
<script>
const TYPE_LABELS = { boolean: 'Yes / no', select: 'Pick one', multi_select: 'Pick several', text: 'Text', number: 'Number', date: 'Date' };
// "Nothing picked" options need a real value - an empty one makes the select's label act as though it's blank
const LIST = 'list';
const NONE = 'none';
// Yes / no questions always have these two values - only their labels change
const BOOLEAN_OPTIONS = () => [{ value: 'true', label: 'Yes' }, { value: 'false', label: 'No' }];

function blank(order) {
    return {
        key: '', order: order, type: 'select', title: '', subtitle: '', description: '', image: [],
        source: LIST, options: [{ value: '', label: '' }], allow_other: false, other_label: '',
        placeholder: '', min: '', max: '',
        target_table: NONE, target_column: '', target_key: 'id',
        required: true, active: true,
    };
}

export default {
    data: function() {
        return {
            loading: false,
            submitting: false,
            errors: {},
            form: blank(this.nextOrder),
            LIST: LIST,
            NONE: NONE,
        };
    },
    computed: {
        hasChoices: function() {
            return ['boolean', 'select', 'multi_select'].includes(this.form.type);
        },
        typeItems: function() {
            return this.choices.types.map((type) => ({ value: type, text: TYPE_LABELS[type] ?? type }));
        },
        sourceItems: function() {
            return [{ value: LIST, text: 'The choices listed below' }, ...this.choices.sources.map((source) => ({ value: source, text: `The app's ${source}` }))];
        },
        tableItems: function() {
            return [{ value: NONE, text: "Don't copy it anywhere" }, ...Object.keys(this.choices.tables ?? {}).map((table) => ({ value: table, text: table }))];
        },
        // Only the columns allowed_tables lists for the picked table
        columnItems: function() {
            return (this.choices.tables?.[this.form.target_table] ?? []).map((column) => ({ value: column, text: column }));
        },
        // Every choice's errors in one list under the choices
        optionErrors: function() {
            return Object.keys(this.errors).filter((key) => key === 'options' || key.startsWith('options.')).flatMap((key) => this.errors[key]);
        },
    },
    methods: {
        get: function() {
            this.loading = true;
            axios.get(`/questionnaire/question/${this.id}`).then(({ data }) => {
                const question = data.question;
                this.form = {
                    ...blank(question.order),
                    ...Object.fromEntries(Object.entries(question).map(([key, value]) => [key, value ?? ''])),
                    options: question.options?.length ? question.options : (question.type === 'boolean' ? BOOLEAN_OPTIONS() : [{ value: '', label: '' }]),
                    // The uploader works with a list of images - the question's one, by its address
                    image: question.image ? [{ id: 'current', path: question.image, full_url: question.image }] : [],
                    source: question.source || LIST,
                    target_table: question.target_table || NONE,
                    allow_other: !!question.allow_other,
                    required: !!question.required,
                    active: !!question.active,
                };
            }).catch(({ response }) => {
                this.$emit('message', { text: response?.data?.message ?? 'The question could not be loaded.', error: true });
            }).finally(() => {
                this.loading = false;
            });
        },
        save: function() {
            this.submitting = true;
            this.errors = {};
            // Multipart, since a new image comes along as a file
            const formData = new FormData();
            const fields = ['key', 'order', 'type', 'title', 'subtitle', 'description', 'source', 'other_label', 'placeholder',
                'min', 'max', 'target_table', 'target_column', 'target_key'];
            fields.forEach((field) => formData.append(field, this.form[field] ?? ''));
            formData.set('source', this.form.source === LIST ? '' : this.form.source);
            formData.set('target_table', this.form.target_table === NONE ? '' : this.form.target_table);
            ['allow_other', 'required', 'active'].forEach((field) => formData.append(field, this.form[field] ? 1 : 0));
            if (this.form.type === 'boolean' || (this.hasChoices && this.form.source === LIST)) {
                this.form.options.forEach((option, index) => {
                    formData.append(`options[${index}][value]`, option.value ?? '');
                    formData.append(`options[${index}][label]`, option.label ?? '');
                });
            }
            const image = Array.isArray(this.form.image) ? this.form.image[0] : null;
            if (image?.file) {
                formData.append('image_file', image.file);
            } else {
                // Keeps the current image, or clears it when it was taken off
                formData.append('image', image?.full_url ?? image?.path ?? '');
            }
            const id = this.id ? `/${this.id}` : '';
            axios.post(`/questionnaire/question${id}`, formData).then(({ data }) => {
                this.$emit('message', { text: data.message });
                this.$emit('redraw');
                this.close();
            }).catch(({ response }) => {
                this.errors = response?.data?.errors ?? {};
                if (response?.data?.message) {
                    this.$emit('message', { text: response.data.message, error: true });
                }
            }).finally(() => {
                this.submitting = false;
            });
        },
        close: function() {
            this.$emit('close');
            setTimeout(() => {
                this.errors = {};
                this.form = blank(this.nextOrder);
            }, 250);
        },
    },
    watch: {
        id: {
            immediate: true,
            handler: function(id) {
                this.errors = {};
                if (id) {
                    this.get();
                } else {
                    this.form = blank(this.nextOrder);
                }
            },
        },
        'form.type': function(type, previous) {
            // Yes / no gets its fixed true / false values; switching away from it starts the choices fresh
            if (type === 'boolean' && !this.loading) {
                this.form.options = BOOLEAN_OPTIONS();
                this.form.source = LIST;
            } else if (previous === 'boolean' && !this.loading) {
                this.form.options = [{ value: '', label: '' }];
            }
        },
        'form.target_table': function(table) {
            if (this.loading) return;
            // The users table's own id is the user, anything else points back at it
            this.form.target_key = table === 'users' || table === NONE ? 'id' : 'user_id';
            // A column from the old table isn't one of the new table's
            if (!(this.choices.tables?.[table] ?? []).includes(this.form.target_column)) this.form.target_column = '';
        },
    },
    props: {
        id: { type: [String, Number], default: null },
        choices: { type: Object, default: () => ({ types: [], sources: [], tables: {} }) },
        nextOrder: { type: Number, default: 1 },
    },
}
</script>
