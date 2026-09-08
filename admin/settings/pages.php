<?php
/**
 * Admin - Footer Information Pages & FAQs Management
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login
require_admin_login();

$db = Database::connect();

$success = '';
$error = '';
$active_tab = sanitize_input($_GET['tab'] ?? 'faqs');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_tab = sanitize_input($_POST['action_tab'] ?? 'faqs');
    $active_tab = $action_tab;

    try {
        $stmt = $db->prepare("INSERT INTO settings (key_name, value_data) VALUES (?, ?) ON DUPLICATE KEY UPDATE value_data = ?, updated_at = CURRENT_TIMESTAMP");

        if ($action_tab === 'faqs') {
            $questions = $_POST['faq_questions'] ?? [];
            $answers = $_POST['faq_answers'] ?? [];
            $faqs = [];

            for ($i = 0; $i < count($questions); $i++) {
                $q = trim($questions[$i] ?? '');
                $a = trim($answers[$i] ?? '');
                if (!empty($q) && !empty($a)) {
                    $faqs[] = [
                        'question' => $q,
                        'answer' => $a
                    ];
                }
            }

            $json_val = json_encode($faqs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $stmt->execute(['page_faqs_json', $json_val, $json_val]);
            log_activity('settings_update_faqs', ['faq_count' => count($faqs)]);
            $success = "FAQs updated successfully!";

        } elseif ($action_tab === 'contact') {
            $contact_address = sanitize_input($_POST['contact_address'] ?? '');
            $contact_phone = sanitize_input($_POST['contact_phone'] ?? '');
            $contact_email = sanitize_input($_POST['contact_email'] ?? '');
            $contact_whatsapp = sanitize_input($_POST['contact_whatsapp'] ?? '');
            $contact_hours = sanitize_input($_POST['contact_hours'] ?? '');

            $stmt->execute(['contact_address', $contact_address, $contact_address]);
            $stmt->execute(['contact_phone', $contact_phone, $contact_phone]);
            $stmt->execute(['contact_email', $contact_email, $contact_email]);
            $stmt->execute(['contact_whatsapp', $contact_whatsapp, $contact_whatsapp]);
            $stmt->execute(['contact_hours', $contact_hours, $contact_hours]);

            log_activity('settings_update_contact_info', ['phone' => $contact_phone]);
            $success = "Contact details updated successfully!";

        } elseif ($action_tab === 'terms') {
            $terms_content = $_POST['page_terms_content'] ?? '';
            $stmt->execute(['page_terms_content', $terms_content, $terms_content]);
            log_activity('settings_update_terms', []);
            $success = "Terms & Conditions page content updated successfully!";

        } elseif ($action_tab === 'privacy') {
            $privacy_content = $_POST['page_privacy_content'] ?? '';
            $stmt->execute(['page_privacy_content', $privacy_content, $privacy_content]);
            log_activity('settings_update_privacy', []);
            $success = "Privacy Policy page content updated successfully!";

        } elseif ($action_tab === 'cancellation') {
            $cancellation_content = $_POST['page_cancellation_content'] ?? '';
            $stmt->execute(['page_cancellation_content', $cancellation_content, $cancellation_content]);
            log_activity('settings_update_cancellation', []);
            $success = "Refund & Cancellation Policy updated successfully!";
        }

    } catch (PDOException $e) {
        $error = "Failed to update configurations: " . $e->getMessage();
    }
}

// Fetch current configurations
$faqs_json = get_setting('page_faqs_json', '[]');
$faqs_list = json_decode($faqs_json, true) ?: [];

$contact_address = get_setting('contact_address', CONTACT_ADDRESS);
$contact_phone = get_setting('contact_phone', CONTACT_PHONE);
$contact_email = get_setting('contact_email', CONTACT_EMAIL);
$contact_whatsapp = get_setting('contact_whatsapp', '+918951221179');
$contact_hours = get_setting('contact_hours', 'Mon – Sun: 8:00 AM – 9:00 PM');

$terms_content = get_setting('page_terms_content', '');
$privacy_content = get_setting('page_privacy_content', '');
$cancellation_content = get_setting('page_cancellation_content', '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer & Information Pages Settings | Admin Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Summernote WYSIWYG Editor CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>/assets/css/responsive.css" rel="stylesheet">
    <style>
        .note-editor.note-frame {
            border-radius: 10px;
            border-color: #dee2e6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background-color: #fff;
        }
        .note-toolbar {
            background-color: #f8f9fa !important;
            border-top-left-radius: 9px !important;
            border-top-right-radius: 9px !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
        .note-editable {
            background-color: #fff;
            min-height: 350px;
            font-size: 15px;
            line-height: 1.6;
        }
    </style>
</head>
<body class="admin-body">

<div class="d-flex" id="wrapper">
    <!-- Sidebar Navigation -->
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper">
        <!-- Top Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light admin-navbar border-bottom">
            <div class="container-fluid">
                <button class="btn btn-success btn-sm" id="menu-toggle"><i class="fas fa-bars"></i></button>
                <div class="ms-auto d-flex align-items-center gap-3">
                    <span class="text-muted small">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_name']); ?></strong></span>
                    <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-power-off me-1"></i>Logout</a>
                </div>
            </div>
        </nav>

        <!-- Main Workspace -->
        <div class="container-fluid p-4">
            
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <div>
                    <h2 class="fw-bold mb-0 text-success">Footer & Information Pages</h2>
                    <p class="text-muted mb-0">Manage content for FAQs, Contact Details, Terms & Conditions, Privacy Policy, and Cancellation rules.</p>
                </div>
            </div>

            <!-- Settings Sub-Nav -->
            <div class="mb-4">
                <div class="btn-group flex-wrap shadow-sm">
                    <a href="<?php echo SITE_URL; ?>/admin/settings/general.php" class="btn btn-outline-success">General</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/payment.php" class="btn btn-outline-success">Payments</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/seo.php" class="btn btn-outline-success">SEO tags</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/announcement.php" class="btn btn-outline-success">Announcement Bar</a>
                    <a href="<?php echo SITE_URL; ?>/admin/settings/pages.php" class="btn btn-success active"><i class="fas fa-file-alt me-1"></i>Footer Pages & FAQs</a>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs for Footer Pages -->
            <ul class="nav nav-tabs nav-fill mb-4 bg-white p-2 rounded shadow-sm border" id="footerPagesTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold <?php echo ($active_tab === 'faqs') ? 'active text-success' : 'text-dark'; ?>" id="faqs-tab" data-bs-toggle="tab" data-bs-target="#faqs-pane" type="button" role="tab">
                        <i class="fas fa-question-circle me-1"></i> FAQs Manager
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold <?php echo ($active_tab === 'contact') ? 'active text-success' : 'text-dark'; ?>" id="contact-tab" data-bs-toggle="tab" data-bs-target="#contact-pane" type="button" role="tab">
                        <i class="fas fa-headset me-1"></i> Contact Support
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold <?php echo ($active_tab === 'terms') ? 'active text-success' : 'text-dark'; ?>" id="terms-tab" data-bs-toggle="tab" data-bs-target="#terms-pane" type="button" role="tab">
                        <i class="fas fa-gavel me-1"></i> Terms & Conditions
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold <?php echo ($active_tab === 'privacy') ? 'active text-success' : 'text-dark'; ?>" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy-pane" type="button" role="tab">
                        <i class="fas fa-user-shield me-1"></i> Privacy Policy
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-bold <?php echo ($active_tab === 'cancellation') ? 'active text-success' : 'text-dark'; ?>" id="cancellation-tab" data-bs-toggle="tab" data-bs-target="#cancellation-pane" type="button" role="tab">
                        <i class="fas fa-undo-alt me-1"></i> Refund & Cancellation
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="footerPagesTabContent">
                
                <!-- TAB 1: FAQS -->
                <div class="tab-pane fade <?php echo ($active_tab === 'faqs') ? 'show active' : ''; ?>" id="faqs-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-3">
                            <div>
                                <h5 class="fw-bold mb-0 text-success"><i class="fas fa-question-circle me-2"></i>Manage Frequently Asked Questions</h5>
                                <small class="text-muted">These questions and answers display dynamically on the <a href="<?php echo SITE_URL; ?>/faq.php" target="_blank" class="text-success fw-bold">FAQ Page</a>.</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-success" id="btn-add-faq">
                                <i class="fas fa-plus me-1"></i>Add New Question
                            </button>
                        </div>
                        <div class="card-body p-4">
                            <form action="?tab=faqs" method="POST">
                                <input type="hidden" name="action_tab" value="faqs">
                                
                                <div id="faqs-container">
                                    <?php if (!empty($faqs_list)): ?>
                                        <?php foreach ($faqs_list as $idx => $faq): ?>
                                            <div class="faq-row card mb-3 border bg-light p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="fw-bold text-success faq-index-badge"><i class="fas fa-question me-1"></i>Question #<?php echo ($idx + 1); ?></span>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-faq"><i class="fas fa-trash-alt me-1"></i>Remove</button>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">Question *</label>
                                                    <input type="text" name="faq_questions[]" class="form-control" value="<?php echo htmlspecialchars($faq['question'] ?? ''); ?>" required placeholder="e.g. Are forest permits included?">
                                                </div>
                                                <div>
                                                    <label class="form-label small fw-bold">Answer *</label>
                                                    <textarea name="faq_answers[]" class="form-control" rows="3" required placeholder="Write the complete answer..."><?php echo htmlspecialchars($faq['answer'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="faq-row card mb-3 border bg-light p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold text-success faq-index-badge"><i class="fas fa-question me-1"></i>Question #1</span>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-faq"><i class="fas fa-trash-alt me-1"></i>Remove</button>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-bold">Question *</label>
                                                <input type="text" name="faq_questions[]" class="form-control" required placeholder="e.g. Are forest permits included in the trek price?">
                                            </div>
                                            <div>
                                                <label class="form-label small fw-bold">Answer *</label>
                                                <textarea name="faq_answers[]" class="form-control" rows="3" required placeholder="Write answer here..."></textarea>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-add-faq-bottom"><i class="fas fa-plus me-1"></i>Add Another FAQ</button>
                                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fas fa-save me-2"></i>Save All FAQs</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: CONTACT SUPPORT -->
                <div class="tab-pane fade <?php echo ($active_tab === 'contact') ? 'show active' : ''; ?>" id="contact-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 class="fw-bold mb-0 text-success"><i class="fas fa-headset me-2"></i>Contact Support Details</h5>
                            <small class="text-muted">These contact coordinates display on the <a href="<?php echo SITE_URL; ?>/contact.php" target="_blank" class="text-success fw-bold">Contact Us Page</a>, Navbar, and Footer.</small>
                        </div>
                        <div class="card-body p-4">
                            <form action="?tab=contact" method="POST">
                                <input type="hidden" name="action_tab" value="contact">
                                
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Official Support Phone / Helpline *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone-alt text-success"></i></span>
                                            <input type="text" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($contact_phone); ?>" required placeholder="+91 89512 21179">
                                        </div>
                                        <small class="text-muted">Used for direct call buttons and support coordinates.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Official Support Email *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope text-success"></i></span>
                                            <input type="email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($contact_email); ?>" required placeholder="info@karnatakatrekkers.in">
                                        </div>
                                        <small class="text-muted">Where customer enquiries and receipts are directed.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Official WhatsApp Number / Chat Link</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                                            <input type="text" name="contact_whatsapp" class="form-control" value="<?php echo htmlspecialchars($contact_whatsapp); ?>" placeholder="+918951221179 or https://wa.me/...">
                                        </div>
                                        <small class="text-muted">Used for WhatsApp chat button & direct notifications.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Operating / Support Hours</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="far fa-clock text-success"></i></span>
                                            <input type="text" name="contact_hours" class="form-control" value="<?php echo htmlspecialchars($contact_hours); ?>" placeholder="e.g. Mon – Sun: 8:00 AM – 9:00 PM">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold">Headquarters / Physical Address *</label>
                                        <textarea name="contact_address" class="form-control" rows="3" required placeholder="Full registered business address..."><?php echo htmlspecialchars($contact_address); ?></textarea>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fas fa-save me-2"></i>Save Contact Settings</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: TERMS & CONDITIONS -->
                <div class="tab-pane fade <?php echo ($active_tab === 'terms') ? 'show active' : ''; ?>" id="terms-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 class="fw-bold mb-0 text-success"><i class="fas fa-gavel me-2"></i>Edit Terms & Conditions Content</h5>
                            <small class="text-muted">Visual WYSIWYG Editor: Format headings, bullet lists, and paragraphs visually. Displays on the public <a href="<?php echo SITE_URL; ?>/terms.php" target="_blank" class="text-success fw-bold">Terms & Conditions Page</a>.</small>
                        </div>
                        <div class="card-body p-4">
                            <form action="?tab=terms" method="POST">
                                <input type="hidden" name="action_tab" value="terms">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Terms & Conditions Content *</label>
                                    <textarea name="page_terms_content" class="form-control summernote-editor" required><?php echo htmlspecialchars($terms_content); ?></textarea>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fas fa-save me-2"></i>Save Terms & Conditions</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: PRIVACY POLICY -->
                <div class="tab-pane fade <?php echo ($active_tab === 'privacy') ? 'show active' : ''; ?>" id="privacy-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 class="fw-bold mb-0 text-success"><i class="fas fa-user-shield me-2"></i>Edit Privacy Policy Content</h5>
                            <small class="text-muted">Visual WYSIWYG Editor: Format text and headings visually without writing HTML. Displays on the public <a href="<?php echo SITE_URL; ?>/privacy.php" target="_blank" class="text-success fw-bold">Privacy Policy Page</a>.</small>
                        </div>
                        <div class="card-body p-4">
                            <form action="?tab=privacy" method="POST">
                                <input type="hidden" name="action_tab" value="privacy">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Privacy Policy Content *</label>
                                    <textarea name="page_privacy_content" class="form-control summernote-editor" required><?php echo htmlspecialchars($privacy_content); ?></textarea>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fas fa-save me-2"></i>Save Privacy Policy</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: REFUND & CANCELLATION -->
                <div class="tab-pane fade <?php echo ($active_tab === 'cancellation') ? 'show active' : ''; ?>" id="cancellation-pane" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3">
                            <h5 class="fw-bold mb-0 text-success"><i class="fas fa-undo-alt me-2"></i>Edit Refund & Cancellation Policy</h5>
                            <small class="text-muted">Visual WYSIWYG Editor: Format refund rules, charges, and timelines. Displays on the public <a href="<?php echo SITE_URL; ?>/cancellation.php" target="_blank" class="text-success fw-bold">Refund & Cancellation Page</a>.</small>
                        </div>
                        <div class="card-body p-4">
                            <form action="?tab=cancellation" method="POST">
                                <input type="hidden" name="action_tab" value="cancellation">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Cancellation & Refund Policy *</label>
                                    <textarea name="page_cancellation_content" class="form-control summernote-editor" required><?php echo htmlspecialchars($cancellation_content); ?></textarea>
                                </div>

                                <div class="mt-4 pt-3 border-top text-end">
                                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="fas fa-save me-2"></i>Save Refund Policy</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Summernote WYSIWYG JS -->
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
<script>
$(document).ready(function() {
    // Initialize Summernote WYSIWYG Visual Editor
    $('.summernote-editor').summernote({
        placeholder: 'Write and format your content visually here...',
        tabsize: 2,
        height: 380,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'hr']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ]
    });

    // Handle tab switching for Summernote rendering
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        $('.summernote-editor').each(function() {
            $(this).summernote('resize');
        });
    });

    function reindexFaqs() {
        $('#faqs-container .faq-row').each(function(idx) {
            $(this).find('.faq-index-badge').html('<i class="fas fa-question me-1"></i>Question #' + (idx + 1));
        });
    }

    function addFaqRow() {
        var count = $('#faqs-container .faq-row').length + 1;
        var html = `
            <div class="faq-row card mb-3 border bg-light p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold text-success faq-index-badge"><i class="fas fa-question me-1"></i>Question #${count}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-faq"><i class="fas fa-trash-alt me-1"></i>Remove</button>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Question *</label>
                    <input type="text" name="faq_questions[]" class="form-control" required placeholder="e.g. Is transport included from Bengaluru?">
                </div>
                <div>
                    <label class="form-label small fw-bold">Answer *</label>
                    <textarea name="faq_answers[]" class="form-control" rows="3" required placeholder="Write answer here..."></textarea>
                </div>
            </div>
        `;
        $('#faqs-container').append(html);
        reindexFaqs();
    }

    $('#btn-add-faq, #btn-add-faq-bottom').click(function(e) {
        e.preventDefault();
        addFaqRow();
    });

    $(document).on('click', '.btn-remove-faq', function(e) {
        e.preventDefault();
        if ($('#faqs-container .faq-row').length > 1) {
            $(this).closest('.faq-row').remove();
            reindexFaqs();
        } else {
            alert('At least one FAQ item is required.');
        }
    });
});
</script>
</body>
</html>
