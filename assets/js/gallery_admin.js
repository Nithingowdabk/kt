$(document).ready(function() {
    const _trekId = typeof TREK_ID !== 'undefined' ? TREK_ID : 0;
    const _sessionToken = typeof SESSION_TOKEN !== 'undefined' ? SESSION_TOKEN : '';
    const SITE_URL = typeof JS_SITE_URL !== 'undefined' ? JS_SITE_URL : '';
    const API_URL = '../treks/gallery_api.php';
    const VIDEO_API_URL = '../treks/video_api.php';

    function getQueryString() {
        if (_trekId > 0) return `?trek_id=${_trekId}`;
        return `?session_token=${_sessionToken}`;
    }

    function appendSession(dataObj) {
        if (_trekId > 0) {
            dataObj.trek_id = _trekId;
        } else {
            dataObj.session_token = _sessionToken;
        }
        return dataObj;
    }

    function updateGalleryCount() {
        let count = $('#gallery-grid .gallery-item').not('.uploading-placeholder').length;
        $('#gallery-count').text(count);
    }

    function checkImageRatio(imgEl) {
        let w = imgEl[0].naturalWidth;
        let h = imgEl[0].naturalHeight;
        if (w === 0 || h === 0) return;
        
        let ratio = w / h;
        let idealRatio = 16 / 9;
        let warnings = [];
        
        if (h > w) {
            warnings.push("Portrait image uploaded. Hero gallery requires landscape images.");
        } else {
            if (Math.abs(ratio - idealRatio) > 0.15) {
                warnings.push("Aspect ratio differs significantly from 16:9.");
            }
            if (w < 1280 || h < 720) {
                warnings.push("Resolution is too low. Minimum recommended is 1280x720.");
            }
        }
        
        let cardBody = imgEl.closest('.card').find('.card-body');
        cardBody.find('.ratio-warning').remove();
        
        if (warnings.length > 0) {
            let warnMsg = warnings.join("<br>");
            cardBody.prepend(`
                <div class="alert alert-warning py-1 px-2 mb-2 ratio-warning text-start" style="font-size: 11px; line-height: 1.2;">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> ${warnMsg}<br>
                    <span class="text-secondary small d-block mt-1">Recommended hero image ratio is 16:9 (1920&times;1080). Current image may experience cropping.</span>
                </div>
            `);
        }
    }

    // --- GALLERY MANAGEMENT ---
    const dropzone = $('#gallery-dropzone');
    const fileInput = $('#gallery-file-input');

    dropzone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('bg-success bg-opacity-10');
    });

    dropzone.on('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('bg-success bg-opacity-10');
    });

    dropzone.on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('bg-success bg-opacity-10');
        const files = e.originalEvent.dataTransfer.files;
        uploadFiles(files);
    });

    fileInput.on('change', function() {
        uploadFiles(this.files);
        $(this).val(''); // Reset input so same file can be selected again
    });

    function uploadFiles(files) {
        console.log(files);
        console.log(files.length);
        if (!files.length) return;
        
        let grid = $('#gallery-grid');
        // Remove empty state if present
        grid.find('.empty-state').remove();

        let uploadsRemaining = files.length;
        let successCount = 0;

        function checkGlobalCompletion() {
            uploadsRemaining--;
            if (uploadsRemaining === 0) {
                if (successCount > 0) {
                    // Show a toast or simple alert
                    let toastHtml = `
                        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
                            <div class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="fas fa-check-circle me-2"></i> ${successCount} image(s) uploaded successfully.
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        </div>
                    `;
                    let toastEl = $(toastHtml).appendTo('body');
                    setTimeout(() => {
                        toastEl.fadeOut(300, function() { $(this).remove(); });
                    }, 4000);

                    // Auto-execute loadGallery after success
                    loadGallery();
                }
                updateGalleryCount();
            }
        }

        Array.from(files).forEach((file, index) => {
            // Create a unique ID for this upload block
            let tempId = 'upload_' + Date.now() + '_' + index;
            
            // Build the placeholder HTML
            let placeholderHtml = `
                <div class="col-6 col-md-4 col-lg-3 gallery-item uploading-placeholder" id="${tempId}">
                    <div class="card h-100 shadow-sm border-0 position-relative">
                        <div class="img-preview-container" style="height: 150px; background: #eee; overflow: hidden; position: relative;">
                            <img src="" class="card-img-top w-100 h-100" style="object-fit: cover; opacity: 0.5;">
                            <div class="position-absolute top-50 start-50 translate-middle text-center w-100 px-2 upload-overlay">
                                <span class="badge bg-secondary mb-1 status-text">Uploading... 0%</span>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2 text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger btn-cancel-upload w-100">Cancel Upload</button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-retry-upload w-100 d-none mt-2">Retry Upload</button>
                        </div>
                    </div>
                </div>
            `;
            grid.append(placeholderHtml);

            let placeholderEl = $('#' + tempId);

            // Read image for preview & check ratio
            let reader = new FileReader();
            reader.onload = function(e) {
                let imgPreview = placeholderEl.find('img');
                imgPreview.attr('src', e.target.result);
                imgPreview.on('load', function() {
                    let w = this.naturalWidth;
                    let h = this.naturalHeight;
                    let ratio = w / h;
                    let idealRatio = 16 / 9;
                    let warnings = [];
                    
                    if (h > w) {
                        warnings.push("Portrait image uploaded. Hero gallery requires landscape images.");
                    } else {
                        if (Math.abs(ratio - idealRatio) > 0.15) {
                            warnings.push("Aspect ratio differs significantly from 16:9.");
                        }
                        if (w < 1280 || h < 720) {
                            warnings.push("Resolution is too low. Minimum recommended is 1280x720.");
                        }
                    }
                    
                    let cardBody = placeholderEl.find('.card-body');
                    cardBody.find('.ratio-warning').remove();
                    if (warnings.length > 0) {
                        let warnMsg = warnings.join("<br>");
                        cardBody.prepend(`
                            <div class="alert alert-warning py-1 px-2 mb-2 ratio-warning text-start" style="font-size: 11px; line-height: 1.2;">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Warning:</strong> ${warnMsg}<br>
                                <span class="text-secondary small d-block mt-1">Recommended hero image ratio is 16:9 (1920&times;1080). Current image may experience cropping.</span>
                            </div>
                        `);
                    }
                });
            }
            reader.readAsDataURL(file);

            // Start upload
            function doUpload() {
                placeholderEl.find('.status-text').removeClass('bg-danger').addClass('bg-secondary').text('Uploading...');
                placeholderEl.find('.progress').removeClass('d-none');
                placeholderEl.find('.progress-bar').css('width', '0%');
                placeholderEl.find('.btn-cancel-upload').removeClass('d-none');
                placeholderEl.find('.btn-retry-upload').addClass('d-none');

                let formData = new FormData();
                formData.append('action', 'upload');
                formData.append('file', file);
                
                if (_trekId > 0) {
                    formData.append('trek_id', _trekId);
                } else {
                    formData.append('session_token', _sessionToken);
                }

                let xhr = new window.XMLHttpRequest();
                
                xhr.upload.addEventListener("progress", function(evt) {
                    if (evt.lengthComputable) {
                        let percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        placeholderEl.find('.progress-bar').css('width', percentComplete + '%');
                        placeholderEl.find('.status-text').text(`Uploading... ${percentComplete}%`);
                    }
                }, false);

                // Store xhr to allow cancellation
                placeholderEl.data('xhr', xhr);

                $.ajax({
                    xhr: function() { return xhr; },
                    url: API_URL,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(res) {
                        if (res.status === 'success') {
                            successCount++;
                            placeholderEl.find('.status-text').removeClass('bg-secondary bg-danger').addClass('bg-success').html('<i class="fas fa-check"></i> Success');
                            placeholderEl.find('.progress').addClass('d-none');
                            placeholderEl.find('.card-body').html('<span class="text-success small fw-bold">Processing...</span>');
                            
                            // Fetch the single newly uploaded item to replace the placeholder
                            $.getJSON(`${API_URL}${getQueryString()}&action=list`, function(listRes) {
                                let newImg = listRes.data.find(i => i.id == res.id);
                                if (newImg) {
                                    let newHtml = renderGalleryCard(newImg);
                                    let newEl = $(newHtml);
                                    placeholderEl.replaceWith(newEl);
                                    let newImgEl = newEl.find('img');
                                    if (newImgEl[0].complete) {
                                        checkImageRatio(newImgEl);
                                    } else {
                                        newImgEl.on('load', function() {
                                            checkImageRatio(newImgEl);
                                        });
                                    }
                                }
                            });
                        }
                        checkGlobalCompletion();
                    },
                    error: function(err, textStatus) {
                        if (textStatus === 'abort') {
                            placeholderEl.remove(); // Just remove it silently
                            checkGlobalCompletion();
                        } else {
                            placeholderEl.find('.status-text').removeClass('bg-secondary').addClass('bg-danger').text('Upload Failed');
                            placeholderEl.find('.progress').addClass('d-none');
                            placeholderEl.find('.btn-cancel-upload').addClass('d-none');
                            placeholderEl.find('.btn-retry-upload').removeClass('d-none');
                            checkGlobalCompletion();
                        }
                    }
                });
            }

            // Bind cancel & retry
            placeholderEl.on('click', '.btn-cancel-upload', function() {
                let currentXhr = placeholderEl.data('xhr');
                if (currentXhr) {
                    currentXhr.abort();
                } else {
                    placeholderEl.remove();
                }
            });

            placeholderEl.on('click', '.btn-retry-upload', function() {
                // We must increment uploadsRemaining because we already decremented it during the failure
                uploadsRemaining++; 
                doUpload();
            });

            doUpload();
        });
    }

    function renderGalleryCard(img) {
        let optionsHtml = '<option value="">General</option>';
        GALLERY_CATEGORIES.forEach(cat => {
            let sel = cat.id == img.category_id ? 'selected' : '';
            optionsHtml += `<option value="${cat.id}" ${sel}>${cat.category_name}</option>`;
        });

        let isFeatured = img.is_featured == 1;
        let borderClass = isFeatured ? 'border border-success border-3 shadow' : 'border-0 shadow-sm';
        let featuredBadge = isFeatured ? '<span class="badge bg-success text-white position-absolute top-0 start-0 m-2"><i class="fas fa-star"></i> FEATURED IMAGE</span>' : '';
        let featuredBtn = isFeatured ? '' : `<button type="button" class="btn btn-sm btn-outline-success w-100 btn-set-featured" data-id="${img.id}"><i class="fas fa-star"></i> Set Featured</button>`;
        
        let thumbUrl = img.thumbnail_path ? (SITE_URL + '/' + img.thumbnail_path) : (SITE_URL + '/' + img.image_path);
        let fullUrl = SITE_URL + '/' + img.image_path;

        return `
            <div class="col-6 col-md-4 col-lg-3 gallery-item" data-id="${img.id}">
                <div class="card h-100 ${borderClass} position-relative">
                    ${featuredBadge}
                    <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 btn-delete-img" data-id="${img.id}"><i class="fas fa-trash"></i></button>
                    
                    <a href="javascript:void(0)" class="lightbox-trigger" data-src="${fullUrl}">
                        <img src="${thumbUrl}" class="card-img-top" style="height: 150px; object-fit: cover; cursor: zoom-in;" alt="Gallery Image">
                    </a>
                    
                    <div class="card-body p-2">
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm img-title" placeholder="Title (optional)" value="${img.image_title || ''}" data-id="${img.id}">
                        </div>
                        <div class="mb-2">
                            <select class="form-select form-select-sm img-category" data-id="${img.id}">
                                ${optionsHtml}
                            </select>
                        </div>
                        <div class="featured-btn-container">
                            ${featuredBtn}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    function loadGallery() {
        const grid = $('#gallery-grid');
        
        // Show Skeleton Loaders
        grid.empty();
        for (let i=0; i<4; i++) {
            grid.append(`
                <div class="col-6 col-md-4 col-lg-3 skeleton-loader">
                    <div class="card h-100 shadow-sm border-0">
                        <div style="height: 150px; background: #e9ecef;" class="card-img-top placeholder-glow"><span class="placeholder w-100 h-100"></span></div>
                        <div class="card-body p-2 placeholder-glow">
                            <span class="placeholder col-12 mb-2"></span>
                            <span class="placeholder col-12 mb-2"></span>
                            <span class="placeholder col-12"></span>
                        </div>
                    </div>
                </div>
            `);
        }

        $.getJSON(`${API_URL}${getQueryString()}&action=list`, function(res) {
            grid.empty();
            
            if (res.data.length === 0) {
                grid.html('<div class="col-12 text-center text-muted py-5 empty-state"><i class="fas fa-images fa-3x mb-3 text-black-50"></i><br>No gallery images uploaded yet.</div>');
                updateGalleryCount();
                return;
            }

            res.data.forEach(img => {
                grid.append(renderGalleryCard(img));
            });

            updateGalleryCount();

            // Run ratio checks on loaded gallery images
            $('#gallery-grid img').each(function() {
                let imgEl = $(this);
                if (this.complete) {
                    checkImageRatio(imgEl);
                } else {
                    imgEl.on('load', function() {
                        checkImageRatio(imgEl);
                    });
                }
            });

            // Initialize Sortable
            new Sortable(document.getElementById('gallery-grid'), {
                animation: 150,
                ghostClass: 'bg-light',
                onEnd: function() {
                    let orders = {};
                    $('#gallery-grid .gallery-item').each(function(index) {
                        orders[$(this).data('id')] = index;
                    });
                    
                    $.post(API_URL, { action: 'reorder', orders: orders });
                }
            });
        });
    }

    // Auto-save title/category changes
    $(document).on('change', '.img-title, .img-category', function() {
        let id = $(this).data('id');
        let card = $(this).closest('.card-body');
        let title = card.find('.img-title').val();
        let cat_id = card.find('.img-category').val();

        $.post(API_URL, { action: 'update_meta', id: id, title: title, category_id: cat_id });
    });

    $(document).on('click', '.btn-set-featured', function() {
        let btn = $(this);
        let id = btn.data('id');
        let payload = appendSession({ action: 'set_featured', id: id });
        
        // Immediate UI update
        // Remove featured from others
        $('.gallery-item .card').removeClass('border border-success border-3 shadow').addClass('border-0 shadow-sm');
        $('.gallery-item .badge').remove();
        
        // Add "Set Featured" button back to previous featured image
        $('.gallery-item').each(function() {
            let itemId = $(this).data('id');
            if ($(this).find('.btn-set-featured').length === 0 && itemId != id) {
                $(this).find('.featured-btn-container').html(`<button type="button" class="btn btn-sm btn-outline-success w-100 btn-set-featured" data-id="${itemId}"><i class="fas fa-star"></i> Set Featured</button>`);
            }
        });

        // Add featured to current
        let card = btn.closest('.card');
        card.removeClass('border-0 shadow-sm').addClass('border border-success border-3 shadow');
        card.prepend('<span class="badge bg-success text-white position-absolute top-0 start-0 m-2"><i class="fas fa-star"></i> FEATURED IMAGE</span>');
        btn.remove();

        $.post(API_URL, payload);
    });

    $(document).on('click', '.btn-delete-img', function() {
        if (!confirm('Are you sure you want to delete this image?')) return;
        let btn = $(this);
        let id = btn.data('id');
        
        btn.closest('.gallery-item').fadeOut(300, function() {
            $(this).remove();
            updateGalleryCount();
            if ($('#gallery-grid .gallery-item').length === 0) {
                $('#gallery-grid').html('<div class="col-12 text-center text-muted py-5 empty-state"><i class="fas fa-images fa-3x mb-3 text-black-50"></i><br>No gallery images uploaded yet.</div>');
            }
        });

        $.post(API_URL, { action: 'delete', id: id });
    });

    // Lightbox
    $(document).on('click', '.lightbox-trigger', function() {
        let src = $(this).data('src');
        $('#lightbox-image').attr('src', src);
        let modal = new bootstrap.Modal(document.getElementById('galleryLightboxModal'));
        modal.show();
    });

    // --- VIDEO MANAGEMENT ---
    function loadVideos() {
        $.getJSON(`${VIDEO_API_URL}${getQueryString()}&action=list`, function(res) {
            const list = $('#video-list');
            list.empty();
            
            if (res.data.length === 0) {
                list.html('<div class="text-center text-muted py-3">No videos added yet.</div>');
                return;
            }

            res.data.forEach(vid => {
                let html = `
                    <div class="list-group-item d-flex justify-content-between align-items-center video-item" data-id="${vid.id}">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-grip-vertical text-muted cursor-move"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">${vid.title || 'Untitled Video'}</h6>
                                <small class="text-muted"><a href="${vid.youtube_url}" target="_blank">${vid.youtube_url}</a></small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-video" data-id="${vid.id}"><i class="fas fa-times"></i></button>
                    </div>
                `;
                list.append(html);
            });

            new Sortable(document.getElementById('video-list'), {
                animation: 150,
                handle: '.cursor-move',
                onEnd: function() {
                    let orders = {};
                    $('#video-list .video-item').each(function(index) {
                        orders[$(this).data('id')] = index;
                    });
                    $.post(VIDEO_API_URL, { action: 'reorder', orders: orders });
                }
            });
        });
    }

    $('#btn-add-video').on('click', function() {
        let url = $('#video-url-input').val();
        let title = $('#video-title-input').val();
        
        if (!url) {
            alert("Please enter a YouTube URL.");
            return;
        }

        let payload = appendSession({ action: 'add', youtube_url: url, title: title });

        $.post(VIDEO_API_URL, payload, function(res) {
            if (res.status === 'success') {
                $('#video-url-input').val('');
                $('#video-title-input').val('');
                loadVideos();
            }
        }).fail(function(err) {
            alert('Error adding video: ' + (err.responseJSON?.message || 'Unknown'));
        });
    });

    $(document).on('click', '.btn-delete-video', function() {
        if (!confirm('Remove this video?')) return;
        let id = $(this).data('id');
        $.post(VIDEO_API_URL, { action: 'delete', id: id }, function() {
            loadVideos();
        });
    });

    // Initialize gallery and video lists
    loadGallery();
    loadVideos();
});
