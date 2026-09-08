/**
 * Karnataka Trekkers - Admin Interface JS
 * Enhanced with responsive sidebar, swipe gestures, and mobile optimizations
 */

$(document).ready(function() {
    // =========================================================================
    // 1. Admin Sidebar Toggle
    // =========================================================================
    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        $("#wrapper").toggleClass("toggled");
    });

    // =========================================================================
    // 2. Close sidebar on backdrop click (mobile/tablet)
    // =========================================================================
    $(document).on('click', function(e) {
        if ($(window).width() < 1025) {
            var sidebar = $("#sidebar-wrapper");
            var toggleBtn = $("#menu-toggle");
            if (!sidebar.is(e.target) && sidebar.has(e.target).length === 0 && 
                !toggleBtn.is(e.target) && toggleBtn.has(e.target).length === 0 && 
                $("#wrapper").hasClass("toggled")) {
                $("#wrapper").removeClass("toggled");
            }
        }
    });

    // =========================================================================
    // 3. Auto-close sidebar on link click (mobile view)
    // =========================================================================
    if ($(window).width() < 1025) {
        $('#sidebar-wrapper .list-group-item').on('click', function() {
            // Allow navigation, then close sidebar
            setTimeout(function() {
                if ($("#wrapper").hasClass("toggled")) {
                    $("#wrapper").removeClass("toggled");
                }
            }, 150);
        });
    }

    // =========================================================================
    // 4. Swipe to close sidebar on touch devices
    // =========================================================================
    (function initSidebarSwipe() {
        var sidebarEl = document.getElementById('sidebar-wrapper');
        if (!sidebarEl) return;

        var touchStartX = 0;
        var touchEndX = 0;

        sidebarEl.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        sidebarEl.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            var diff = touchStartX - touchEndX;
            // Swipe left to close sidebar
            if (diff > 60 && $("#wrapper").hasClass("toggled")) {
                $("#wrapper").removeClass("toggled");
            }
        }, { passive: true });
    })();

    // =========================================================================
    // 5. Image Upload Live Preview
    // =========================================================================
    $(".image-preview-input").change(function() {
        var input = this;
        var previewContainer = $(input).data('preview');
        
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $(previewContainer).attr('src', e.target.result).removeClass('d-none');
            };
            reader.readAsDataURL(input.files[0]);
        }
    });

    // =========================================================================
    // 6. Dynamic Itinerary Builder
    // =========================================================================
    $("#btn_add_itinerary_day").click(function(e) {
        e.preventDefault();
        var container = $("#itinerary_days_container");
        var dayNum = container.children(".itinerary-row").length + 1;

        var rowHtml = `
            <div class="itinerary-row mb-3 p-3 border rounded bg-light" id="itinerary_day_${dayNum}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-primary mb-0">Itinerary Item</h6>
                    <button type="button" class="btn btn-sm btn-danger btn-remove-day" data-day="${dayNum}">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Day</label>
                        <input type="text" name="itinerary_day[]" class="form-control form-control-sm" required value="Day ${dayNum}" placeholder="e.g. Day 0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Title / Heading</label>
                        <input type="text" name="itinerary_title[]" class="form-control form-control-sm" required placeholder="e.g. Arrival & Trek Briefing">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Detailed Description</label>
                        <textarea name="itinerary_desc[]" class="form-control form-control-sm" rows="2" required placeholder="Describe activities, meals, and highlights of the day"></textarea>
                    </div>
                </div>
            </div>
        `;
        container.append(rowHtml);
    });

    // =========================================================================
    // 7. Remove Itinerary Day Row
    // =========================================================================
    $(document).on('click', '.btn-remove-day', function() {
        var row = $(this).closest('.itinerary-row');
        row.slideUp(300, function() {
            $(this).remove();
            // Renumber remaining structural day elements but do not overwrite manual inputs
            $("#itinerary_days_container").children(".itinerary-row").each(function(index) {
                var dayNum = index + 1;
                $(this).attr('id', 'itinerary_day_' + dayNum);
                $(this).find('.btn-remove-day').attr('data-day', dayNum);
            });
        });
    });

    // =========================================================================
    // 8. Auto-populate data-label for mobile card rendering in tables
    // =========================================================================
    $('table.table').each(function() {
        var $table = $(this);
        var headers = [];
        $table.find('thead th').each(function() {
            headers.push($(this).text().trim());
        });
        $table.find('tbody tr').each(function() {
            $(this).find('td').each(function(index) {
                if (headers[index]) {
                    $(this).attr('data-label', headers[index]);
                }
            });
        });
    });

    // =========================================================================
    // 9. Touch-friendly confirmation dialogs
    // =========================================================================
    $(document).on('click', '[data-confirm]', function(e) {
        var message = $(this).data('confirm') || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });

    // =========================================================================
    // 10. Responsive sidebar state on resize
    // =========================================================================
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if ($(window).width() >= 1025) {
                // Desktop: ensure sidebar is visible
                if (!$('#wrapper').hasClass('toggled')) {
                    $('#wrapper').addClass('toggled');
                }
            }
        }, 250);
    });

    // Set initial sidebar state
    if ($(window).width() >= 1025) {
        $('#wrapper').addClass('toggled');
    }
    // =========================================================================
    // 11. Highlights, Notes, and FAQs Repeaters
    // =========================================================================
    if ($('#highlights_container').length) {
        // SortableJS initialization
        new Sortable(document.getElementById('highlights_container'), {
            animation: 150,
            handle: '.drag-handle'
        });
        new Sortable(document.getElementById('notes_container'), {
            animation: 150,
            handle: '.drag-handle'
        });
        if (document.getElementById('additional_notes_container')) {
            new Sortable(document.getElementById('additional_notes_container'), {
                animation: 150,
                handle: '.drag-handle'
            });
        }
        new Sortable(document.getElementById('faqs_container'), {
            animation: 150,
            handle: '.drag-handle'
        });

        // Add Highlight row
        $('#btn_add_highlight').click(function(e) {
            e.preventDefault();
            const container = $('#highlights_container');
            container.append(`
                <div class="highlight-row mb-2 p-2 border rounded bg-light d-flex align-items-center gap-2">
                    <span class="drag-handle cursor-grab px-2 text-muted"><i class="fas fa-grip-vertical"></i></span>
                    <select name="highlight_icons[]" class="form-select form-select-sm" style="width: auto;">
                        <option value="fas fa-hiking">🚶 Guide</option>
                        <option value="fas fa-fire">🔥 Campfire</option>
                        <option value="fas fa-file-contract">📄 Permit</option>
                        <option value="fas fa-water">🌊 Waterfall</option>
                        <option value="fas fa-bus">🚌 Transport</option>
                        <option value="fas fa-utensils">🍽️ Meals</option>
                        <option value="fas fa-campground">⛺ Camping</option>
                        <option value="fas fa-mountain">⛰️ Summit</option>
                        <option value="fas fa-sun">☀️ Sunrise</option>
                        <option value="fas fa-camera">📷 Photo</option>
                        <option value="fas fa-check-circle" selected>✔️ Default</option>
                    </select>
                    <input type="text" name="highlights[]" class="form-control form-control-sm" required placeholder="e.g. Sunrise Trek Experience">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-highlight"><i class="fas fa-trash"></i></button>
                </div>
            `);
        });

        // Remove Highlight row
        $(document).on('click', '.btn-remove-highlight', function() {
            $(this).closest('.highlight-row').remove();
        });

        // Add Note row
        $('#btn_add_note').click(function(e) {
            e.preventDefault();
            const container = $('#notes_container');
            container.append(`
                <div class="note-row mb-2 p-2 border rounded bg-light d-flex align-items-center gap-2">
                    <span class="drag-handle cursor-grab px-2 text-muted"><i class="fas fa-grip-vertical"></i></span>
                    <input type="text" name="notes[]" class="form-control form-control-sm" required placeholder="e.g. Select Friday date for Saturday sunrise.">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-note"><i class="fas fa-trash"></i></button>
                </div>
            `);
        });

        // Remove Note row
        $(document).on('click', '.btn-remove-note', function() {
            $(this).closest('.note-row').remove();
        });

        // Add Additional Note row
        $('#btn_add_additional_note').click(function(e) {
            e.preventDefault();
            const container = $('#additional_notes_container');
            container.append(`
                <div class="note-row mb-2 p-2 border rounded bg-light d-flex align-items-center gap-2">
                    <span class="drag-handle cursor-grab px-2 text-muted"><i class="fas fa-grip-vertical"></i></span>
                    <input type="text" name="additional_notes[]" class="form-control form-control-sm" required placeholder="e.g. Select previous day date while booking.">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-additional-note"><i class="fas fa-trash"></i></button>
                </div>
            `);
        });

        // Remove Additional Note row
        $(document).on('click', '.btn-remove-additional-note', function() {
            $(this).closest('.note-row').remove();
        });

        // Add FAQ row
        $('#btn_add_faq').click(function(e) {
            e.preventDefault();
            const container = $('#faqs_container');
            const count = container.children('.faq-row').length;
            container.append(`
                <div class="faq-row mb-3 p-3 border rounded bg-light" id="faq_row_${count}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="drag-handle cursor-grab text-muted"><i class="fas fa-grip-vertical me-2"></i><strong>FAQ Item</strong></span>
                        <button type="button" class="btn btn-sm btn-danger btn-remove-faq"><i class="fas fa-trash"></i> Remove</button>
                    </div>
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small mb-1 fw-bold">Question</label>
                            <input type="text" name="faq_question[]" class="form-control form-control-sm" required placeholder="e.g. What is the difficulty level?">
                        </div>
                        <div class="col-12">
                            <label class="form-label small mb-1 fw-bold">Answer</label>
                            <textarea name="faq_answer[]" class="form-control form-control-sm" rows="2" required placeholder="e.g. Easy to Moderate."></textarea>
                        </div>
                    </div>
                </div>
            `);
        });

        // Remove FAQ row
        $(document).on('click', '.btn-remove-faq', function() {
            $(this).closest('.faq-row').remove();
        });

        // Live FAQ Search Filter
        $('#faq_search').on('input', function() {
            const query = $(this).val().toLowerCase();
            $('#faqs_container .faq-row').each(function() {
                const question = $(this).find('input[name="faq_question[]"]').val().toLowerCase();
                const answer = $(this).find('textarea[name="faq_answer[]"]').val().toLowerCase();
                if (question.includes(query) || answer.includes(query)) {
                    $(this).removeClass('d-none');
                } else {
                    $(this).addClass('d-none');
                }
            });
        });

        // Live Preview Modal Compile & Trigger
        $('#btn_preview_trek').click(function() {
            // 1. Compile Highlights
            const hlContainer = $('#preview_highlights_container');
            hlContainer.empty();
            let hasHl = false;
            
            $('#highlights_container .highlight-row').each(function() {
                const text = $(this).find('input[name="highlights[]"]').val().trim();
                const icon = $(this).find('select[name="highlight_icons[]"]').val();
                if (text) {
                    hasHl = true;
                    hlContainer.append(`
                        <div class="col-md-6 col-lg-4">
                            <div class="card p-3 h-100 border-0 shadow-sm d-flex flex-row align-items-center gap-2" style="border-radius: 12px; background: #ffffff;">
                                <div class="text-success fs-5"><i class="${icon}"></i></div>
                                <div class="fw-semibold small text-dark">${escapeHtml(text)}</div>
                            </div>
                        </div>
                    `);
                }
            });
            
            if (hasHl) {
                $('#preview_highlights_empty').addClass('d-none');
                hlContainer.removeClass('d-none');
            } else {
                $('#preview_highlights_empty').removeClass('d-none');
                hlContainer.addClass('d-none');
            }
            
            // 2. Compile Important Notes
            const notesContainer = $('#preview_notes_container');
            notesContainer.empty();
            let hasNotes = false;
            let notesText = '';
            
            $('#notes_container .note-row').each(function() {
                const text = $(this).find('input[name="notes[]"]').val().trim();
                if (text) {
                    hasNotes = true;
                    notesText += text + '\n';
                }
            });
            
            if (hasNotes) {
                $('#preview_notes_empty').addClass('d-none');
                notesContainer.removeClass('d-none');
                notesContainer.append(`
                    <div class="p-3 border-start border-warning border-4 rounded-3 mb-3" style="background-color: #FFFDF0;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning-emphasis fw-bold">
                            <i class="fas fa-exclamation-triangle"></i> Important Notes
                        </div>
                        <div class="text-muted small">${formatRichTextJs(notesText)}</div>
                    </div>
                `);
            } else {
                $('#preview_notes_empty').removeClass('d-none');
                notesContainer.addClass('d-none');
            }

            // 2d. Compile Additional Important Notes
            let hasAdditionalNotes = false;
            let additionalNotesText = '';
            $('#additional_notes_container .note-row').each(function() {
                const text = $(this).find('input[name="additional_notes[]"]').val().trim();
                if (text) {
                    hasAdditionalNotes = true;
                    additionalNotesText += text + '\n';
                }
            });

            if (hasAdditionalNotes) {
                $('#preview_notes_empty').addClass('d-none');
                notesContainer.removeClass('d-none');
                notesContainer.append(`
                    <div class="p-3 border-start border-warning border-4 rounded-3" style="background-color: #FFFDF0;">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning-emphasis fw-bold">
                            <i class="fas fa-exclamation-triangle"></i> Additional Important Notes
                        </div>
                        <div class="text-muted small">${formatRichTextJs(additionalNotesText)}</div>
                    </div>
                `);
            }
            
            // 3. Compile FAQs
            const faqsContainer = $('#preview_faqs_container');
            faqsContainer.empty();
            let hasFaqs = false;
            
            $('#faqs_container .faq-row').each(function(index) {
                const q = $(this).find('input[name="faq_question[]"]').val().trim();
                const a = $(this).find('textarea[name="faq_answer[]"]').val().trim();
                if (q && a) {
                    hasFaqs = true;
                    faqsContainer.append(`
                        <div class="accordion-item border rounded-3 mb-2 overflow-hidden shadow-sm bg-white">
                            <h2 class="accordion-header" id="heading_prev_${index}">
                                <button class="accordion-button collapsed fw-bold py-3 text-dark bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapse_prev_${index}" aria-expanded="false" aria-controls="collapse_prev_${index}" style="min-height: 48px; font-size: 0.9rem;">
                                    ${escapeHtml(q)}
                                </button>
                            </h2>
                            <div id="collapse_prev_${index}" class="accordion-collapse collapse" aria-labelledby="heading_prev_${index}" data-bs-parent="#preview_faqs_container">
                                <div class="accordion-body text-muted small border-top bg-light">
                                    ${escapeHtml(a).replace(/\\n/g, '<br>').replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    `);
                }
            });
            
            if (hasFaqs) {
                $('#preview_faqs_empty').addClass('d-none');
                faqsContainer.removeClass('d-none');
            } else {
                $('#preview_faqs_empty').removeClass('d-none');
                faqsContainer.addClass('d-none');
            }
            
            // Show Modal
            const modal = new bootstrap.Modal(document.getElementById('trekPreviewModal'));
            modal.show();
        });
        
        function formatRichTextJs(text) {
            let escaped = escapeHtml(text);
            escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            
            const lines = escaped.split('\n');
            let inList = false;
            let html = '';
            
            lines.forEach(line => {
                line = line.trim();
                if (!line) {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    html += '<br>';
                    return;
                }
                
                if (line.match(/^(&bull;|[•*\-])\s*(.*)/)) {
                    const content = line.replace(/^(&bull;|[•*\-])\s*/, '');
                    if (!inList) {
                        html += '<ul class="mb-0" style="padding-left: 1.2rem;">';
                        inList = true;
                    }
                    html += '<li>' + content + '</li>';
                } else {
                    if (inList) {
                        html += '</ul>';
                        inList = false;
                    }
                    html += '<div>' + line + '</div>';
                }
            });
            
            if (inList) {
                html += '</ul>';
            }
            
            return html;
        }
    } // End of Highlights/Notes/FAQs repeater block

    // Global helper
    function escapeHtml(string) {
        if (!string) return '';
        return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // =========================================================================
    // 12. SEO Character Counters & Tag Manager Widget
    // =========================================================================
    function initSeoCounters() {
        $('[data-seo-counter]').each(function() {
            var $input = $(this);
            var min = parseInt($input.data('seo-min') || 0);
            var max = parseInt($input.data('seo-max') || 0);
            var targetId = $input.data('seo-counter');
            var $badge = $('#' + targetId);

            function updateCounter() {
                var val = $input.val() || '';
                var len = val.length;
                var statusText = '';
                var badgeClass = 'badge ';

                if (len === 0) {
                    badgeClass += 'bg-light text-muted border';
                    statusText = '0 / ' + min + '–' + max + ' chars';
                } else if (len < min) {
                    badgeClass += 'bg-warning text-dark';
                    statusText = len + ' chars (Need ' + (min - len) + ' more for ' + min + '–' + max + ')';
                } else if (len > max) {
                    badgeClass += 'bg-danger text-white';
                    statusText = len + ' chars (Exceeds by ' + (len - max) + ' | Max: ' + max + ')';
                } else {
                    badgeClass += 'bg-success text-white';
                    statusText = '✓ Optimal (' + len + ' chars | ' + min + '–' + max + ')';
                }

                if ($badge.length) {
                    $badge.attr('class', badgeClass).text(statusText);
                }
            }

            $input.on('input keyup change paste', updateCounter);
            updateCounter();
        });
    }
    initSeoCounters();

    // Tag Manager Chip Widget
    function initTagManager() {
        $('.tag-manager-wrapper').each(function() {
            var $wrapper = $(this);
            var $hiddenInput = $wrapper.find('input.tag-hidden-input');
            var $textInput = $wrapper.find('input.tag-add-input');
            var $chipContainer = $wrapper.find('.tag-chips-container');
            var $countBadge = $wrapper.closest('.admin-card, .col-12').find('.tag-count-badge');
            if (!$countBadge.length) {
                $countBadge = $wrapper.find('.tag-count-badge');
            }
            var $addBtn = $wrapper.find('.btn-add-tag');

            function getTags() {
                var raw = $hiddenInput.val() || '';
                return raw.split(',').map(function(t) { return t.trim(); }).filter(function(t) { return t.length > 0; });
            }

            function setTags(tags) {
                // Ensure unique tags
                var unique = [];
                $.each(tags, function(i, el) {
                    if ($.inArray(el, unique) === -1) unique.push(el);
                });
                $hiddenInput.val(unique.join(', '));
                renderTags();
            }

            function renderTags() {
                var tags = getTags();
                $chipContainer.empty();

                if (tags.length === 0) {
                    $chipContainer.html('<span class="text-muted small fst-italic py-1 d-inline-block">No tags added yet. Type below and press Enter or comma.</span>');
                } else {
                    $.each(tags, function(index, tag) {
                        var $chip = $(`
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-2 me-1 mb-1 d-inline-flex align-items-center gap-1 font-monospace" style="font-size: 0.85rem; border-radius: 6px;">
                                <i class="fas fa-tag fa-xs opacity-75"></i>
                                <span>${escapeHtml(tag)}</span>
                                <button type="button" class="btn-close btn-close-xs ms-1 tag-remove-btn" data-index="${index}" style="font-size: 0.55rem; cursor: pointer;"></button>
                            </span>
                        `);
                        $chipContainer.append($chip);
                    });
                }

                // Update Tag Count Badge (Guidance: 10–12 tags)
                var count = tags.length;
                var badgeClass = 'badge ';
                var badgeText = '';

                if (count === 0) {
                    badgeClass += 'bg-light text-muted border';
                    badgeText = '0 tags (Recommended: 10–12)';
                } else if (count >= 10 && count <= 12) {
                    badgeClass += 'bg-success text-white';
                    badgeText = '✓ Optimal: ' + count + ' / 12 tags';
                } else if (count < 10) {
                    badgeClass += 'bg-warning text-dark';
                    badgeText = count + ' tags (Recommended: 10–12 tags | Need ' + (10 - count) + ' more)';
                } else {
                    badgeClass += 'bg-danger text-white';
                    badgeText = count + ' tags (Recommended max: 12 tags | Exceeds by ' + (count - 12) + ')';
                }

                if ($countBadge.length) {
                    $countBadge.attr('class', 'tag-count-badge ' + badgeClass).text(badgeText);
                }
            }

            function addCurrentInputTag() {
                var val = $textInput.val().trim();
                if (!val) return;

                // Support comma-separated batch input
                var parts = val.split(',');
                var existing = getTags();

                $.each(parts, function(i, part) {
                    var clean = part.trim().replace(/^#/, '');
                    if (clean && $.inArray(clean, existing) === -1) {
                        existing.push(clean);
                    }
                });

                setTags(existing);
                $textInput.val('').focus();
            }

            $addBtn.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                addCurrentInputTag();
            });

            $textInput.on('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    e.stopPropagation();
                    addCurrentInputTag();
                }
            });

            $wrapper.on('click', '.tag-remove-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var idx = parseInt($(this).data('index'));
                var tags = getTags();
                tags.splice(idx, 1);
                setTags(tags);
            });

            // Initial render
            renderTags();
        });
    }
    initTagManager();
});
