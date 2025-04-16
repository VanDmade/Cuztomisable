<template>
    <div class="tablelify-page">
        <div class="row mb-4">
            <div class="col col-md-8 col-12">
                <slot name="header"></slot>
            </div>
            <div class="col col-md-4 col-12">
                <div class="input-group">
                    <ee-input label="Search" v-model="search" type="input" id="search" :disabled="disabled" hide-details/>
                    <button type="button" class="btn btn-secondary" @click="query">Refresh</button>
                </div>
            </div>
        </div>
        <table class="table table-striped table-bordered tablelify" cellpadding="0" cellspacing="0">
            <thead v-if="headersList.length != 0">
                <tr>
                    <th v-for="(header, headerIndex) in headersList" :key="'header-'+headerIndex"
                        class="tablelify-header"
                        :class="{ 'tablelify-sortable': header.sortable }"
                        :width="typeof(header.width) != 'undefined' ? header.width : 'auto'"
                        @click="sort(headerIndex)">
                        <span class="tablelify-sorter">
                            <i class="fa-solid tablelift-sort" :class="{ 'fa-sort-up': header.sort == 'asc', 'fa-sort-down': header.sort == 'desc' }"></i>
                            <i v-if="header.sort == ''" class="fa-solid fa-sort tablelify-sort-hover"></i>
                        </span>
                        <span class="tablelify-header-text">{{ header.name }}</span>
                    </th>
                </tr>
            </thead>
            <tbody v-if="loading"><tr><td :colspan="headersList.length" class="text-center"><b>Loading...</b></td></tr></tbody>
            <tbody v-else-if="error != null"><tr><td :colspan="headersList.length" class="text-center text-danger"><b>{{ error }}</b></td></tr></tbody>
            <tbody v-else-if="data.length == 0"><tr><td :colspan="headersList.length" class="text-center"><b>No Results Found...</b></td></tr></tbody>
            <tbody v-else>
                <tr v-for="(row, rowIndex) in data" :key="'row-'+rowIndex" class="tablelify-row">
                    <td v-for="(item, index) in headersList" :key="'item-'+index" class="tablelify-column" :class="{ 'pa-0': item.value == 'image' }">
                        <label class="tablelify-header-text">{{ item.name }}</label>
                        <slot v-bind="row" :name="item.value"><div class="tablelify-data">{{ typeof(row[item.value]) !== 'undefined' && row[item.value] != null ? (row[item.value] + '') : '' }}</div></slot>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="row tablelify-pagination">
            <div class="col col-md-2 col-12">
                <ee-select v-model="size" :disabled="disabled" :items="countOptions" @change="query" />
            </div>
            <div v-if="totalPages > 0" class="col col-md-9 offset-md-1 col-12" :class="{ 'text-right': !breakpoint('sm'), 'text-center': breakpoint('sm') }">
                <button type="button"
                    :disabled="page <= 1 || disabled"
                    @click="page--; query();"
                    class="btn btn-secondary mr-1 tablelify-button tablelify-pagination-button"><i class="fa-solid fa-arrow-left"></i></button>
                <span v-for="number in totalPages">
                    <button type="button"
                        v-if="showButton(number)"
                        :disabled="disabled"
                        class="btn mr-1 tablelify-button"
                        :class="{ 'btn-primary': page == number, 'btn-light': page != number }"
                        @click="changePage(number);">{{ number }}</button>
                    <span v-else-if="!showButton(number) && showButton(number+1)" :class="{'mx-3': !breakpoint('sm'), 'mx-2': breakpoint('sm') }">...</span>
                </span>
                <button type="button"
                    :disabled="page == totalPages || totalPages == 0 || disabled"
                    @click="page++; query();"
                    class="btn btn-secondary tablelify-button tablelify-pagination-button"><i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data: function() {
        return {
            loading: true,
            disabled: true,
            error: null,
            headersList: [],
            data: [],
            searchTImeout: null,
            column: this.initialSortColumn,
            direction: this.initialSortDirection,
            size: 10,
            search: '',
            page: 1,
            totalPages: 1,
            countOptions: [
                { value: 5, text: '5' },
                { value: 10, text: '10' },
                { value: 25, text: '25' },
                { value: 'all', text: 'All' },
            ],
        }
    },
    created: function() {
        this.query();
    },
    methods: {
        query: function() {
            // Resets values for the query to load correctly
            this.loading = true;
            this.disabled = false;
            this.error = null;
            this.data = [];
            // Sets up the query based on the inputs
            var query = {
                search: this.search,
                page: this.page,
                size: this.size,
                column: this.column,
                direction: this.direction,
            };
            axios.get(this.url, { params: query }).then(({ data }) => {
                this.data = data.data;
                this.page = data.page;
                this.totalPages = data.total_pages;
            }).catch((error) => {
                this.error = error.response.data.message ?? error;
            }).finally(() => {
                this.loading = false;
                this.disabled = false;
            });
        },
        sort: function(index) {
            if (!this.headersList[index].sortable) {
                return false;
            }
            // Iterates through the list of headers to allow for ease of sorting and prevention of multiple sorts at the same time.
            for (var i = 0; i < this.headersList.length; i++) {
                if (i != index) {
                    this.headersList[i].sort = '';
                }
            }
            var sort = this.headersList[index].sort;
            this.direction = this.headersList[index].sort = sort == '' ? 'asc' : (sort == 'asc' ? 'desc' : '');
            this.column = this.direction == '' ? this.initialSortColumn : this.headersList[index].value;
            if (this.column == this.initialSortColumn) {
                this.direction = this.initialSortDirection;
            }
            this.query();
        },
        showButton: function(number) {
            this.page = parseInt(this.page);
            number = parseInt(number);
            var totalAround = this.breakpoint('sm') ? 1 : 2;
            return this.totalPages <= 5 || number == 1 || number == this.totalPages ||
                (number <= (this.page + totalAround + (this.page == 1 ? 2 : (this.page == 2 ? 1 : 0))) &&
                 number >= this.page - totalAround - (this.page == this.totalPages ? 1 : (this.page == this.totalPages - 1 ? 1 : 0)));
        },
        changePage: function(page) {
            // MAkes sure the user doesn't constantly reload the same data over and over
            if (page != this.page) {
                this.page = page;
                this.query();
            }
        },
    },
    watch: {
        headers: {
            immediate: true,
            handler: function(headers) {
                for (var i = 0; i < headers.length; i++) {
                    headers[i].sort = '';
                }
                this.headersList = JSON.parse(JSON.stringify(headers));
            },
            deep: true,
        },
        search: {
            handler: function(search) {
                clearTimeout(this.searchTImeout);
                this.searchTImeout = setTimeout(() => {
                    this.query();
                }, 500);
            },
            deep: true,
        },
    },
    props: {
        headers: { type: Array, default: [] },
        initialSortColumn: { type: String, default: 'id' },
        initialSortDirection: { type: String, default: 'desc' },
        url: { type: String, required: true },
    }
}
</script>