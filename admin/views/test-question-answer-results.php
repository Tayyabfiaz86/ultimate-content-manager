<div class="wrap">
    <h1>Test Question&Answer Results</h1>

    <?php
    if (!class_exists('Test_Question_Answer_List_Table')) {
        require_once plugin_dir_path(__FILE__) . '../../class-test-question-answer-list-table.php';
    }

    $list_table = new Test_Question_Answer_List_Table();
    $list_table->prepare_items();
    ?>

    <div class="ucm-filter-box">
        <form method="get" style="margin-bottom:0;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
            <input type="text" name="filter_test_id" placeholder="Test ID" value="<?php echo isset($_GET['filter_test_id']) ? esc_attr($_GET['filter_test_id']) : ''; ?>">
            <input type="text" name="filter_name" placeholder="User Name" value="<?php echo isset($_GET['filter_name']) ? esc_attr($_GET['filter_name']) : ''; ?>">
            <input type="text" name="filter_email" placeholder="User Email" value="<?php echo isset($_GET['filter_email']) ? esc_attr($_GET['filter_email']) : ''; ?>">
            <input type="submit" class="button" value="Filter">
        </form>
    </div>

    <div class="ucm-report-section">

    <h2 style="margin-top:0; margin-bottom:18px; color:#2271b1; border-bottom:1px solid #e2e2e2; padding-bottom:8px;">
        Generate Reports (Test Wise / Student Wise)
    </h2>

    <!-- Tabs for switching -->
    <div class="ucm-report-tabs" style="margin-bottom:20px;">
        <button type="button" id="tab-testwise" class="button button-primary">Test Wise</button>
        <button type="button" id="tab-studentwise" class="button">Student Wise</button>
        
    </div>

    <!-- Test Wise Section -->
    <div id="ucm-testwise-report">
        <select id="ucm-test-select">
            <option value="">Select Test</option>
            <?php
            global $wpdb;
            $tests = $wpdb->get_results("SELECT DISTINCT test_id FROM {$wpdb->prefix}ucm_question_answers_results");
            foreach ($tests as $test) {
                echo '<option value="' . esc_attr($test->test_id) . '">Test ID: ' . esc_html($test->test_id) . '</option>';
            }
            ?>
        </select>
        <button id="ucm-download-testfull-excel" class="button" style="margin-left:10px;">Download Excel (Full Detail)</button>
        <div id="ucm-testwise-content" style="margin-top:20px;"></div>
    </div>

    <!-- Student Wise Section -->
    <div id="ucm-studentwise-report" style="display:none;">
        <select id="ucm-student-select">
            <option value="">Select Student</option>
            <?php
            $students = $wpdb->get_results("SELECT DISTINCT email, name FROM {$wpdb->prefix}ucm_question_answers_results");
            foreach ($students as $student) {
                echo '<option value="' . esc_attr($student->email) . '">' . esc_html($student->name) . ' (' . esc_html($student->email) . ')</option>';
            }
            ?>
        </select>
        <div id="ucm-studentwise-content" style="margin-top:20px;"></div>
    </div>

    <!-- Full Detail Section -->
    <div id="ucm-fulldetail-report" style="display:none;">
        <button id="ucm-download-fulldetail" class="button" style="margin-bottom:10px;">Download CSV</button>
        <div id="ucm-fulldetail-content" style="margin-top:20px;">Select to view full detail report.</div>
    </div>

    </div>

    <form method="post">
        <?php
        $list_table->display();
        ?>
    </form>
</div>

<!-- Student Details Modal -->
<div id="ucm-details-modal" style="display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:9999;">
    <div style="background:#fff; max-width:700px; margin:5% auto; padding:20px; position:relative;">
        <span id="ucm-close-details-modal" style="position:absolute; right:10px; top:10px; cursor:pointer;">&times;</span>
        <div id="ucm-details-content"></div>
    </div>
</div>

<!-- Ensure ajaxurl is available -->
<script type="text/javascript">
if (typeof ajaxurl === 'undefined') {
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
}
</script>

<script>
let modalCounts = {correct: 0, incorrect: 0, unchecked: 0};
let currentTestId = null;
let currentEmail = null;

// Modal ke andar status cells count karo
function updateModalCounts() {
    modalCounts = {correct: 0, incorrect: 0, unchecked: 0};
    document.querySelectorAll('#ucm-details-content .status-cell').forEach(function(cell) {
        if (cell.textContent.includes('Correct')) modalCounts.correct++;
        else if (cell.textContent.includes('Incorrect')) modalCounts.incorrect++;
        else modalCounts.unchecked++;
    });
}

document.addEventListener('DOMContentLoaded', function () {

    // View Details Modal
    document.querySelectorAll('.ucm-view-details').forEach(function(btn) {
        btn.addEventListener('click', function() {
            currentTestId = this.getAttribute('data-test-id');
            currentEmail = this.getAttribute('data-email');
            var modal = document.getElementById('ucm-details-modal');
            var content = document.getElementById('ucm-details-content');
            content.innerHTML = 'Loading...';
            modal.style.display = 'block';

            // AJAX request to get student test details
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    content.innerHTML = xhr.responseText;
                    updateModalCounts();
                }
            };
            xhr.send('action=ucm_get_student_test_details&test_id=' + encodeURIComponent(currentTestId) + '&email=' + encodeURIComponent(currentEmail));
        });
    });

    document.getElementById('ucm-close-details-modal').onclick = function() {
        document.getElementById('ucm-details-modal').style.display = 'none';

        // Table row update karo
        if(currentTestId && currentEmail) {
            let row = document.querySelector('tr[data-test-id="' + currentTestId + '"][data-email="' + currentEmail + '"]');
            if(row) {
                let correctCell = row.querySelector('td.column-correct_count');
                let incorrectCell = row.querySelector('td.column-incorrect_count');
                let uncheckedCell = row.querySelector('td.column-unchecked_count');
                if(correctCell) correctCell.textContent = modalCounts.correct;
                if(incorrectCell) incorrectCell.textContent = modalCounts.incorrect;
                if(uncheckedCell) uncheckedCell.textContent = modalCounts.unchecked;
            }
        }
    };

    // Modal ke andar answer mark karne ke liye
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('mark-correct') || e.target.classList.contains('mark-incorrect')) {
            var id = e.target.getAttribute('data-id');
            var status = e.target.classList.contains('mark-correct') ? 1 : 2;
            var xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxurl, true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Update status in div-based UI
                    var qaItem = e.target.closest('.ucm-qa-item');
                    var statusCell = qaItem.querySelector('.status-cell');
                    if (statusCell) {
                        if (status == 1) {
                            statusCell.innerHTML = '<span style="color:green;font-weight:bold;">Correct</span>';
                        } else if (status == 2) {
                            statusCell.innerHTML = '<span style="color:red;font-weight:bold;">Incorrect</span>';
                        } else {
                            statusCell.innerHTML = '<span style="color:gray;">Unchecked</span>';
                        }
                    }
                    updateModalCounts();
                }
            };
            xhr.send('action=ucm_mark_answer&id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status));
        }
    });

});


document.addEventListener('DOMContentLoaded', function () {
    // Tabs
    document.getElementById('tab-testwise').onclick = function() {
        this.classList.add('button-primary');
        document.getElementById('tab-studentwise').classList.remove('button-primary');
        document.getElementById('ucm-testwise-report').style.display = 'block';
        document.getElementById('ucm-studentwise-report').style.display = 'none';
    };
    document.getElementById('tab-studentwise').onclick = function() {
        this.classList.add('button-primary');
        document.getElementById('tab-testwise').classList.remove('button-primary');
        document.getElementById('ucm-testwise-report').style.display = 'none';
        document.getElementById('ucm-studentwise-report').style.display = 'block';
    };

    // Test Wise AJAX
    document.getElementById('ucm-test-select').onchange = function() {
        var testId = this.value;
        var content = document.getElementById('ucm-testwise-content');
        if (!testId) { content.innerHTML = ''; return; }
        content.innerHTML = 'Loading...';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl, true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) content.innerHTML = xhr.responseText;
        };
        xhr.send('action=ucm_report_testwise&test_id=' + encodeURIComponent(testId));
    };

    // Student Wise AJAX
    document.getElementById('ucm-student-select').onchange = function() {
        var email = this.value;
        var content = document.getElementById('ucm-studentwise-content');
        if (!email) { content.innerHTML = ''; return; }
        content.innerHTML = 'Loading...';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', ajaxurl, true);
        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
        xhr.onload = function () {
            if (xhr.status === 200) content.innerHTML = xhr.responseText;
        };
        xhr.send('action=ucm_report_studentwise&email=' + encodeURIComponent(email));
    };

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'ucm-download-testwise') {
            var table = document.getElementById('ucm-testwise-table');
            if (!table) return;
            var csv = [];
            for (var i = 0; i < table.rows.length; i++) {
                var row = [], cols = table.rows[i].cells;
                for (var j = 0; j < cols.length; j++)
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                csv.push(row.join(','));
            }
            var csvFile = new Blob([csv.join('\n')], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            var testId = document.getElementById('ucm-test-select').value;
            downloadLink.download = "testwise-report-" + testId + ".csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'ucm-download-studentwise') {
            var table = document.getElementById('ucm-studentwise-table');
            if (!table) return;
            var csv = [];
            for (var i = 0; i < table.rows.length; i++) {
                var row = [], cols = table.rows[i].cells;
                for (var j = 0; j < cols.length; j++)
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                csv.push(row.join(','));
            }
            // Get student name and test id for filename
            var studentSelect = document.getElementById('ucm-student-select');
            var studentName = studentSelect.options[studentSelect.selectedIndex].text.split(' (')[0].replace(/\s+/g, '-').toLowerCase();
            var testIds = [];
            for (var i = 1; i < table.rows.length; i++) { // skip header
                var tid = table.rows[i].cells[0].innerText;
                if (testIds.indexOf(tid) === -1) testIds.push(tid);
            }
            var testIdPart = testIds.length === 1 ? testIds[0] : 'multiple';
            var csvFile = new Blob([csv.join('\n')], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = studentName + "-test-" + testIdPart + ".csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'ucm-download-testfull-excel') {
            var testId = document.getElementById('ucm-test-select').value;
            if (!testId) {
                alert('Please select a test first!');
                return;
            }
            window.location.href = ajaxurl + '?action=ucm_download_test_full_detail_excel&test_id=' + encodeURIComponent(testId);
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.id === 'ucm-download-fulldetail') {
            var table = document.getElementById('ucm-fulldetail-table');
            if (!table) return;
            var csv = [];
            for (var i = 0; i < table.rows.length; i++) {
                var row = [], cols = table.rows[i].cells;
                for (var j = 0; j < cols.length; j++)
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                csv.push(row.join(','));
            }
            var csvFile = new Blob([csv.join('\n')], {type: "text/csv"});
            var downloadLink = document.createElement("a");
            downloadLink.download = "full-detail-report.csv";
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('ucm-clear-report')) {
            // Test wise
            var testwise = document.getElementById('ucm-testwise-content');
            if (testwise && testwise.contains(e.target)) {
                testwise.innerHTML = '';
                document.getElementById('ucm-test-select').value = '';
            }
            // Student wise
            var studentwise = document.getElementById('ucm-studentwise-content');
            if (studentwise && studentwise.contains(e.target)) {
                studentwise.innerHTML = '';
                document.getElementById('ucm-student-select').value = '';
            }
        }
    });
});

</script>

<style>
#ucm-details-modal .modal-content,
#ucm-details-modal > div {
    max-height: 80vh;
    overflow-y: auto;
}

.ucm-qa-list {
    margin: 0;
    padding: 0;
    max-height: 65vh;
    overflow-y: auto;
}
.ucm-qa-item {
    margin-bottom: 18px;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
    background: #fafbfc;
    border-radius: 4px;
    padding: 12px 10px;
}
.ucm-qa-question {
    font-weight: bold;
    color: #222;
    margin-bottom: 4px;
}
.ucm-qa-answer {
    margin: 0 0 8px 0;
    color: #444;
    font-style: italic;
}
.ucm-qa-status-action {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 2px;
}
.status-cell {
    min-width: 80px;
    display: inline-block;
}
.ucm-modal-heading {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 18px;
    color: #1a1a1a;
}
.ucm-report-tabs .button-primary {
    background: #2271b1;
    color: #fff;
}
.ucm-filter-box {
    background: #f8f9fa;
    border: 1px solid #e2e2e2;
    padding: 18px 16px 10px 16px;
    margin-bottom: 18px;
    border-radius: 6px;
}
.ucm-report-section {
    background: #fff;
    border: 1px solid #d1d5db;
    padding: 18px 16px;
    border-radius: 6px;
    margin-bottom: 24px;
}
.ucm-report-tabs {
    margin-bottom: 18px;
}
.ucm-report-section h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 8px;
}
.ucm-clear-report {
    float: right;
    margin-left: 10px;
}
</style>