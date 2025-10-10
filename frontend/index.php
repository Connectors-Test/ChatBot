<?php
// Prevent caching to avoid redirect loops
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include centralized session configuration
require_once __DIR__ . '/includes/session_config.php';

$API_BASE = getenv('RENDER') ? "https://chatbot-backend-mxra.onrender.com" : "http://localhost:5001";

// Debug: Log session status
debugSession("Index page");

// Validate session (only destroys expired sessions, not sessions without usernames)
validateSession();

// This page requires authentication - redirect to login if not authenticated
requireAuth('login.php');

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spreadsheet Chatbot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/styles.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Header with Logo and Title -->
    <div class="container-fluid py-3 bg-green text-gray">
        <div class="d-flex align-items-center">
            <img src="public/images/logo.png" alt="Logo" class="me-3 ms-3" style="height: 50px;">
            
            <h3 class="mb-0">AI Chatbot Dashboard</h3>
            
        </div>
        <div style="border-bottom: 2px solid #28a745; width: 100%;"></div>
    </div>

    <div class="container py-5">
        <!-- LOGGED IN INTERFACE -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
        <a href="?logout=1" class="btn btn-danger">Logout</a>
    </div>

    <!-- Invisible Button for Click Tracking -->
    <div id="invisibleButton" style="position: absolute; top: 10px; right: 10px; width: 50px; height: 50px; background: transparent; cursor: pointer; z-index: 1000;"></div>

    <div class="d-flex align-items-center mb-3">
        <button class="btn btn-info" onclick="loadSavedChatbots()">Preview Saved Chatbots</button>
        <button class="btn btn-success ms-2" id="shareBtn" onclick="openShareModal()" style="display:none;">Share</button>
    </div>
    <div id="savedList" class="mb-3"></div>

    <!-- Chatbot Configuration Form -->
    <form id="configForm">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="chatbot_name">Chatbot Name</label>
                <input type="text" class="form-control" id="chatbot_name" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="chatbot_id">Chatbot ID</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="chatbot_id" readonly>
                    <button type="button" class="btn btn-secondary" onclick="generateID()">Generate ID</button>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="gemini_api_key">Gemini API Key</label>
            <input type="text" class="form-control" id="gemini_api_key" required>
        </div>

        <div class="mb-3">
            <label for="gemini_model">Gemini Model</label>
            <input type="text" class="form-control" id="gemini_model" value="gemini-2.0-flash" required>
        </div>

        <div class="mb-3">
            <label for="data_source">Data Source</label>
            <select class="form-select" id="data_source" onchange="onDataSourceChange()">
                <option value="google_sheets" selected>Google Sheets</option>
                <option value="mysql">MySQL</option>
                <option value="postgresql">PostgreSQL</option>
                <option value="mssql">MS SQL</option>
            <option value="neo4j">Neo4j</option>
            <option value="mongodb">MongoDB</option>
            <option value="oracle">Oracle</option>
            <option value="airtable">Airtable</option>
            <option value="databricks">Databricks</option>
                <option value="supabase">Supabase</option>
                <option value="snowflake">Snowflake</option>
                <option value="odoo">Odoo</option>
        </select>
    </div>

    <!-- Dynamic Fields Based on Data Source -->
    <?php include_once __DIR__ . '/includes/googleSheets_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/db_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/mssql_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/neo4j_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/mongodb_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/oracle_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/airtable_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/databricks_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/supabase_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/snowflake_fields.php'; ?>
    <?php include_once __DIR__ . '/includes/odoo_fields.php'; ?>

        <button type="button" class="btn btn-primary mb-3" onclick="connectSpreadsheet()">Connect</button>
    </form>

    <!-- Sheet/Table Selection Form -->
    <form id="selectionForm">
        <div id="sheetSelection" class="mt-3"></div>
        <button type="button" class="btn btn-success mt-2" id="loadChatBtn" style="display:none;" onclick="loadChat()">Load to Chat</button>
    </form>

    <div id="chatInterface" class="mt-3" style="display:none;">
        <h4>Chat Interface</h4>
        <div id="chat"></div>
        <div class="input-group mt-2">
            <div class="position-relative flex-grow-1">
                <input type="text" id="user_input" class="form-control" placeholder="Ask about your data...">
                <button class="btn position-absolute top-50 end-0 translate-middle-y me-2" id="micBtn" type="button" style="z-index: 5;">🎙️</button>
            </div>
            <button class="btn btn-success" onclick="sendMessage()">Send</button>
        </div>
        <button class="btn btn-success mt-3" onclick="saveChatbot()">Save Chatbot</button>
    </div>

    <!-- Modal for JotForm -->
    <div class="modal fade" id="jotformModal" tabindex="-1" aria-labelledby="jotformModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jotformModalLabel">Access Required</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <iframe src="https://form.jotform.com/252633118963460" width="100%" height="600" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Share Customization -->
    <div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareModalLabel">Customize Chatbot Design</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="customizeForm">
                        <div class="mb-3">
                            <label for="company_logo_upload">Upload Company Logo</label>
                            <input type="file" class="form-control" id="company_logo_upload" accept="image/*">
                            <input type="hidden" id="company_logo">
                            <div id="logo_preview" style="margin-top: 10px;"></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nav_color">Navigation Color</label>
                                <input type="color" class="form-control" id="nav_color" value="#007bff">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="text_color">Text Color</label>
                                <input type="color" class="form-control" id="text_color" value="#000000">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="content_bg_color">Content Area Color</label>
                                <input type="color" class="form-control" id="content_bg_color" value="#ffffff">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="textarea_color">Text Area Color</label>
                                <input type="color" class="form-control" id="textarea_color" value="#ffffff">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="textarea_border_color">Text Area Border Color</label>
                                <input type="color" class="form-control" id="textarea_border_color" value="#cccccc">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="textarea_border_thickness">Text Area Border Thickness</label>
                                <input type="text" class="form-control" id="textarea_border_thickness" value="1px">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="button_color">Button Color</label>
                                <input type="color" class="form-control" id="button_color" value="#007bff">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="button_text_color">Button Text Color</label>
                                <input type="color" class="form-control" id="button_text_color" value="#ffffff">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="border_color">Border Color</label>
                                <input type="color" class="form-control" id="border_color" value="#007bff">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="border_thickness">Border Thickness</label>
                                <input type="text" class="form-control" id="border_thickness" value="2px">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info" onclick="previewChatbot()">Preview</button>
                    <button type="button" class="btn btn-success" onclick="shareChatbot()">Share</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Iframe Code -->
    <div class="modal fade" id="iframeModal" tabindex="-1" aria-labelledby="iframeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="iframeModalLabel">Embed Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <textarea id="iframeCode" class="form-control" rows="5" readonly></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="copyIframeCode()">Copy Code</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_BASE = "<?= $API_BASE ?>";

let currentChatbot = null;

// Variables for click tracking and unlock status
let clickCount = parseInt(localStorage.getItem('invisibleButtonClicks')) || 0;
let isUnlocked = localStorage.getItem('unlocked') === 'true';

// Function to handle invisible button clicks
document.getElementById('invisibleButton').addEventListener('click', function() {
    clickCount++;
    localStorage.setItem('invisibleButtonClicks', clickCount);
    if (clickCount >= 15 && !isUnlocked) {
        isUnlocked = true;
        localStorage.setItem('unlocked', 'true');
        alert('Unlocked! You now have access to all datasources and can save multiple chatbots.');
    }
});

// Clear localStorage on logout
document.querySelector('a[href="?logout=1"]').addEventListener('click', function() {
    localStorage.removeItem('invisibleButtonClicks');
    localStorage.removeItem('unlocked');
});

// Function to check if user is unlocked
function checkUnlocked() {
    console.log('checkUnlocked: isUnlocked =', isUnlocked);
    return isUnlocked;
}

    // Modified onDataSourceChange to show modal if not unlocked
    function onDataSourceChange() {
        const dataSource = document.getElementById('data_source').value;
        const googleFields = document.getElementById('googleSheetsFields');
        const dbFields = document.getElementById('dbFields');
        const mssqlFields = document.getElementById('mssqlFields');
        const neo4jFields = document.getElementById('neo4jFields');
        const mongodbFields = document.getElementById('mongodbFields');
        const oracleFields = document.getElementById('oracleFields');
        const airtableFields = document.getElementById('airtableFields');
        const databricksFields = document.getElementById('databricksFields');
        const supabaseFields = document.getElementById('supabaseFields');
        const snowflakeFields = document.getElementById('snowflakeFields');
        const odooFields = document.getElementById('odooFields');
        if (dataSource === 'google_sheets') {
            googleFields.style.display = 'block';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'mssql') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'block';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'neo4j') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'block';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'mongodb') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'block';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'oracle') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'block';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'airtable') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'block';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'databricks') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'block';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'supabase') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'block';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
        } else if (dataSource === 'snowflake') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'block';
            odooFields.style.display = 'none';
        } else if (dataSource === 'odoo') {
            googleFields.style.display = 'none';
            dbFields.style.display = 'none';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'block';
        } else {
            googleFields.style.display = 'none';
            dbFields.style.display = 'block';
            mssqlFields.style.display = 'none';
            neo4jFields.style.display = 'none';
            mongodbFields.style.display = 'none';
            oracleFields.style.display = 'none';
            airtableFields.style.display = 'none';
            databricksFields.style.display = 'none';
            supabaseFields.style.display = 'none';
            snowflakeFields.style.display = 'none';
            odooFields.style.display = 'none';
            if (dataSource === 'mysql') {
                document.getElementById('db_port').value = '3306';
            } else if (dataSource === 'postgresql') {
                document.getElementById('db_port').value = '5432';
            }
        }

        // Show modal if not unlocked and not Google Sheets
        console.log('onDataSourceChange: dataSource =', dataSource, 'isUnlocked =', checkUnlocked());
        if (!checkUnlocked() && dataSource !== 'google_sheets') {
            console.log('Showing modal for datasource selection');
            const modal = new bootstrap.Modal(document.getElementById('jotformModal'));
            modal.show();
        }
    }

    // Add event listener for modal close to rollback datasource to google_sheets
    const jotformModal = document.getElementById('jotformModal');
    jotformModal.addEventListener('hidden.bs.modal', function () {
        const dataSourceSelect = document.getElementById('data_source');
        if (dataSourceSelect.value !== 'google_sheets') {
            dataSourceSelect.value = 'google_sheets';
            onDataSourceChange();
        }
    });

// Modified saveChatbot to check count and show modal if needed
async function saveChatbot() {
    // Validation: Ensure chatbot_id is generated
    if (!document.getElementById('chatbot_id').value) {
        alert("Please generate a Chatbot ID first.");
        return;
    }

    const dataSource = document.getElementById('data_source').value;
    const itemName = dataSource === 'google_sheets' ? 'sheet_names' : 'table_names';
    const selectedItems = Array.from(document.querySelectorAll(`input[name="${itemName}"]:checked`)).map(el=>el.value);
    const username = "<?= $_SESSION['username'] ?>";

    // Check chatbot count if using Google Sheets and not unlocked
    if (dataSource === 'google_sheets' && !checkUnlocked()) {
        console.log('Checking chatbot count for user:', username);
        const res = await fetch(`${API_BASE}/check_chatbot_count?username=${encodeURIComponent(username)}`);
        const data = await res.json();
        console.log('Chatbot count response:', data);
        if (data.count >= 1) {
            console.log('Showing modal for chatbot saving restriction');
            const modal = new bootstrap.Modal(document.getElementById('jotformModal'));
            modal.show();
            return;
        }
    }

    const formData = new URLSearchParams({
        username: username,
        chatbot_name: document.getElementById('chatbot_name').value,
        chatbot_id: document.getElementById('chatbot_id').value,
        gemini_api_key: document.getElementById('gemini_api_key').value,
        gemini_model: document.getElementById('gemini_model').value,
        data_source: dataSource
    });

    if (dataSource === 'google_sheets') {
        formData.append('sheet_id', document.getElementById('sheet_id').value);
        formData.append('service_account_json', document.getElementById('service_account_json').value);
    } else if (dataSource === 'neo4j') {
        formData.append('neo4j_uri', document.getElementById('neo4j_uri').value);
        formData.append('neo4j_db_name', document.getElementById('neo4j_db_name').value);
        formData.append('neo4j_username', document.getElementById('neo4j_username').value);
        formData.append('neo4j_password', document.getElementById('neo4j_password').value);
    } else if (dataSource === 'mongodb') {
        formData.append('mongo_uri', document.getElementById('mongo_uri').value);
        formData.append('mongo_db_name', document.getElementById('mongo_db_name').value);
    } else if (dataSource === 'mssql') {
        formData.append('db_host', document.getElementById('mssql_host').value);
        formData.append('db_port', document.getElementById('mssql_port').value);
        formData.append('db_name', document.getElementById('mssql_name').value);
        formData.append('db_username', document.getElementById('mssql_username').value);
        formData.append('db_password', document.getElementById('mssql_password').value);
    } else if (dataSource === 'oracle') {
        formData.append('oracle_version', document.getElementById('oracle_version').value);
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_name', document.getElementById('db_name').value);
        formData.append('db_username', document.getElementById('db_username').value);
        formData.append('db_password', document.getElementById('db_password').value);
    } else if (dataSource === 'airtable') {
        formData.append('airtable_api_key', document.getElementById('airtable_api_key').value);
        formData.append('airtable_base_id', document.getElementById('airtable_base_id').value);
    } else if (dataSource === 'databricks') {
        formData.append('databricks_hostname', document.getElementById('databricks_hostname').value);
        formData.append('databricks_http_path', document.getElementById('databricks_http_path').value);
        formData.append('databricks_token', document.getElementById('databricks_token').value);
    } else if (dataSource === 'supabase') {
        formData.append('supabase_url', document.getElementById('supabase_url').value);
        formData.append('supabase_anon_key', document.getElementById('supabase_anon_key').value);
    } else if (dataSource === 'snowflake') {
        formData.append('snowflake_account', document.getElementById('snowflake_account').value);
        formData.append('snowflake_user', document.getElementById('snowflake_user').value);
        formData.append('snowflake_password', document.getElementById('snowflake_password').value);
        formData.append('snowflake_warehouse', document.getElementById('snowflake_warehouse').value);
        formData.append('snowflake_database', document.getElementById('snowflake_database').value);
        formData.append('snowflake_schema', document.getElementById('snowflake_schema').value);
        formData.append('snowflake_role', document.getElementById('snowflake_role').value);
    } else if (dataSource === 'odoo') {
        formData.append('odoo_url', document.getElementById('odoo_url').value);
        formData.append('odoo_db', document.getElementById('odoo_db').value);
        formData.append('odoo_username', document.getElementById('odoo_username').value);
        formData.append('odoo_password', document.getElementById('odoo_password').value);
        formData.append('selected_module', document.getElementById('selected_module').value);
    } else {
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_port', document.getElementById('db_port').value);
        formData.append('db_name', document.getElementById('db_name').value);
        formData.append('db_username', document.getElementById('db_username').value);
        formData.append('db_password', document.getElementById('db_password').value);
    }

    selectedItems.forEach(s => formData.append('selected_items', s));

    // Logging: Print data being sent
    console.log('Saving chatbot with data:', Object.fromEntries(formData));

    const res = await fetch(`${API_BASE}/save_chatbot`, { method:'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body:formData });
    if(res.ok) {
        alert("Chatbot saved!");
    } else {
        // Error handling: Alert user if save fails
        const error = await res.json();
        alert("Failed to save chatbot: " + (error.message || "Unknown error"));
    }
}

function generateID() {
    document.getElementById('chatbot_id').value = 'cb-' + Math.random().toString(36).substring(2,10);
}

// Connect and list items
async function connectSpreadsheet() {
    const dataSource = document.getElementById('data_source').value;
    const data = new URLSearchParams({
        username:"<?= $_SESSION['username'] ?>",
        chatbot_name: document.getElementById('chatbot_name').value,
        chatbot_id: document.getElementById('chatbot_id').value,
        gemini_api_key: document.getElementById('gemini_api_key').value,
        gemini_model: document.getElementById('gemini_model').value,
        data_source: dataSource
    });

    if (dataSource === 'google_sheets') {
        data.append('sheet_id', document.getElementById('sheet_id').value);
        data.append('service_account_json', document.getElementById('service_account_json').value);
    } else if (dataSource === 'neo4j') {
        data.append('neo4j_uri', document.getElementById('neo4j_uri').value);
        data.append('neo4j_db_name', document.getElementById('neo4j_db_name').value);
        data.append('neo4j_username', document.getElementById('neo4j_username').value);
        data.append('neo4j_password', document.getElementById('neo4j_password').value);
    } else if (dataSource === 'mssql') {
        data.append('db_host', document.getElementById('mssql_host').value);
        data.append('db_port', document.getElementById('mssql_port').value);
        data.append('db_name', document.getElementById('mssql_name').value);
        data.append('db_username', document.getElementById('mssql_username').value);
        data.append('db_password', document.getElementById('mssql_password').value);
    } else if (dataSource === 'mongodb') {
        data.append('mongo_uri', document.getElementById('mongo_uri').value);
        data.append('mongo_db_name', document.getElementById('mongo_db_name').value);
    } else if (dataSource === 'airtable') {
        data.append('airtable_api_key', document.getElementById('airtable_api_key').value);
        data.append('airtable_base_id', document.getElementById('airtable_base_id').value);
    } else if (dataSource === 'databricks') {
        data.append('databricks_hostname', document.getElementById('databricks_hostname').value);
        data.append('databricks_http_path', document.getElementById('databricks_http_path').value);
        data.append('databricks_token', document.getElementById('databricks_token').value);
    } else if (dataSource === 'supabase') {
        data.append('supabase_url', document.getElementById('supabase_url').value);
        data.append('supabase_anon_key', document.getElementById('supabase_anon_key').value);
    } else if (dataSource === 'snowflake') {
        data.append('snowflake_account', document.getElementById('snowflake_account').value);
        data.append('snowflake_user', document.getElementById('snowflake_user').value);
        data.append('snowflake_password', document.getElementById('snowflake_password').value);
        data.append('snowflake_warehouse', document.getElementById('snowflake_warehouse').value);
        data.append('snowflake_database', document.getElementById('snowflake_database').value);
        data.append('snowflake_schema', document.getElementById('snowflake_schema').value);
        data.append('snowflake_role', document.getElementById('snowflake_role').value);
    } else if (dataSource === 'odoo') {
        data.append('odoo_url', document.getElementById('odoo_url').value);
        data.append('odoo_db', document.getElementById('odoo_db').value);
        data.append('odoo_username', document.getElementById('odoo_username').value);
        data.append('odoo_password', document.getElementById('odoo_password').value);
        data.append('selected_module', document.getElementById('selected_module').value);
    } else {
        data.append('db_host', document.getElementById('db_host').value);
        data.append('db_port', document.getElementById('db_port').value);
        data.append('db_name', document.getElementById('db_name').value);
        data.append('db_username', document.getElementById('db_username').value);
        data.append('db_password', document.getElementById('db_password').value);
    }

    const res = await fetch(`${API_BASE}/set_credentials`, { method:'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body:data });
    if(!res.ok) {
        const error = await res.json();
        return alert("Failed to connect: " + (error.error || "Unknown error"));
    }

    const json = await res.json();
    const container = document.getElementById('sheetSelection');
    container.innerHTML = "";

    const itemType = json.type;
    const itemName = itemType === 'sheets' ? 'sheet_names' : 'table_names';

    json.items.forEach(name => {
        const div = document.createElement('div');
        div.innerHTML = `<input type="checkbox" name="${itemName}" value="${name}"> ${name}`;
        container.appendChild(div);
    });

    document.getElementById('loadChatBtn').style.display = 'inline-block';
}

// Load selected items to chat
function loadChat() {
    const dataSource = document.getElementById('data_source').value;
    const itemName = dataSource === 'google_sheets' ? 'sheet_names' : 'table_names';
    const selectedItems = Array.from(document.querySelectorAll(`input[name="${itemName}"]:checked`))
                                .map(el=>el.value);
    if(selectedItems.length === 0) {
        const itemType = dataSource === 'google_sheets' ? 'sheet' : 'table';
        return alert(`Select at least one ${itemType}`);
    }

    const data = new URLSearchParams();
    selectedItems.forEach(s => data.append('item_names', s));

    fetch(`${API_BASE}/set_items`, { method:'POST', body:data })
        .then(res => res.json())
        .then(json => {
            document.getElementById('chatInterface').style.display='block';
            const itemType = dataSource === 'google_sheets' ? 'Sheets' : 'Tables';
            alert(`${itemType} loaded! You can now chat.`);
        });
}

// Send message to chat
async function sendMessage() {
    const input = document.getElementById('user_input').value;
    if(!input) return;
    const chatDiv = document.getElementById('chat');
    chatDiv.innerHTML += `<p class="user"><b>You:</b> ${input}</p>`;

    const res = await fetch(`${API_BASE}/chat`, {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({message: input})
    });
    const data = await res.json();

    // Check if response contains HTML tags (simple check)
    const isHTML = /<\/?[a-z][\s\S]*>/i.test(data.response);

    if (isHTML) {
        // Insert raw HTML inside a div container
        chatDiv.innerHTML += `<div class="bot">${data.response}</div>`;
    } else {
        // Insert as plain text inside a paragraph
        chatDiv.innerHTML += `<p class="bot"><b>Bot:</b> ${data.response}</p>`;
    }

    chatDiv.scrollTop = chatDiv.scrollHeight;
    document.getElementById('user_input').value = '';
}

async function loadSavedChatbots() {
    const listDiv = document.getElementById('savedList');
    const username = "<?= $_SESSION['username'] ?>";
    const res = await fetch(`${API_BASE}/list_chatbots?username=${encodeURIComponent(username)}`);
    const data = await res.json();
    listDiv.innerHTML = '';
    data.forEach(cb=>{
        const div = document.createElement('div');
        div.className = 'savedItem';
        div.textContent = cb.chatbot_name + ' (' + cb.id + ')';
        div.onclick = ()=>{
            fillForm(cb);
            listDiv.style.display = 'none'; // Auto close dropdown on selection
            document.removeEventListener('click', outsideClickListener);
        };
        listDiv.appendChild(div);
    });
    // Position the dropdown below the button
    const button = document.querySelector('button.btn-info');
    const rect = button.getBoundingClientRect();
    listDiv.style.position = 'absolute';
    listDiv.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    listDiv.style.left = (rect.left + window.scrollX) + 'px';
    listDiv.style.width = Math.max(rect.width, 300) + 'px';

    listDiv.style.display = 'block';

    // Add click outside listener to close dropdown
    setTimeout(() => {
        document.addEventListener('click', outsideClickListener);
    }, 0);

    function outsideClickListener(event) {
        if (!listDiv.contains(event.target) && event.target !== button) {
            listDiv.style.display = 'none';
            document.removeEventListener('click', outsideClickListener);
        }
    }
}

function fillForm(cb){
    document.getElementById('chatbot_name').value = cb.chatbot_name;
    document.getElementById('chatbot_id').value = cb.id;
    document.getElementById('gemini_api_key').value = cb.gemini_api_key;
    document.getElementById('gemini_model').value = cb.gemini_model;
    document.getElementById('data_source').value = cb.data_source || 'google_sheets';
    onDataSourceChange(); // Update fields visibility

    if (cb.data_source === 'google_sheets') {
        document.getElementById('sheet_id').value = cb.sheet_id;
        document.getElementById('service_account_json').value = cb.service_account_json;
    } else if (cb.data_source === 'neo4j') {
        document.getElementById('neo4j_uri').value = cb.db_host;
        document.getElementById('neo4j_db_name').value = cb.db_name;
        document.getElementById('neo4j_username').value = cb.db_username;
        document.getElementById('neo4j_password').value = cb.db_password;
    } else if (cb.data_source === 'mongodb') {
        document.getElementById('mongo_uri').value = cb.mongo_uri;
        document.getElementById('mongo_db_name').value = cb.mongo_db_name;
    } else if (cb.data_source === 'mssql') {
        document.getElementById('mssql_host').value = cb.db_host;
        document.getElementById('mssql_port').value = cb.db_port;
        document.getElementById('mssql_name').value = cb.db_name;
        document.getElementById('mssql_username').value = cb.db_username;
        document.getElementById('mssql_password').value = cb.db_password;
    } else if (cb.data_source === 'oracle') {
        document.getElementById('oracle_version').value = cb.oracle_version || '19';
        document.getElementById('db_host').value = cb.db_host;
        document.getElementById('db_port').value = cb.db_port;
        document.getElementById('db_name').value = cb.db_name;
        document.getElementById('db_username').value = cb.db_username;
        document.getElementById('db_password').value = cb.db_password;
    } else if (cb.data_source === 'airtable') {
        document.getElementById('airtable_api_key').value = cb.airtable_api_key;
        document.getElementById('airtable_base_id').value = cb.airtable_base_id;
    } else if (cb.data_source === 'databricks') {
        document.getElementById('databricks_hostname').value = cb.databricks_hostname;
        document.getElementById('databricks_http_path').value = cb.databricks_http_path;
        document.getElementById('databricks_token').value = cb.databricks_token;
    } else if (cb.data_source === 'supabase') {
        document.getElementById('supabase_url').value = cb.supabase_url;
        document.getElementById('supabase_anon_key').value = cb.supabase_anon_key;
    } else if (cb.data_source === 'snowflake') {
        document.getElementById('snowflake_account').value = cb.snowflake_account;
        document.getElementById('snowflake_user').value = cb.snowflake_user;
        document.getElementById('snowflake_password').value = cb.snowflake_password;
        document.getElementById('snowflake_warehouse').value = cb.snowflake_warehouse;
        document.getElementById('snowflake_database').value = cb.snowflake_database;
        document.getElementById('snowflake_schema').value = cb.snowflake_schema;
        document.getElementById('snowflake_role').value = cb.snowflake_role;
    } else if (cb.data_source === 'odoo') {
        document.getElementById('odoo_url').value = cb.odoo_url;
        document.getElementById('odoo_db').value = cb.odoo_db;
        document.getElementById('odoo_username').value = cb.odoo_username;
        document.getElementById('odoo_password').value = cb.odoo_password;
        document.getElementById('selected_module').value = cb.selected_module;
    } else {
        document.getElementById('db_host').value = cb.db_host;
        document.getElementById('db_port').value = cb.db_port;
        document.getElementById('db_name').value = cb.db_name;
        document.getElementById('db_username').value = cb.db_username;
        document.getElementById('db_password').value = cb.db_password;
    }

    // Fill styling fields for share modal
    if (cb.styles) {
        const styles = JSON.parse(cb.styles);
        document.getElementById('company_logo').value = styles.company_logo || '';
        if (styles.company_logo) {
            document.getElementById('logo_preview').innerHTML = `<img src="${styles.company_logo}" style="max-width: 100px; max-height: 100px;">`;
        } else {
            document.getElementById('logo_preview').innerHTML = '';
        }
        document.getElementById('nav_color').value = styles.nav_color || '#007bff';
        document.getElementById('text_color').value = styles.text_color || '#000000';
        document.getElementById('content_bg_color').value = styles.content_bg_color || '#ffffff';
        document.getElementById('textarea_color').value = styles.textarea_color || '#ffffff';
        document.getElementById('textarea_border_color').value = styles.textarea_border_color || '#cccccc';
        document.getElementById('textarea_border_thickness').value = styles.textarea_border_thickness || '1px';
        document.getElementById('button_color').value = styles.button_color || '#007bff';
        document.getElementById('button_text_color').value = styles.button_text_color || '#ffffff';
        document.getElementById('border_color').value = styles.border_color || '#007bff';
        document.getElementById('border_thickness').value = styles.border_thickness || '2px';
    } else {
        // Reset to defaults if no styles
        document.getElementById('company_logo').value = '';
        document.getElementById('logo_preview').innerHTML = '';
        document.getElementById('nav_color').value = '#007bff';
        document.getElementById('text_color').value = '#000000';
        document.getElementById('content_bg_color').value = '#ffffff';
        document.getElementById('textarea_color').value = '#ffffff';
        document.getElementById('textarea_border_color').value = '#cccccc';
        document.getElementById('textarea_border_thickness').value = '1px';
        document.getElementById('button_color').value = '#007bff';
        document.getElementById('button_text_color').value = '#ffffff';
        document.getElementById('border_color').value = '#007bff';
        document.getElementById('border_thickness').value = '2px';
    }

    const selectedItems = JSON.parse(cb.selected_sheets || cb.selected_tables || "[]");
    const itemName = cb.data_source === 'google_sheets' ? 'sheet_names' : 'table_names';
    const container = document.getElementById('sheetSelection');
    container.querySelectorAll(`input[name="${itemName}"]`).forEach(input=>{
        input.checked = selectedItems.includes(input.value);
    });

    currentChatbot = cb;
    document.getElementById('shareBtn').style.display = 'inline-block';
}

function openShareModal() {
    if (!currentChatbot) {
        alert("Please select a chatbot first.");
        return;
    }
    const modal = new bootstrap.Modal(document.getElementById('shareModal'));
    modal.show();
}

async function previewChatbot() {
    try {
        await saveStyles();
        if (!currentChatbot.share_key) {
            alert("Share key not found. Please save the chatbot styles first.");
            return;
        }
        window.open(`${API_BASE}/shared/${currentChatbot.share_key}`, '_blank');
    } catch (error) {
        alert("Error during preview: " + error.message);
    }
}

async function shareChatbot() {
    try {
        await saveStyles();
        if (!currentChatbot.share_key) {
            alert("Share key not found. Please save the chatbot styles first.");
            return;
        }
        const iframeCode = `<iframe src="${API_BASE}/shared/${currentChatbot.share_key}" width="400" height="600" frameborder="0"></iframe>`;
        document.getElementById('iframeCode').value = iframeCode;
        const modal = new bootstrap.Modal(document.getElementById('iframeModal'));
        modal.show();
    } catch (error) {
        alert("Error during share: " + error.message);
    }
}

function copyIframeCode() {
    const textarea = document.getElementById('iframeCode');
    textarea.select();
    document.execCommand('copy');
    alert('Copied to clipboard!');
}

async function saveStyles() {
    if (!currentChatbot) {
        alert("No chatbot selected to save.");
        throw new Error("No chatbot selected");
    }
    const formData = new URLSearchParams({
        username: "<?= $_SESSION['username'] ?>",
        chatbot_id: currentChatbot.id,
        chatbot_name: currentChatbot.chatbot_name,
        gemini_api_key: currentChatbot.gemini_api_key,
        gemini_model: currentChatbot.gemini_model,
        data_source: currentChatbot.data_source,
        sheet_id: currentChatbot.sheet_id || '',
        service_account_json: currentChatbot.service_account_json || '',
        db_host: currentChatbot.db_host || '',
        db_port: currentChatbot.db_port || '',
        db_name: currentChatbot.db_name || '',
        db_username: currentChatbot.db_username || '',
        db_password: currentChatbot.db_password || '',
        selected_items: currentChatbot.selected_items || '',
        mongo_uri: currentChatbot.mongo_uri || '',
        mongo_db_name: currentChatbot.mongo_db_name || '',
        selected_collections: currentChatbot.selected_collections || '',
        airtable_api_key: currentChatbot.airtable_api_key || '',
        airtable_base_id: currentChatbot.airtable_base_id || '',
        databricks_hostname: currentChatbot.databricks_hostname || '',
        databricks_http_path: currentChatbot.databricks_http_path || '',
        databricks_token: currentChatbot.databricks_token || '',
        supabase_url: currentChatbot.supabase_url || '',
        supabase_anon_key: currentChatbot.supabase_anon_key || '',
        snowflake_account: currentChatbot.snowflake_account || '',
        snowflake_user: currentChatbot.snowflake_user || '',
        snowflake_password: currentChatbot.snowflake_password || '',
        snowflake_warehouse: currentChatbot.snowflake_warehouse || '',
        snowflake_database: currentChatbot.snowflake_database || '',
        snowflake_schema: currentChatbot.snowflake_schema || '',
        snowflake_role: currentChatbot.snowflake_role || '',
        odoo_url: currentChatbot.odoo_url || '',
        odoo_db: currentChatbot.odoo_db || '',
        odoo_username: currentChatbot.odoo_username || '',
        odoo_password: currentChatbot.odoo_password || '',
        selected_module: currentChatbot.selected_module || '',
        share_key: currentChatbot.share_key || '',
        company_logo: document.getElementById('company_logo').value,
        nav_color: document.getElementById('nav_color').value,
        text_color: document.getElementById('text_color').value,
        content_bg_color: document.getElementById('content_bg_color').value,
        textarea_color: document.getElementById('textarea_color').value,
        textarea_border_color: document.getElementById('textarea_border_color').value,
        textarea_border_thickness: document.getElementById('textarea_border_thickness').value,
        button_color: document.getElementById('button_color').value,
        button_text_color: document.getElementById('button_text_color').value,
        border_color: document.getElementById('border_color').value,
        border_thickness: document.getElementById('border_thickness').value
    });

    const res = await fetch(`${API_BASE}/save_chatbot`, { method:'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body:formData });
    if(res.ok) {
        const data = await res.json();
        if(data.share_key) {
            currentChatbot.share_key = data.share_key; // Update share_key if generated
        } else {
            alert("Share key not returned from server.");
            throw new Error("Share key missing in response");
        }
    } else {
        const errorData = await res.json();
        alert("Failed to save styles: " + (errorData.message || "Unknown error"));
        throw new Error("Save failed");
    }
}

// Speech Recognition
const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
let recognition;

if (SpeechRecognition) {
    recognition = new SpeechRecognition();
    recognition.continuous = false;
    recognition.interimResults = false;
    recognition.lang = 'en-US';

    recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        document.getElementById('user_input').value = transcript;
    };

    recognition.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
    };
} else {
    console.warn('Speech recognition not supported in this browser.');
}

function startListening() {
    if (recognition) {
        recognition.start();
    } else {
        alert('Speech recognition not supported.');
    }
}

document.getElementById('micBtn').addEventListener('click', startListening);

// Handle logo upload
document.getElementById('company_logo_upload').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('company_logo').value = e.target.result;
            document.getElementById('logo_preview').innerHTML = `<img src="${e.target.result}" style="max-width: 100px; max-height: 100px;">`;
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('company_logo').value = '';
        document.getElementById('logo_preview').innerHTML = '';
    }
});
</script>
</body>
</html>
