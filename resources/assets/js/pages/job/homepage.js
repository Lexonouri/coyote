import Vue from 'vue';
import Config from '../../libs/config';
import VueJob from '../../components/job.vue';
import VueJobTiny from '../../components/job-tiny.vue';
import VuePagination from '../../components/pagination.vue';
import axios from 'axios';
import store from '../../store';
import * as Ps from 'perfect-scrollbar';

new Vue({
    el: '#page-job',
    delimiters: ['${', '}'],
    components: {
        'vue-job': VueJob,
        'vue-pagination': VuePagination,
        'vue-job-tiny': VueJobTiny
    },
    data: window.data,
    store,
    created () {
        store.state.subscriptions.subscribed = window.data.subscribed;
    },
    mounted () {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = Config.csrfToken();

        this.$refs.q.addEventListener('search', this.search);
        this.$refs.city.addEventListener('search', this.search);

        window.onpopstate = e => {
            this.jobs = e.state.jobs;
            this.input = e.state.input;
        };

        this.initYScrollbar(document.querySelector('#panel-published'));
        this.initYScrollbar(document.querySelector('#panel-subscribed'));

        this.initXScrollbar(document.querySelector('#filter-location'));
        this.initXScrollbar(document.querySelector('#filter-tech'));
    },
    filters: {
        capitalize (value) {
            return value.charAt(0).toUpperCase() + value.slice(1);
        }
    },
    methods: {
        toggleTag (tag) {
            this.toggle(this.input.tags, tag);
        },

        toggleLocation (location) {
            this.toggle(this.input.locations, location);
        },

        toggle (input, item) {
            const index = input.indexOf(item);

            if (index > -1) {
                input.splice(index, 1);
            }
            else {
                input.push(item);
            }

            this.search();
        },

        toggleRemote () {
            if (this.input.remote) {
                this.input.remote = null;
            }
            else {
                this.input.remote = 1;
                this.input.remote_range = 100;
            }

            this.search();
        },

        changePage (page) {
            this.jobs.meta.current_page = page;
            this.input.page = page;

            this.search();

            window.scrollTo(0,0);
        },

        search () {
            const input = {
                q: this.input.q,
                city: this.input.city,
                tags: this.input.tags,
                sort: this.input.sort,
                page: this.input.page,
                salary: this.input.salary,
                currency: this.input.currency,
                remote: this.input.remote,
                remote_range: this.input.remote_range,
                locations: this.input.locations,
                json: 1 // add json param to distinguish JSON url from "normal" request
            };

            this.skeleton = true;

            axios.get(this.$refs.searchForm.action, {params: input})
                .then(response => {
                    this.jobs = response.data.jobs;
                    this.defaults = response.data.defaults;

                    window.history.pushState(response.data, '', response.data.url);
                })
                .catch(error => {
                    console.log(error);
                })
                .then(() => {
                    this.skeleton = false;
                });
        },

        includesLocation (location) {
            return this.input.locations.includes(location);
        },

        includesTag (tag) {
            return this.input.tags.includes(tag);
        },

        initYScrollbar (container) {
            if (container) {
                Ps.initialize(container);
            }
        },

        initXScrollbar (container) {
            if (container) {
                Ps.initialize(container, { suppressScrollY: true });

                window.addEventListener('resize', () => Ps.update(container));
            }
        },

        isTabSelected (tab) {
            return this.selectedTab === tab;
        },

        selectTab (tab) {
            this.selectedTab = tab;
        },

        getTabDropdownClass (tab) {
            return {'fa-angle-up': this.selectedTab !== tab, 'fa-angle-down': this.selectedTab === tab};
        }
    },
    computed: {
        defaultSort: {
            get () {
                return this.input.sort ? this.input.sort : this.defaults.sort;
            },
            set (value) {
                this.input.sort = value;
            }
        },

        defaultCurrency: {
            get () {
                return this.input.currency ? this.input.currency : this.defaults.currency;
            },
            set (value) {
                this.input.currency = value;
            }
        },

        subscribedStore () {
            return store.state.subscriptions.subscribed;
        }
    }
});
