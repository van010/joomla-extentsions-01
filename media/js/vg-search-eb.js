/**
 * VgSearchEb Module - Joomla Event Search
 * Encapsulated, modern, and optimized for Joomla 4/5/6
 */
(function($) {
    'use strict';

    class VgSearchEb {
        constructor(moduleId) {
            this.moduleId = moduleId;
            this.form = $(`.mod-vg-search-eb-${moduleId}`);
            this.resultSection = $(`.vg-search-eb-result-${moduleId}`);
            this.baseUrl = Joomla.getOptions('system.paths')?.root || '';
            this.isLoading = false;

            this._bindEvents();
            this._loadOnPageReady();
        }

        /**
         * Bind form events instead of inline onclick
         */
        _bindEvents() {
            // Search button
            this.form.on('click', '[data-action="search"]', (e) => {
                e.preventDefault();
                this.fetchEvents();
            });

            // Reset button
            this.form.on('click', '[data-action="reset"]', (e) => {
                e.preventDefault();
                this.resetSearch();
            });

            // Optional: Auto-search on change (uncomment if needed)
            // this.form.on('change', 'select, input', () => this.fetchEvents());
        }

        /**
         * Automatically load events when the page is ready
         */
        _loadOnPageReady() {
            // Only load if DOM is ready & we haven't loaded yet
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this._triggerInitialLoad());
            } else {
                this._triggerInitialLoad();
            }
        }

        /**
         * Internal: Trigger initial load safely
         */
        async _triggerInitialLoad() {
            if (this.initialLoadDone || this.isLoading) return;
            this.initialLoadDone = true;
            /*const moduleConfig = JSON.parse(vgSearchEbData.params);
            const resultLayout = moduleConfig.search_result_layout;*/
            $('#eb-upcoming-events-page-timeline, #eb-upcoming-events-page-default, #eb-upcoming-events-page-columns').remove();

            await this.fetchEvents();
        }

        /**
         * Fetch events via AJAX
         */
        async fetchEvents() {
            if (this.isLoading) return;
            this._setLoading(true);

            try {
                const ajaxUrl = this._parseAjaxUrl();
                const formData = this._getFormData();

                const response = await $.ajax({
                    type: 'POST',
                    url: ajaxUrl,
                    data: formData,
                    dataType: 'json',
                    timeout: 15000         // Prevent hanging requests
                });

                if (response.success !== false) {
                    this.resultSection.html(response.html || '');
                } else {
                    this._showError(response.message || 'Unable to load events.');
                }
            } catch (error) {
                console.error('[VgSearchEb] AJAX Error:', error);
                this._showError('Connection failed. Please try again.');
            } finally {
                this._setLoading(false);
            }
        }

        /**
         * Serialize form data, handling multi-select arrays properly
         */
        _getFormData() {
            const dataArray = this.form.serializeArray();
            const dataObj = {};

            $.each(dataArray, function() {
                if (dataObj[this.name]) {
                    if (!Array.isArray(dataObj[this.name])) {
                        dataObj[this.name] = [dataObj[this.name]];
                    }
                    dataObj[this.name].push(this.value);
                } else {
                    dataObj[this.name] = this.value;
                }
            });

            return dataObj;
        }

        /**
         * Build com_ajax URL
         */
        _parseAjaxUrl() {
            const params = {
                option: 'com_ajax',
                module: 'vg_search_eb',
                method: 'loadEvents',
                format: 'json',
                tmpl: 'component' // Prevents Joomla template wrapper in AJAX response
            };

            const queryString = new URLSearchParams(params).toString();
            return `${this.baseUrl}/index.php?${queryString}`;
        }

        /**
         * Reset form and clear results
         */
        resetSearch() {
            this.form[0].reset();
            this.resultSection.empty();

            // Optional: Trigger initial state fetch
            this.fetchEvents();
        }

        /**
         * Toggle loading state on search button
         */
        _setLoading(isLoading) {
            this.isLoading = isLoading;
            const btn = this.form.find('[data-action="search"]');

            if (isLoading) {
                btn.prop('disabled', true)
                   .data('original-text', btn.text())
                   .html('<span class="spinner-border spinner-border-sm me-1"></span> Loading...');
            } else {
                btn.prop('disabled', false)
                   .text(btn.data('original-text') || 'Search');
            }
        }

        /**
         * Show error message in result area
         */
        _showError(message) {
            this.resultSection.html(`
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);
        }
    }

    // Initialize on DOM ready
    $(document).ready(function() {
        if (typeof modVgSearchEbId !== 'undefined') {
            // Store instance globally for backward compatibility
            window.vgSearchEbInstance = new VgSearchEb(modVgSearchEbId);
        }
    });

    // Keep global functions for existing inline onclick handlers
    window.searchEvents = function() {
        window.vgSearchEbInstance?.fetchEvents();
    };

    window.resetSearch = function() {
        window.vgSearchEbInstance?.resetSearch();
    };

})(jQuery);