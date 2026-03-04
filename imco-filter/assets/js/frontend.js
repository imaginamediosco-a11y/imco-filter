// JS del frontend para IMCO Filter.
// - Auto-filtrado AJAX al cambiar filtros.
// - Paginación (páginas numeradas / Cargar más).
// - Chips que se pueden cerrar.
// - Panel móvil (botón FILTRAR) + acordeón de categorías.
// - Toggle de filtro en ESCRITORIO con el mismo botón.

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
            // Fallback: si no hay wrapper, usamos el bloque .imco-active-filters tal cual.
            return $('.imco-active-filters').parent();
        }

        /**
         * Envía los filtros por AJAX.
         * @param {boolean} append - Si true, añade productos (modo "Cargar más").
         */
        function submitFiltersAjax(append) {

            if (typeof imcoAjax === 'undefined' || !imcoAjax.ajax_url) {
                // Fallback: envío normal del formulario.
                $form[0].submit();
                return;
            }

            // Si no estamos en modo "append", volvemos a página 1.
            if (!append) {
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

                $.ajax({
                    url: imcoAjax.ajax_url,
                    type: 'GET',
                    data: $.param(data),
                    dataType: 'json',
                    success: function (response) {
                        if (!response || !response.success || !response.data) {
                            return;
                        }

                        // Actualizar productos
                        if ($products.length && typeof response.data.products_html !== 'undefined') {

                            if (paginationMode === 'load_more' && append) {
                                // APPEND: añadir productos nuevos sin borrar los anteriores.
                                var temp = $('<div>').html(response.data.products_html);

                                var $newItemsContainer = temp.find('.imco-products-items');
                                var $newItems = $newItemsContainer.length
                                    ? $newItemsContainer.children()
                                    : temp.children();

                                var $newPagination = temp.find('.imco-pagination');

                                var $existingItemsContainer = $products.find('.imco-products-items');
                                if ($existingItemsContainer.length && $newItems.length) {
                                    $existingItemsContainer.append($newItems);
                                } else {
                                    // Fallback: si algo falla, reemplazar todo.
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
                                // REPLACE normal (paginación por páginas o cambio de filtros).
                                $products.html(response.data.products_html);
                            }
                        }

                        // Actualizar bloque de filtros activos (chips)
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

        // Auto-filtrado al cambiar cualquier input / select
        $form.on('change', 'input, select', function () {
            // IMPORTANTE: aquí NO cerramos el panel móvil.
            submitFiltersAjax(false);
        });

        // Interceptar submit del formulario (fallback)
        $form.on('submit', function (e) {
            e.preventDefault();
            submitFiltersAjax(false);
        });

        // Quitar filtro individual al hacer clic en la "X" del chip
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

            // 1) Checkboxes (selección múltiple)
            var $checkbox = $form.find('input[type="checkbox"][name="' + nameArray + '"][value="' + value + '"]');
            if ($checkbox.length) {
                $checkbox.prop('checked', false);
                submitFiltersAjax(false);
                return;
            }

            // 2) Radios (botones)
            var $radio = $form.find('input[type="radio"][name="' + nameSingle + '"][value="' + value + '"]');
            if ($radio.length) {
                $radio.prop('checked', false);
                submitFiltersAjax(false);
                return;
            }

            // 3) Select (lista)
            var $select = $form.find('select[name="' + nameSingle + '"]');
            if ($select.length && String($select.val()) === String(value)) {
                $select.val('');
                submitFiltersAjax(false);
                return;
            }
        });

        // Limpiar todos los filtros desde el link del formulario o el de chips
        $(document).on('click', '.imco-filter-reset, .imco-active-filters-clear', function (e) {
            e.preventDefault();

            $form.find('input[type="checkbox"], input[type="radio"]').prop('checked', false);
            $form.find('select').val('');
            $pageInput.val('1');

            submitFiltersAjax(false);
        });

        // Paginación por páginas (1, 2, 3...)
        $(document).on('click', '.imco-pagination-pages .imco-page-link', function (e) {
            e.preventDefault();
            var page = $(this).data('imco-page');
            if (!page) {
                return;
            }
            $pageInput.val(page);
            submitFiltersAjax(false);
        });

        // Botón "Cargar más"
        $(document).on('click', '.imco-load-more-button', function (e) {
            e.preventDefault();
            var nextPage = $(this).data('imco-next-page');
            if (!nextPage) {
                return;
            }
            $pageInput.val(nextPage);
            submitFiltersAjax(true);
        });
    }

    /**
     * Panel móvil: botón "Filtrar", overlay, botón de cierre.
     * Y en ESCRITORIO el mismo botón abre/cierra el filtro arriba.
     */
    function imcoInitMobilePanel() {
        var $archives = $('.imco-filter-archive');
        if (!$archives.length) {
            return;
        }

        $archives.each(function () {
            var $container = $(this);
            var $button    = $container.find('.imco-mobile-filter-button');
            var $overlay   = $container.find('.imco-mobile-filter-overlay');
            var $close     = $container.find('.imco-mobile-filter-close');

            if (!$button.length || !$overlay.length || !$close.length) {
                return;
            }

            // Comportamiento original móvil
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

            // Click del botón FILTRAR
            $button.on('click', function (e) {
                // ESCRITORIO (PC): usamos el botón como toggle
                if (window.matchMedia('(min-width: 769px)').matches) {
                    e.preventDefault();

                    // Alternar la clase que el CSS usa para mostrar/ocultar el panel
                    $container.toggleClass('imco-desktop-open');

                    // Aseguramos que no quede en modo móvil
                    $container.removeClass('imco-mobile-filter-open');
                    $('body').removeClass('imco-no-scroll');
                } else {
                    // MÓVIL: comportamiento original
                    openPanel(e);
                }
            });

            // Estos solo se usan en móvil
            $close.on('click', closePanel);
            $overlay.on('click', closePanel);
        });
    }

    /**
     * Acordeón de categorías en móvil:
     * - Usa la clase .is-open en .imco-filter-group
     * - Título clicable: .imco-filter-title
     */
    function imcoInitMobileAccordion() {
        var $groups = $('.imco-filter-archive .imco-filter-group');
        if (!$groups.length) {
            return;
        }

        // Ya no abrimos ningún grupo por defecto en móvil.

        $groups.each(function () {
            var $group = $(this);
            var $title = $group.find('.imco-filter-title').first();

            if (!$title.length) {
                return;
            }

            $title.css('cursor', 'pointer');

            $title.on('click', function (e) {
                e.preventDefault();
                // Solo abrimos/cerramos este grupo, sin cerrar el panel.
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
