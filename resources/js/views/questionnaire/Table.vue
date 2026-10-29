<!--
    Admin page for the questionnaire's questions - like Settings, one row each, and dragged up and down to set the
    order they're asked in. Each opens in a form to edit, and questions can be switched off without losing answers.
-->
<template>
    <div class="page table-page container-fluid">
        <div class="cz-questionnaire-header mb-6">
            <div>
                <h3 class="card-title">Questionnaire</h3>
                <h6 class="card-subtitle text-muted mb-0">
                    The questions users are asked until they've answered them - new and existing accounts alike. Drag a
                    question to change the order.
                </h6>
            </div>
            <button type="button" class="button button--primary" @click="edit(null)">Add Question</button>
        </div>
        <cz-loading v-if="loading" :loading="loading" :large="false" :full="false" />
        <div v-else class="cz-settings-list">
            <!-- Laid out like a question row, so the empty list looks the same as a full one -->
            <div v-if="questions.length === 0" class="cz-settings-row">
                <div class="cz-settings-row-info">
                    <h6 class="card-title mb-1">No questions yet</h6>
                    <p class="note mb-0">Add the first one with Add Question.</p>
                </div>
            </div>
            <div
                v-for="(question, index) in questions"
                :key="question.id"
                class="cz-settings-row cz-questionnaire-row"
                :class="{
                    'cz-questionnaire-row--dragging': dragIndex === index,
                    'cz-questionnaire-row--over': overIndex === index && dragIndex !== index,
                    'cz-questionnaire-row--off': !question.active,
                }"
                draggable="true"
                @dragstart="startDrag(index, $event)"
                @dragover.prevent="overIndex = index"
                @drop.prevent="drop(index)"
                @dragend="endDrag">
                <span class="material-icons cz-questionnaire-grip" aria-hidden="true">drag_indicator</span>
                <span class="cz-questionnaire-number">{{ index + 1 }}</span>
                <div class="cz-settings-row-info">
                    <h6 class="card-title mb-1">{{ question.title }}</h6>
                    <p class="note mb-0">
                        {{ question.key }} · {{ typeLabel(question.type) }}
                        <template v-if="question.target"> · saves to {{ question.target }}</template>
                        · {{ question.answers }} {{ question.answers === 1 ? 'answer' : 'answers' }}
                    </p>
                </div>
                <span class="cz-questionnaire-badge" :class="question.required ? 'cz-questionnaire-badge--on' : ''">{{ question.required ? 'Required' : 'Optional' }}</span>
                <span v-if="!question.active" class="cz-questionnaire-badge">Off</span>
                <button type="button" class="button button--secondary" @click="edit(question.id)">Edit</button>
                <button type="button" class="button button--danger" @click="confirmDelete(question.id)">Delete</button>
            </div>
        </div>
        <cz-modal ref="questionModal" modal-width="760px">
            <questionnaire-form
                :id="activeId"
                :choices="choices"
                :next-order="questions.length + 1"
                v-on:close="$refs.questionModal.close()"
                v-on:message="setMessage"
                v-on:redraw="load" />
        </cz-modal>
        <cz-modal ref="deleteModal" modal-width="420px">
            <cz-delete
                v-if="deleteId != null"
                :id="deleteId"
                url="/questionnaire/question/"
                title="Delete this question?"
                v-on:redraw="load"
                v-on:close="closeDelete">
                <p class="note mb-0">It won't be asked any more. Answers people already gave are kept.</p>
            </cz-delete>
        </cz-modal>
    </div>
</template>
<script>
import QuestionnaireForm from './Form.vue';
export default {
    data: function() {
        return {
            questions: [],
            choices: { types: [], sources: [], tables: {} },
            loading: true,
            activeId: null,
            deleteId: null,
            dragIndex: null,
            overIndex: null,
        };
    },
    created: function() {
        this.load();
    },
    methods: {
        load: function() {
            axios.get('/questionnaire/questions').then(({ data }) => {
                this.questions = data.questions ?? [];
                this.choices = data.choices ?? this.choices;
            }).finally(() => {
                this.loading = false;
            });
        },
        edit: function(id) {
            this.activeId = id;
            this.$refs.questionModal.open();
        },
        confirmDelete: function(id) {
            this.deleteId = id;
            this.$refs.deleteModal.open();
        },
        closeDelete: function() {
            this.$refs.deleteModal.close();
            this.deleteId = null;
        },
        typeLabel: function(type) {
            return ({ boolean: 'Yes / no', select: 'Pick one', multi_select: 'Pick several', text: 'Text', number: 'Number', date: 'Date' })[type] ?? type;
        },
        startDrag: function(index, event) {
            this.dragIndex = index;
            event.dataTransfer.effectAllowed = 'move';
            // Firefox won't start a drag without some data set
            event.dataTransfer.setData('text/plain', String(index));
        },
        drop: function(index) {
            if (this.dragIndex === null || this.dragIndex === index) {
                return;
            }
            const moved = this.questions.splice(this.dragIndex, 1)[0];
            this.questions.splice(index, 0, moved);
            this.saveOrder();
        },
        endDrag: function() {
            this.dragIndex = null;
            this.overIndex = null;
        },
        saveOrder: function() {
            axios.post('/questionnaire/questions/order', { ids: this.questions.map((q) => q.id) }).then(({ data }) => {
                this.$message.push({ text: data.message });
            }).catch(({ response }) => {
                this.$message.push({ text: response?.data?.message ?? 'The order could not be saved.', color: 'danger' });
                this.load();
            });
        },
        setMessage: function(message) {
            this.$message.push({ ...(message ?? {}), color: message?.error ? 'danger' : message?.color });
        },
    },
    components: {
        QuestionnaireForm,
    },
}
</script>
<style>
.cz-questionnaire-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
}
/* The button keeps to one line - the text beside it wraps instead */
.cz-questionnaire-header .button {
    flex-shrink: 0;
    white-space: nowrap;
}
.cz-questionnaire-row {
    gap: 12px;
    cursor: grab;
    transition: background-color 0.15s ease, opacity 0.15s ease;
}
.cz-questionnaire-row--dragging {
    opacity: 0.4;
}
.cz-questionnaire-row--over {
    box-shadow: inset 0 3px 0 #009688;
}
.cz-questionnaire-row--off .cz-settings-row-info {
    opacity: 0.55;
}
.cz-questionnaire-grip {
    color: #9CA3AF;
}
.cz-questionnaire-number {
    min-width: 24px;
    font-weight: 700;
    color: #6B7280;
}
.cz-questionnaire-badge {
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    background: rgba(107, 114, 128, 0.12);
    color: #6B7280;
}
.cz-questionnaire-badge--on {
    background: rgba(0, 150, 136, 0.12);
    color: #00796B;
}
</style>
