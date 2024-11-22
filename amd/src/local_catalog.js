/**
 * Javascript containing function of the catalog space
 */

define([
    'jquery',
    'jqueryui',
    'format_edadmin/format_edadmin',
    'core/templates',
    'local_mentor_core/mentor',
    'local_mentor_core/cookie'
], function ($, ui, format_edadmin, templates, mentor, cookie) {

    var localCatalog = {
        /**
         * Init JS
         * @param {array} collections
         */
        init: function (collections) {

            // Set to true if a the scroll can load more results.
            $(window).data('ajaxready', true);

            // Session storage selected training name.
            this.sessionStorageSelectedTraining = 'mentor_local_catalog_selected_training';

            // Get list of available trainings.
            var listAvailableTrainingsData = JSON.parse($('#available-trainings').html());
            localCatalog.listAvailableTrainings = [];
            listAvailableTrainingsData.forEach(function (element) {
                localCatalog.listAvailableTrainings[element.id] = element;
            })

            // Store the page title by default.
            this.basePageTitle = document.title;

            // Init filters.
            this.selectedFilters = {
                entities: [],
                collections: []
            };

            // List of all the collections.
            this.collections = collections;

            // Nb trainings shown on screen.
            this.trainingsCount = 0;

            // Set nb results per scroll depending on the screen size.
            this.nbResultsPerScroll = this.initOffset = (window.innerWidth >= 1920) ? 20 : 10;

            var that = this;
            this.trainingsDictionnary = this.getDictionnary();

            // Restore filter cookies
            this.restoreCookieState();
            // Search with preset search input and filter with preset filters.
            this.searchWithFilters();

            // Search and filter on click.
            $('#search-button').on('click', function () {
                that.searchByText();
            });

            // Search and filter on enter keypress.
            $('#search').on('keypress', function () {
                var keycode = (event.keyCode ? event.keyCode : event.which);
                if (keycode === 13) {
                    that.searchByText();
                }
            });

            this.initInfiniteScroll();

            // Set the scroll at the top of the page to manage the previous button.
            window.addEventListener('unload', function (e) {
                //set scroll position to the top of the page.
                window.scrollTo(0, 0);
            });

            this.initExportTraining();

            // Reset filters & search
            $('#trainings-reset-filters_button').on('click', function() {
                that.resetAll();
            });
            
            // Call restore function when page loads
            $(document).ready(function() {
                that.scrollToTraining();
            });
        },

        /**
         * Get the dictionnary of trainings words
         * @returns {any}
         */
        getDictionnary: function() {
            return JSON.parse(mentor.sanitizeText($('#trainings-dictionnary').html()));
        },

        /**
         * Start a new search by text
         */
        searchByText: function () {
            this.initOffset = this.nbResultsPerScroll;

            // Filter the trainings.
            this.searchWithFilters();

            // Set the focus on the first tile.
            this.setFirstTileFocus();

            // Refresh the search cookie.
            cookie.create('catalogSearch', JSON.stringify($('#search').val()));
        },

        /**
         * Initialise infinite scroll
         */
        initInfiniteScroll: function () {

            var that = this;

            // Add loading image.
            $('#trainings-tile').append('<div id="loader"><img src="' + M.cfg.wwwroot + '/local/catalog/pix/loading.svg" alt="loader ajax"></div>');

            this.updateLoader();

            var deviceAgent = navigator.userAgent.toLowerCase();
            var agentID = deviceAgent.match(/(iphone|ipod|ipad)/);

            // Load more results on scroll.
            $(window).scroll(function () {

                that.updateLoader();

                if ($(window).data('ajaxready') == false) return;

                if (($(window).scrollTop() + $(window).height() + 400) > $(document).height()
                    || agentID && ($(window).scrollTop() + $(window).height()) + 150 > $(document).height()) {

                    $(window).data('ajaxready', false);

                    that.initOffset += that.nbResultsPerScroll;

                    // Load more results.
                    that.searchWithFilters();

                    $(window).data('ajaxready', true);
                }
            });
        },

        /**
         * Update the loader visibility
         * @return boolean true if all results have been loaded
         */
        updateLoader: function () {
            // Get all shown results before a new load.
            var nbResultsShown = $('.training-tile:not(.hidden)').length;

            // All results have been shown.
            if (nbResultsShown == this.trainingsCount) {
                // Hide the loader.
                $('#trainings-tile #loader').fadeOut(400);
                return true;
            }

            // Display the loader.
            $('#trainings-tile #loader').fadeIn(400);
            return false;
        },

        /**
         * Search and filter in trainings list
         */
        searchWithFilters: function () {
            var search = $('#search').val();

            if (search.length === 0) {
                // If the search text is empty, start an empty filter.
                this.filter();
                return;
            }

            // Clean the searched text.
            search = this.cleanupString(search);

            // Split the search text into words.
            var words = this.splitString(search);

            // Look for words into the dictionnary.
            var trainingsFound = this.searchTrainings(words, this.trainingsDictionnary);

            this.filter(trainingsFound);
        },

        /**
         * Update page title with filters and search text
         */
        updatePageTitle: function () {
            var search = $('#search').val();

            // Add the base page title.
            var pageTitle = this.basePageTitle;

            // Add search text to page title.
            if (search.length !== 0) {
                pageTitle += ' - ' + search;
            }

            // Add collections to page title.
            for (var i in this.selectedFilters.collections) {
                pageTitle += ' - ' + this.selectedFilters.collections[i];
            }

            // Add entities to page title.
            for (var j in this.selectedFilters.entities) {
                pageTitle += ' - ' + this.selectedFilters.entities[j];
            }

            document.title = pageTitle;
        },


        /**
         * Search words in dictionnary and returns a list of trainings ids
         * @param {array} words
         * @param {array} dictionnary
         * @return {array}
         */
        searchTrainings: function (words, dictionnary) {
            var trainingsFound = [];

            $.each(dictionnary, function (key, value) {
                trainingsFound[key] = key;
            });

            for (var index in dictionnary) {
                words.forEach(function (element) {
                    if (dictionnary[index].indexOf(element) === -1) {
                        delete trainingsFound[index];
                    }
                });
            }

            return trainingsFound;
        },

        /**
         * Filter trainings
         * @param {array} trainings
         */
        filter: function (trainings) {
            if (typeof trainings === 'undefined') {
                trainings = [];
            }

            var filters = this.getFiltersData();

            this.filterTrainings(
                filters.collections,
                filters.entities,
                trainings
            );
        },

        /**
         * Returns filters data (collections, entities)
         * @returns {array}
         */
        getFiltersData: function () {
            return this.selectedFilters;
        },

        /**
         * Filter available trainings by collection and entity
         * @param {array} collections
         * @param {array} entities
         * @param {array} selectedTrainings
         */
        filterTrainings: function (
            collections,
            entities,
            selectedTrainings
        ) {

            var that = this;

            if (typeof collections === 'undefined') {
                collections = [];
            }
            if (typeof entities === 'undefined') {
                entities = [];
            }
            if (typeof selectedTrainings === 'undefined') {
                selectedTrainings = [];
            }

            var trainings = localCatalog.listAvailableTrainings;
            var trainingsTileChildrens = $('#trainings-tile').children('.training-tile');
            that.trainingsCount = trainingsTileChildrens.length;

            var filteredTrainings = [];

            var countTrainingShown = 0;

            trainingsTileChildrens.each(function () {

                var currentTile = this;
                var currentTrainingId = $(this).attr('data-training-id');

                // Exclude non selected trainings, if given.
                if (selectedTrainings.length !== 0 && $.inArray(currentTrainingId, selectedTrainings) === -1) {
                    $(this).addClass('hidden').removeClass('odd').removeClass('even');
                    that.trainingsCount--;
                    return;
                }

                var currentTraining = trainings[currentTrainingId];
                var currentTrainingCollections = currentTraining.collectionstr.split(';');
                var currentTrainingEntity = currentTraining.entityid;

                // Filter from collections.
                var collectionsok = true;
                if (collections.length !== 0) {
                    $(collections).each(function (i, elem) {
                        if ($.inArray(elem, currentTrainingCollections) === -1) {
                            collectionsok = false;
                        }
                    });

                    if (!collectionsok) {
                        $(this).addClass('hidden').removeClass('odd').removeClass('even');
                        that.trainingsCount--;
                        return;
                    }
                }

                // Filter from entities.
                if (entities.length !== 0 && entities.find(element => element != currentTrainingEntity)) {
                    $(this).addClass('hidden').removeClass('odd').removeClass('even');
                    that.trainingsCount--;
                    return;
                }

                if (countTrainingShown >= that.initOffset) {
                    $(this).addClass('hidden').removeClass('odd').removeClass('even');
                } else {

                    // Add training image.
                    var thumbnailDiv = $(this).find('div.training-tile-thumbnail-resize');
                    var thumbnailUrl = thumbnailDiv.attr('data-thumbnail-url');

                    countTrainingShown++;
                    var classToAdd = countTrainingShown % 2 ? 'odd' : 'even';
                    var classToRemove = countTrainingShown % 2 ? 'even' : 'odd';
                    $(currentTile).removeClass(classToRemove).addClass(classToAdd).removeClass('hidden');

                    $('<img/>').attr('src', thumbnailUrl).on('load', function () {
                        $(this).remove();
                        thumbnailDiv.css('background-image', 'url(' + thumbnailUrl + ')');
                        $(currentTile).removeClass('hidden');
                    });

                }

                filteredTrainings.push(currentTraining);

            });

            // Update filters list.
            this.updateFilters(filteredTrainings);

            // Trainings count display.
            var trainingsCountText = '<span class="countnumber">' + that.trainingsCount + "</span> " + M.str.local_catalog.trainings_found;

            if (0 === that.trainingsCount) {
                trainingsCountText = M.str.local_catalog.no_training_found;
            } else if (1 === that.trainingsCount) {
                trainingsCountText = '<span class="countnumber">' + that.trainingsCount + "</span> " + M.str.local_catalog.training_found;
            }

            $('#trainings-count').html(trainingsCountText);

            // Update loader.
            that.updateLoader();
        },
        /**
         * Set the focus on the first visible tile.
         */
        setFirstTileFocus: function () {
            if ($('.training-tile:not(.hidden)').length > 0) {
                $('.training-tile:not(.hidden)')[0].focus();
            }
        },

        /**
         * Update collection and entity filters with trainings results
         *
         * @param {array} trainings
         */
        updateFilters: function (trainings) {
            var that = this;

            var collections = [];
            var entities = [];

            var currentFilters = this.getFiltersData();

            $(trainings).each(function () {

                // Set collections
                var trainingCollections = this.collection.split(',');

                $(trainingCollections).each(function () {

                    var col = collections.find(x => x.name === that.collections[this]);

                    if (typeof col != 'undefined') {
                        col.nbresults++;
                    } else {
                        collections.push({
                            'name': that.collections[this],
                            'identifier': this,
                            'nbresults': 1
                        });
                    }
                });

                // Set entities.
                var ent = entities.find(x => x.id === this.entityid);

                if (typeof ent != 'undefined') {
                    ent.nbresults++;
                } else {
                    entities.push({
                        'id': this.entityid,
                        'name': this.entityname,
                        'fullname': this.entityfullname,
                        'nbresults': 1
                    });
                }
            });

            collections.sort(
                (a, b) => {
                    if (a.name < b.name) {
                        return -1;
                    }
                    if (a.name > b.name) {
                        return 1;
                    }
                    return 0;
                }
            );

            entities.sort(
                (a, b) => {
                    if (a.name < b.name) {
                        return -1;
                    }
                    if (a.name > b.name) {
                        return 1;
                    }
                    return 0;
                }
            );

            // Empty the collections filter category.
            $('#collections').html('');

            var cross = '<div class="cross" title="Supprimer le filtre">x</div>';

            // Update collections buttons.
            $(collections).each(function () {
                var selected = currentFilters.collections.find(element => element == this.name) ? 'class="selected"' : '';
                $('#collections').append('<li><button data-type="collections" data-identifier="' + this.name + '" ' + selected + '>' + this.name + '<span' +
                    ' class="nbresults">(' + this.nbresults + ')</span>' + cross + '</button></li>');
            });

            // Empty the entities filter category.
            $('#entities').html('');

            // Update entities buttons.
            $(entities).each(function () {
                var selected = currentFilters.entities.find(element => element == this.id) ? 'class="selected"' : '';
                $('#entities').append('<li><button title="' + this.fullname + '" data-type="entities" data-identifier="' + this.id + '" ' + selected + '>' + this.name + '<span' +
                    ' class="nbresults">(' + this.nbresults + ')</span>' + cross + '</button></li>');
            });

            // Manage click on filters.
            $('#collections button, #entities button').on('click', function () {
                that.selectFilter(this);
            });

            // Update page title.
            this.updatePageTitle();
        },

        /**
         * Manage click on a filter
         *
         * @param {string} $element the jquery button element
         */
        selectFilter: function ($element) {
            var identifier = $($element).data('identifier');
            var type = $($element).data('type');

            if ($($element).hasClass('selected')) {
                this.removeFilter(type, identifier);
                $($element).removeClass('selected');
            } else {
                this.addFilter(type, identifier);
                $($element).addClass('selected');
            }

            this.initOffset = this.nbResultsPerScroll;

            // Launch a new search with filters.
            this.searchWithFilters();

            // Set the focus on the first visible tile.
            this.setFirstTileFocus();
        },

        /**
         * Add a filter
         * @param {string} type collections or entities
         * @param {string} identifier
         */
        addFilter: function (type, identifier) {
            var index = this.selectedFilters[type].findIndex(e => e === identifier);

            if (index === -1) {
                this.selectedFilters[type].push(identifier);
            }

            // Refresh filters cookie.
            this.updateFiltersCookie(JSON.stringify(this.selectedFilters));
        },

        /**
         * Remove a filter
         * @param {string} type collections or entities
         * @param {string} identifier
         */
        removeFilter: function (type, identifier) {
            var index = this.selectedFilters.collections.indexOf(identifier);
            this.selectedFilters[type].splice(index, 1);

            // Refresh filters cookie.
            this.updateFiltersCookie(JSON.stringify(this.selectedFilters));
        },

        /**
         * Refresh filters cookie.
         *
         * @param {string} values
         */
        updateFiltersCookie: function (values) {
            cookie.create('catalogFilters', values);
        },

        /**
         * Cleanup a string by removing special chars and accents
         * @param {string} str
         * @returns {string}
         */
        cleanupString: function (str) {
            str = str.toLowerCase();
            str = str.trim();
            var nonasciis = {'a': '[àáâãäå]', 'ae': 'æ', 'c': 'ç', 'e': '[èéêë]', 'i': '[ìíîï]', 'n': 'ñ', 'o': '[òóôõö]', 'oe': 'œ', 'u': '[ùúûűü]', 'y': '[ýÿ]'};
            for (var i in nonasciis) {
                str = str.replace(new RegExp(nonasciis[i], 'g'), i);
            }
            str = str.replace(new RegExp("[^a-zA-Z0-9\\s]", "g"), ' ');
            str = str.replace(/ +/g, ' ');
            return str;
        },
        /**
         * Split a string on spaces and remove words shorter than 3 caracters
         * @param {string} str
         * @returns {array}
         */
        splitString: function (str) {
            var split = str.split(' ');

            var words = [];

            for (var i in split) {
                if (words.indexOf(split[i]) == -1 && split[i].length > 2) {
                    words.push(split[i]);
                }
            }

            return words;
        },
        /**
         * Init all event to export trainings catalog select.
         */
        initExportTraining: function () {
            var exportButton = '#trainings-export';
            var exportNumber = '#trainings-export-number';

            $(exportButton).on('submit', function (eventForm) {
                eventForm.preventDefault();
                localCatalog.initModalExportTraining(eventForm);
            });

            localCatalog.initSelectedTraining();
            localCatalog.updateExportTrainingButton(exportButton, exportNumber);

            // Delineates a larger perimeter for the checkbox.
            $('.training-tile .training-tile-export-selection').click(function (e) {
                if ($(e.target).hasClass('training-tile-export-selection')) {
                    e.preventDefault();
                    $(e.target).children()[0].click();
                }
            });

            $('.training-tile .training-tile-export-selection input').click(function (e) {
                /**
                 * Do not use the base system.
                 * Firefox does not work correctly with checkboxes when they are in an anchor
                 */

                // Stop redirect event anchor.
                e.preventDefault();
                e.stopPropagation();

                // Add or remove new training to export PDF.
                localCatalog.addOrRemoveSelectedTrainingId(e.currentTarget);

                // Reset checkbox select.
                localCatalog.initSelectedTraining();

                // Check if user show export button.
                localCatalog.updateExportTrainingButton(exportButton, exportNumber);
            });
        },
        /**
         * Initialize selected training checkbox with data to session storage.
         */
        initSelectedTraining: function () {

            // Why setTimeout ? -> https://stackoverflow.com/a/67241292
            setTimeout(function () {
                $('.training-tile-export-selection')
                    .children()
                    .prop('checked', false)
                    .prop('title', M.util.get_string('addtoexport', 'local_catalog'))
                ;

                localCatalog.getSelectedTrainingId()
                    .forEach(function (element) {
                        $('#training-tile-export-selection-' + element)
                            .prop('checked', true)
                            .prop('title', M.util.get_string('removetoexport', 'local_catalog'))
                        ;
                    })
                ;
            }, 0);

        },
        /**
         * Get selected training id list to session storage.
         *
         * @return {string[]}
         */
        getSelectedTrainingId: function () {
            var item = sessionStorage
                .getItem(localCatalog.sessionStorageSelectedTraining);

            if (!item) {
                return [];
            }

            return item.split(',');
        },
        /**
         * Get selected training id list to session storage for URI.
         *
         * @return {string}
         */
        getExportPdfUri: function () {
            var listSelectedTrainingsId = localCatalog.getSelectedTrainingId();

            if (listSelectedTrainingsId.length === 0) {
                return '';
            }

            return M.cfg.wwwroot +
                '/local/catalog/pages/export-catalog.php?' +
                listSelectedTrainingsId.map(
                    function (e) {
                        return 'trainingsid[]=' + e;
                    }).join('&');
        },
        /**
         * Setting selected training id list to session storage.
         *
         * @param {string} checkedTraining
         */
        addOrRemoveSelectedTrainingId: function (checkedTraining) {
            var sessionStoragetraining = localCatalog.getSelectedTrainingId();
            var trainingId = $(checkedTraining).parent().parent().data('trainingId').toString();
            var positionElement = $.inArray(trainingId, sessionStoragetraining);

            if (positionElement === -1) {
                sessionStoragetraining.push(trainingId);
            } else {
                sessionStoragetraining.splice(positionElement, 1);
            }

            sessionStorage.setItem(localCatalog.sessionStorageSelectedTraining, sessionStoragetraining);
        },
        /**
         * Update export button with new data.
         *
         * @param {string} exportButton
         * @param {string} exportNumber
         */
        updateExportTrainingButton: function (exportButton, exportNumber) {
            var sessionStoragetraining = localCatalog.getSelectedTrainingId();
            $(exportNumber).html(sessionStoragetraining.length);
        },
        /**
         * Initialize modal export training catalog.
         *
         * @param {event} eventForm
         */
        initModalExportTraining: function (eventForm) {
            var sessionNameListOrderByName = localCatalog
                .getSelectedTrainingId()
                // Get session name.
                .map(function (element) {
                    return localCatalog.listAvailableTrainings[element].name;
                })
                // Order by name.
                .sort(Intl.Collator().compare);

            var selectedtraininglength = localCatalog.getSelectedTrainingId().length;

            templates.renderForPromise(
                'local_catalog/training-catalog-popin',
                {
                    selectedtraininglength: selectedtraininglength,
                    selectedtrainingname: sessionNameListOrderByName
                }
            ).then(function (_ref) {
                localCatalog.setModalExportTraining(_ref.html, eventForm, selectedtraininglength);
            });
        },
        /**
         * Setting modal export training catalog with body HTML.
         *
         * @param {string} html
         * @param {event} eventForm
         * @param {int} selectedtraininglength
         */
        setModalExportTraining: function (html, eventForm, selectedtraininglength) {

            var buttons = [];

            if (selectedtraininglength > 0) {
                buttons.push({
                    // Export.
                    text: M.util.get_string('toexport', 'local_catalog'),
                    class: "btn btn-primary",
                    click: function () {
                        $(eventForm.target).attr('action', localCatalog.getExportPdfUri());
                        eventForm.target.submit();
                        // Just close the modal.
                        $(this).dialog("destroy");
                    }
                });
            }

            buttons.push({
                // Cancel button.
                text: M.util.get_string('cancel', 'moodle'),
                class: "btn btn-secondary",
                click: function () {
                    // Just close the modal.
                    $(this).dialog("destroy");
                }
            });

            mentor.dialog(
                html,
                {
                    width: 700,
                    title: M.util.get_string('exportpdfformat', 'local_catalog'),
                    buttons: buttons
                });
        },
        /**
         * Restore search and filter state from cookies
        */
        restoreCookieState: function() {
            // Restore search text from cookies
            var savedSearch = cookie.read('catalogSearch');
            if (savedSearch) {
                try {
                    savedSearch = JSON.parse(savedSearch);
                    if (savedSearch) {
                        $('#search').val(savedSearch);
                    }
                } catch (e) {
                    console.error('Error parsing saved search', e);
                }
            }

            // Restore filters from cookies
            var savedFilters = cookie.read('catalogFilters');
            if (savedFilters) {
                try {
                    savedFilters = JSON.parse(savedFilters);
                    if (savedFilters && 
                        (savedFilters.collections.length > 0 || savedFilters.entities.length > 0)) {
                        
                        this.selectedFilters = savedFilters;
                        savedFilters.collections.forEach(function(collection) {
                            $(`#collections button[data-identifier="${collection}"]`).addClass('selected');
                        });

                        savedFilters.entities.forEach(function(entity) {
                            $(`#entities button[data-identifier="${entity}"]`).addClass('selected');
                        });

                        this.searchWithFilters();
                    }
                } catch (e) {
                    console.error('Error parsing saved filters', e);
                }
            }
        },
        
        /**
         * Reset all filters and search
         */
        resetAll: function() {
            $('#search').val('');

            this.selectedFilters = {
                entities: [],
                collections: []
            };

            this.initOffset = this.nbResultsPerScroll;

            $('#collections button, #entities button').removeClass('selected');

            cookie.create('catalogSearch', JSON.stringify(''));
            cookie.create('catalogFilters', JSON.stringify(this.selectedFilters));

            document.title = this.basePageTitle;

            this.searchWithFilters();

            this.setFirstTileFocus();

            this.updateLoader();
        },
        /**
         * Scroll to a training on the page, based on its id
         */
        scrollToTraining: function(trainingId = null) {
            if(!trainingId){
                trainingId = sessionStorage.getItem('selectedTrainingIdCatalog');
            }
            
            const trainingUrl = M.cfg.wwwroot + `/local/catalog/pages/training.php?trainingid=${trainingId}`;
            if(!trainingId || document.referrer !== trainingUrl){
                return;
            }

            
            let trainingLink = document.querySelector(`a[data-training-id="${trainingId}"]`);
            
            const originalAjaxReady = $(window).data('ajaxready');
            $(window).data('ajaxready', true);

            // Limit the loop in case of error
            for (let index = 0; trainingLink.classList.contains('hidden') && index < 1000; index++) {
                if($('.training-tile:not(.hidden)').length > 0){
                    localCatalog.initOffset += localCatalog.nbResultsPerScroll;
                    this.searchWithFilters();
                }
            }
            $(window).data('ajaxready', originalAjaxReady); 
            
            if (trainingLink && !trainingLink.classList.contains('hidden')) {
                trainingLink.scrollIntoView({ behavior: 'smooth', block: 'center'});
            }else{
                console.error(`TRAINING NOT FOUND, ID TRAINING ${trainingId}`)
            }
        }
    }

    // Add object to window to be called outside require.
    window.localCatalog = localCatalog;
    return localCatalog;
});
