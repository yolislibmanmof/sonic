/**
 * SONIC OPAC — Vue Components Engine
 * Mengganti app.js default: render koleksi populer/terbaru/top reader dengan kartu SONIC
 */
(function () {
    if (typeof Vue === 'undefined') return;

    function thumb(img) {
        if (!img) return './images/default/image.png';
        return './lib/minigalnano/createthumb.php?filename=' + encodeURIComponent('images/docs/' + img) + '&width=240';
    }
    function personThumb(img) {
        if (!img) return './images/persons/person.png';
        return './lib/minigalnano/createthumb.php?filename=' + encodeURIComponent('images/persons/' + img) + '&width=120';
    }
    function detailUrl(id) { return 'index.php?p=show_detail&id=' + id; }
    function pick(res) {
        if (!res) return [];
        if (Array.isArray(res)) return res;
        return res.data || res.biblio || res.results || res.items || res.members || res.subjects || [];
    }

    /* ===== KOLEKSI (grid buku) ===== */
    Vue.component('slims-collection', {
        props: ['url', 'limit'],
        data: function () { return { items: [], loaded: false }; },
        created: function () {
            var self = this;
            fetch(this.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) { self.items = pick(res).slice(0, parseInt(self.limit || 10, 10)); self.loaded = true; })
                .catch(function () { self.loaded = true; });
        },
        methods: { thumb: thumb, detailUrl: detailUrl },
        template:
            '<div class="sonic-biblio-grid">' +
            '<div v-if="!loaded" v-for="n in 8" :key="n" class="sonic-book sonic-skeleton">' +
            '<div class="sonic-skeleton-cover"></div><div class="sonic-skeleton-line"></div><div class="sonic-skeleton-line short"></div></div>' +
            '<a v-else v-for="b in items" :key="b.biblio_id" class="sonic-book" :href="detailUrl(b.biblio_id)">' +
            '<div class="sonic-book-cover"><img loading="lazy" :src="thumb(b.image)" :alt="b.title"></div>' +
            '<h4 class="sonic-book-title">{{ b.title }}</h4>' +
            '<p class="sonic-book-author">{{ b.author || b.authors || "&nbsp;" }}</p>' +
            '</a>' +
            '</div>'
    });

    /* ===== GROUP SUBJECT (chips topik) ===== */
    Vue.component('slims-group-subject', {
        props: ['url'],
        data: function () { return { items: [], loaded: false }; },
        created: function () {
            var self = this;
            fetch(this.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) { self.items = pick(res).slice(0, 12); self.loaded = true; })
                .catch(function () { self.loaded = true; });
        },
        methods: {
            label: function (s) { return (typeof s === 'object') ? (s.topic || s.subject || s.name) : s; },
            href: function (s) { return 'index.php?subject=' + encodeURIComponent(this.label(s)) + '&search=search'; }
        },
        template:
            '<div class="sonic-quick-tags" style="justify-content:flex-start;">' +
            '<a v-if="loaded" v-for="(s, i) in items" :key="i" class="sonic-tag" :href="href(s)"><i class="fas fa-tags"></i>{{ label(s) }}</a>' +
            '</div>'
    });

    /* ===== TOP MEMBER (kartu pembaca) ===== */
    Vue.component('slims-group-member', {
        props: ['url'],
        data: function () { return { items: [], loaded: false }; },
        created: function () {
            var self = this;
            fetch(this.url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (res) { self.items = pick(res).slice(0, 6); self.loaded = true; })
                .catch(function () { self.loaded = true; });
        },
        methods: { personThumb: personThumb },
        template:
            '<div class="sonic-topics">' +
            '<div v-if="loaded" v-for="(m, i) in items" :key="i" class="sonic-topic" style="cursor:default;">' +
            '<div class="sonic-topic-icon" style="overflow:hidden;border-radius:50%;"><img :src="personThumb(m.image || m.member_image)" style="width:100%;height:100%;object-fit:cover;"></div>' +
            '<span class="sonic-topic-label">{{ m.name || m.member_name }}</span>' +
            '<span class="sonic-tag" style="cursor:default;"><i class="fas fa-book-reader"></i>{{ m.total }} {{ m.total_title || 0 }}</span>' +
            '</div>' +
            '</div>'
    });

    /* ===== MOUNT ===== */
    var sonicHasTags = document.querySelector('slims-collection, slims-group-subject, slims-group-member');
    if (sonicHasTags && document.getElementById('slims-home')) {
        new Vue({ el: '#slims-home' });
    }
})();