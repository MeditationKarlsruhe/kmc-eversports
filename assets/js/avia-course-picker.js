(function ($) {
    'use strict';

    var COURSE_SELECT = 'select[name="aviaTBcourse_groups"]';
    var COURSE_NAMES = 'input[name="aviaTBcourse_names"]';
    var FILTER_CLASS = 'kmc-eversports-course-filter';
    var CANVAS_NAMES = '.kmc-eversports-course-names';
    var SEPARATOR = '|';

    function courseName(option) {
        return $.trim($(option).text()).replace(/[\[\]"<>|]/g, '');
    }

    function storeCourseNames(select) {
        var names = $.map(select.find('option:selected'), courseName);
        var modal = select.closest('.avia-modal');

        modal.find(COURSE_NAMES).val(names.join(' ' + SEPARATOR + ' '));
    }

    function splitCanvasNames(container) {
        var names = container.text().split(SEPARATOR);

        if (container.children('.avia-element-item').length) {
            return;
        }

        container.empty();
        $.each(names, function (index, name) {
            var trimmed = $.trim(name);

            if (trimmed !== '') {
                container.append($('<span class="avia-element-item"></span>').text(trimmed));
            }
        });
    }

    function filterOptions(select, term) {
        var needle = term.toLowerCase();

        select.find('option').each(function () {
            var label = $(this).text().toLowerCase();

            this.hidden = needle !== '' && label.indexOf(needle) === -1;
        });
    }

    function createFilter(select) {
        var filter = $('<input type="search" placeholder="Kurse filtern …">');

        filter.addClass(FILTER_CLASS);
        filter.css({ display: 'block', width: '100%', marginBottom: '6px' });
        filter.on('input', function () {
            filterOptions(select, filter.val());
        });
        filter.on('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
            }
        });

        return filter;
    }

    function enhance(select) {
        if (select.prev('.' + FILTER_CLASS).length) {
            return;
        }

        select.before(createFilter(select));
        storeCourseNames(select);
    }

    function keepScrollPosition(select, scrollTop) {
        var restore = function () {
            select.scrollTop(scrollTop);
        };

        select.on('scroll', restore);
        window.setTimeout(function () {
            select.off('scroll', restore);
        }, 300);
    }

    function toggleOption(event) {
        var select = $(this).closest('select');
        var scrollTop = select.scrollTop();

        event.preventDefault();
        this.selected = !this.selected;
        select.trigger('change');
        keepScrollPosition(select, scrollTop);
    }

    $(document).on('avia_modal_finished', function () {
        $('.avia-modal').find(COURSE_SELECT).each(function () {
            enhance($(this));
        });
    });

    $(document).on('av-alb-canvas-updated', function (event) {
        $(event.target).find(CANVAS_NAMES).each(function () {
            splitCanvasNames($(this));
        });
    });

    $(document).on('mousedown', COURSE_SELECT + ' option', toggleOption);

    $(document).on('change', COURSE_SELECT, function () {
        storeCourseNames($(this));
    });
}(jQuery));
