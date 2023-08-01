/**
 * Editor button
 */
if (typeof window.toolbar !== 'undefined') {
    window.toolbar[window.toolbar.length] = {
        type: "plugindoi",
        title: LANG.plugins.doi.toolbarButton,
        icon: "../../plugins/doi/img/library.png",
   };
}

function tb_plugindoi(btn, props, edid) {
    PluginDoi.edid = edid;
    PluginDoi.buildSyntax();
}

const PluginDoi = {
    edid: null,

    /**
     * Ask for ID and check format to determine DOI or ISBN syntax
     */
    buildSyntax: function () {

        const ident = prompt(LANG.plugins.doi.prompt);
        if (!ident) return;

        const isbnRegex = new RegExp('^(?=(?:\\D*\\d){10}(?:(?:\\D*\\d){3})?$)[\\d-]+$', 'i');
        if (ident.match(isbnRegex)) {
            PluginDoi.insert('isbn', ident);
            return;
        }

        const doiRegex = new RegExp('(10[.][0-9]{4,}[^\\s"\\/<>]*\\/[^\\s"<>]+)');
        if (ident.match(doiRegex)) {
            PluginDoi.insert('doi', ident);
            return;
        }

        alert(LANG.plugins.doi.noMatch);
    },

    /**
     * Insert syntax
     *
     * @param {string} key
     * @param {string} ident
     */
    insert: function (key, ident) {
        const syntax = '[[' + key + '>' + ident + ']]';
        insertAtCarret(PluginDoi.edid, syntax);
    }
};
