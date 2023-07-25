/**
 * Editor button
 */
if (typeof window.toolbar !== 'undefined') {
    window.toolbar[window.toolbar.length] = {
        type: "picker",
        title: LANG.plugins.doi.toolbarButton,
        icon: "../../plugins/doi/img/library.png",
        class: "plugin_doi_picker_narrow",
        list: [
            {
                title: "DOI",
                type: "format",
                icon: "../../plugins/doi/img/doi.png",
                open: "[[doi>",
                close: "]]",
            },
            {
                title: "ISBN",
                type: "format",
                icon: "../../plugins/doi/img/ISBN.png",
                open: "[[isbn>",
                close: "]]",
            }
        ]
    };
}
