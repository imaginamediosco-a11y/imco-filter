// JS del frontend para IMCO Filter.
// - Auto-filtrado AJAX al cambiar filtros.
// - Paginación (páginas numeradas / Cargar más).
// - Chips que se pueden cerrar.
// - Panel móvil (botón FILTRAR) + acordeón de categorías.
// - Toggle de filtro en ESCRITORIO con el mismo botón.
// - Restauración de estado (Botón Atrás) y URL dinámica.

(function ($) {
    'use strict';

    /**
     * Inicializa toda la lógica del filtro AJAX.
     */
    function imcoInitAjaxFilters() {
        var $form = $('#imco-filter-form');

        if (!$form.length) {
            return;
        }

        var paginationMode = (typeof imcoAjax !== 'undefined' && imcoAjax.pagination_mode)
            ? imcoAjax.pagination_mode
            : 'pages';

        var $pageInput = $('#imco-page-input');
        if (!$pageInput.length) {
            $pageInput = $('<input>', {
                type: 'hidden',
                id: 'imco-page-input',
                name: 'imco_page',
                value: '1'
            });
            $form.append($pageInput);
        }

        var submitTimeout = null;

        /**
         * Devuelve el contenedor de productos (soporta dos posibles IDs).
         */
        function getProductsContainer() {
            var $products = $('#imco-products-container');
            if (!$products.length) {
                $products = $('#imco-products-grid');
            }
            return $products;
        }

        /**
         * Devuelve el contenedor de filtros activos (si existe).
         */
        function getFiltersWrapper() {
            var $wrapper = $('#imco-active-filters-wrapper');
            if ($wrapper.length) {
                return $wrapper;
            }
            return $('.imco-active-filters').parent();
        }

        /**
         * Actualiza la URL del navegador sin recargar la página.
         */
        function updateBrowserUrl() {
            var urlData = $form.serializeArray().filter(function (item) {
                // Ignorar campos internos o vacíos
                return item.name !== 'action' && item.name !== 'imco_append' && item.value !== '';
            });

            // Si la página es 1, no hace falta mostrarla en la URL
            urlData = urlData.filter(function (item) {
                return !(item.name === 'imco_page' && item.value === '1');
            });

            var queryString = $.param(urlData);
            var newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + (queryString ? '?' + queryString : '');

            // Usamos replaceState para no llenar el historial del botón "Atrás" con cada clic de filtro
            window.history.replaceState(null, '', newUrl);
        }

        /**
         * Envía los filtros por AJAX.
         * @param {boolean} append - Si true, añade productos (modo "Cargar más").
         */
        function submitFiltersAjax(append, isPagination) {

            if (typeof imcoAjax === 'undefined' || !imcoAjax.ajax_url) {
                $form[0].submit();
                return;
            }

            if (!append && !isPagination) {
                $pageInput.val('1');
            }

            if (submitTimeout) {
                clearTimeout(submitTimeout);
            }

            submitTimeout = setTimeout(function () {
                var data = $form.serializeArray();
                data.push({ name: 'action', value: 'imco_filter_products' });

                if (append) {
                    data.push({ name: 'imco_append', value: '1' });
                }

                var $products = getProductsContainer();
                var $filtersWrapper = getFiltersWrapper();

                if ($products.length) {
                    $products.addClass('imco-loading');
                }

                // Actualizar la URL visualmente
                updateBrowserUrl();

                $.ajax({
                    url: imcoAjax.ajax_url,
                    type: 'GET',
                    data: $.param(data),
                    dataType: 'json',
                    success: function (response) {
                        if (!response || !response.success || !response.data) {
                            return;
                        }

                        if ($products.length && typeof response.data.products_html !== 'undefined') {

                            if (paginationMode === 'load_more' && append) {
                                var temp = $('<div>').html(response.data.products_html);

                                var $newItemsContainer = temp.find('.imco-products-items, ul.products').first();
                                var $newItems = $newItemsContainer.length
                                    ? $newItemsContainer.children()
                                    : temp.children().not('.imco-pagination');

                                var $newPagination = temp.find('.imco-pagination');

                                var $existingItemsContainer = $products.find('.imco-products-items, ul.products').first();
                                if ($existingItemsContainer.length && $newItems.length) {
                                    $existingItemsContainer.append($newItems);
                                } else {
                                    $products.html(response.data.products_html);
                                }

                                if ($newPagination.length) {
                                    var $existingPagination = $products.find('.imco-pagination');
                                    if ($existingPagination.length) {
                                        $existingPagination.replaceWith($newPagination);
                                    } else {
                                        $products.append($newPagination);
                                    }
                                } else {
                                    $products.find('.imco-pagination').remove();
                                }
                            } else {
                                $products.html(response.data.products_html);
                            }
                        }

                        if ($filtersWrapper.length && typeof response.data.filters_html !== 'undefined') {
                            $filtersWrapper.html(response.data.filters_html);
                        }
                    },
                    complete: function () {
                        if ($products.length) {
                            $products.removeClass('imco-loading');
                        }
                    }
                });
            }, 250);
        }

        $form.on('change', 'input, select', function () {
            submitFiltersAjax(false);
        });

        $form.on('submit', function (e) {
            e.preventDefault();
            submitFiltersAjax(false);
        });

        $(document).on('click', '.imco-active-filter-chip-close', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $chip = $(this).closest('.imco-active-filter-chip');
            var cat = $chip.data('imco-cat');
            var value = $chip.data('imco-value');

            if (!cat || typeof value === 'undefined') {
                return;
            }

            var nameArray = 'imco_filter[' + cat + '][]';
            var nameSingle = 'imco_filter[' + cat + ']';

            var $checkbox = $form.find('input[type="checkbox"][name="' + nameArray + '"][value="' + value + '"]');
            if ($checkbox.length) {
                $checkbox.prop('checked', false);
                submitFiltersAjax(false);
                return;
            }

            var $radio = $form.find('input[type="radio"][name="' + nameSingle + '"][value="' + value + '"]');
            if ($radio.length) {
                $radio.prop('checked', false);
                submitFiltersAjax(false);
                return;
            }

            var $select = $form.find('select[name="' + nameSingle + '"]');
            if ($select.length && String($select.val()) === String(value)) {
                $select.val('');
                submitFiltersAjax(false);
                return;
            }
        });

        $(document).on('click', '.imco-filter-reset, .imco-active-filters-clear', function (e) {
            e.preventDefault();

            $form.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
            $form.find('select').val('');
            $pageInput.val('1');

            submitFiltersAjax(false);
        });

        // ==========================================
        // FIX 1: Paginación por páginas (Soporta estructura IMCO y WooCommerce nativo)
        // ==========================================
        $(document).on('click', '.imco-pagination-pages .imco-page-link, .woocommerce-pagination a.page-numbers', function (e) {
            e.preventDefault();

            var page = $(this).data('imco-page');

            // Si no tiene el data-attribute, intentamos extraerlo del texto o href (WooCommerce nativo)
            if (!page) {
                if ($(this).hasClass('next')) {
                    page = parseInt($pageInput.val() || 1) + 1;
                } else if ($(this).hasClass('prev')) {
                    page = parseInt($pageInput.val() || 1) - 1;
                } else {
                    var textPage = parseInt($(this).text());
                    if (!isNaN(textPage)) {
                        page = textPage;
                    } else {
                        // Extraer de la URL
                        var href = $(this).attr('href');
                        var match = href ? (href.match(/paged?=([0-9]+)/) || href.match(/\/page\/([0-9]+)/)) : null;
                        if (match) {
                            page = parseInt(match[1]);
                        }
                    }
                }
            }

            if (!page) {
                return;
            }

            // Actualizar el input oculto
            $pageInput.val(page);

            // Forzar la actualización del parámetro imco_page en el formulario antes de enviar
            if ($form.find('input[name="imco_page"]').length === 0) {
                $form.append('<input type="hidden" name="imco_page" value="' + page + '">');
            } else {
                $form.find('input[name="imco_page"]').val(page);
            }

            submitFiltersAjax(false, true);

            // Scroll suave hacia arriba al cambiar de página
            var $products = getProductsContainer();
            if ($products.length) {
                $('html, body').animate({
                    scrollTop: $products.offset().top - 100
                }, 500);
            }
        });

        // Botón "Cargar más"
        $(document).on('click', '.imco-load-more-button', function (e) {
            e.preventDefault();
            var nextPage = $(this).data('imco-next-page');
            if (!nextPage) {
                return;
            }
            $pageInput.val(nextPage);
            submitFiltersAjax(true, true);
        });

        // ==========================================
        // FIX 2: Restaurar estado al usar botón Atrás
        // ==========================================

        // Guardar el estado antes de salir de la página
        $(window).on('beforeunload', function () {
            var $products = getProductsContainer();
            if ($products.length) {
                var state = {
                    html: $products.html(),
                    page: $pageInput.val(),
                    scroll: $(window).scrollTop(),
                    url: window.location.href
                };
                sessionStorage.setItem('imco_saved_state', JSON.stringify(state));
            }
        });

        // Restaurar el estado al cargar la página
        var savedStateStr = sessionStorage.getItem('imco_saved_state');
        if (savedStateStr) {
            try {
                var state = JSON.parse(savedStateStr);
                // Solo restaurar si estamos exactamente en la misma URL donde guardamos
                if (state.url === window.location.href) {
                    var $products = getProductsContainer();
                    if ($products.length && state.html) {
                        $products.html(state.html);
                        $pageInput.val(state.page);

                        // Restaurar el scroll después de un pequeño delay para asegurar que el DOM pintó
                        setTimeout(function () {
                            $(window).scrollTop(state.scroll);
                        }, 50);
                    }
                }
            } catch (e) {
                console.error("Error restaurando estado IMCO:", e);
            }
            // Limpiar la memoria para que no interfiera en futuras visitas limpias
            sessionStorage.removeItem('imco_saved_state');
        }
    }

    /**
     * Panel móvil: botón "Filtrar", overlay, botón de cierre.
     */
    function imcoInitMobilePanel() {
        var $archives = $('.imco-filter-archive');
        if (!$archives.length) {
            return;
        }

        $archives.each(function () {
            var $container = $(this);
            var $button = $container.find('.imco-mobile-filter-button');
            var $overlay = $container.find('.imco-mobile-filter-overlay');
            var $close = $container.find('.imco-mobile-filter-close');

            if (!$button.length || !$overlay.length || !$close.length) {
                return;
            }

            function openPanel(e) {
                if (e) e.preventDefault();
                $container.addClass('imco-mobile-filter-open');
                $('body').addClass('imco-no-scroll');
            }

            function closePanel(e) {
                if (e) e.preventDefault();
                $container.removeClass('imco-mobile-filter-open');
                $('body').removeClass('imco-no-scroll');
            }

            $button.on('click', function (e) {
                if (window.matchMedia('(min-width: 769px)').matches) {
                    e.preventDefault();
                    $container.toggleClass('imco-desktop-open');
                    $container.removeClass('imco-mobile-filter-open');
                    $('body').removeClass('imco-no-scroll');
                } else {
                    openPanel(e);
                }
            });

            $close.on('click', closePanel);
            $overlay.on('click', closePanel);
        });
    }

    /**
     * Acordeón de categorías en móvil
     */
    function imcoInitMobileAccordion() {
        var $groups = $('.imco-filter-archive .imco-filter-group');
        if (!$groups.length) {
            return;
        }

        $groups.each(function () {
            var $group = $(this);
            var $title = $group.find('.imco-filter-title').first();

            if (!$title.length) {
                return;
            }

            $title.css('cursor', 'pointer');

            $title.on('click', function (e) {
                e.preventDefault();
                $group.toggleClass('is-open');
            });
        });
    }

    // ====================================
    // Inicialización al cargar la página
    // ====================================
    $(function () {
        imcoInitAjaxFilters();
        imcoInitMobilePanel();
        imcoInitMobileAccordion();
    });

})(jQuery);
