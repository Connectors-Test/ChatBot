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

    <button class="btn btn-info mb-3" onclick="loadSavedChatbots()">Preview Saved Chatbots</button>
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
        </select>
    </div>

    <div id="googleSheetsFields">
        <div class="mb-3">
            <label for="sheet_id">Google Spreadsheet ID</label>
            <input type="text" class="form-control" id="sheet_id">
        </div>

        <div class="mb-3">
            <label for="service_account_json">Service Account JSON</label>
            <textarea class="form-control" id="service_account_json" rows="5"></textarea>
        </div>
    </div>

    <div id="dbFields" style="display:none;">
        <div class="mb-3">
            <label for="db_host">Database Host</label>
            <input type="text" class="form-control" id="db_host">
        </div>
        <div class="mb-3">
            <label for="db_port">Database Port</label>
            <input type="number" class="form-control" id="db_port" value="3306">
        </div>
        <div class="mb-3">
            <label for="db_name">Database Name</label>
            <input type="text" class="form-control" id="db_name">
        </div>
        <div class="mb-3">
            <label for="db_username">Database Username</label>
            <input type="text" class="form-control" id="db_username">
        </div>
        <div class="mb-3">
            <label for="db_password">Database Password</label>
            <input type="password" class="form-control" id="db_password">
        </div>
    </div>

    <div id="mssqlFields" style="display:none;">
        <div class="mb-3">
            <label for="db_host">Database Host</label>
            <input type="text" class="form-control" id="mssql_host">
        </div>
        <div class="mb-3">
            <label for="db_port">Database Port</label>
            <input type="number" class="form-control" id="mssql_port" value="1433">
        </div>
        <div class="mb-3">
            <label for="db_name">Database Name</label>
            <input type="text" class="form-control" id="mssql_name">
        </div>
        <div class="mb-3">
            <label for="db_username">Database Username</label>
            <input type="text" class="form-control" id="mssql_username">
        </div>
        <div class="mb-3">
            <label for="db_password">Database Password</label>
            <input type="password" class="form-control" id="mssql_password">
        </div>
    </div>

    <div id="neo4jFields" style="display:none;">
        <div class="mb-3">
            <label for="neo4j_uri">Neo4j URI</label>
            <input type="text" class="form-control" id="neo4j_uri">
        </div>
        <div class="mb-3">
            <label for="neo4j_db_name">Database Name</label>
            <input type="text" class="form-control" id="neo4j_db_name">
        </div>
        <div class="mb-3">
            <label for="neo4j_username">Neo4j Username</label>
            <input type="text" class="form-control" id="neo4j_username">
        </div>
        <div class="mb-3">
            <label for="neo4j_password">Neo4j Password</label>
            <input type="password" class="form-control" id="neo4j_password">
        </div>
    </div>

    <div id="mongodbFields" style="display:none;">
        <div class="mb-3">
            <label for="mongo_uri">MongoDB URI</label>
            <input type="text" class="form-control" id="mongo_uri" placeholder="mongodb://localhost:27017">
        </div>
        <div class="mb-3">
            <label for="mongo_db_name">Database Name</label>
            <input type="text" class="form-control" id="mongo_db_name">
        </div>
    </div>

    <div id="oracleFields" style="display:none;">
        <div class="mb-3">
            <label for="oracle_version">Oracle Version</label>
            <select class="form-select" id="oracle_version">
                <option value="19">Oracle 19c</option>
                <option value="23">Oracle 23c</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="db_host">Database Host</label>
            <input type="text" class="form-control" id="db_host">
        </div>
        <div class="mb-3">
            <label for="db_port">Database Port</label>
            <input type="number" class="form-control" id="db_port" value="1521">
        </div>
        <div class="mb-3">
            <label for="db_name">Service Name</label>
            <input type="text" class="form-control" id="db_name">
        </div>
        <div class="mb-3">
            <label for="db_username">Database Username</label>
            <input type="text" class="form-control" id="db_username">
        </div>
        <div class="mb-3">
            <label for="db_password">Database Password</label>
            <input type="password" class="form-control" id="db_password">
        </div>
    </div>

    <div id="airtableFields" style="display:none;">
        <div class="mb-3">
            <label for="airtable_api_key">Airtable API Key</label>
            <input type="text" class="form-control" id="airtable_api_key" placeholder="keyXXXXXXXXXXXXXX">
        </div>
        <div class="mb-3">
            <label for="airtable_base_id">Airtable Base ID</label>
            <input type="text" class="form-control" id="airtable_base_id" placeholder="appXXXXXXXXXXXXXX">
        </div>
    </div>

    <div id="databricksFields" style="display:none;">
        <div class="mb-3">
            <label for="databricks_hostname">Databricks Server Hostname</label>
            <input type="text" class="form-control" id="databricks_hostname" placeholder="adb-1234567890123456.7.azuredatabricks.net">
        </div>
        <div class="mb-3">
            <label for="databricks_http_path">HTTP Path</label>
            <input type="text" class="form-control" id="databricks_http_path" placeholder="sql/protocolv1/o/1234567890123456/1234-567890-abcdef12">
        </div>
        <div class="mb-3">
            <label for="databricks_token">Access Token</label>
            <input type="password" class="form-control" id="databricks_token" placeholder="dapi-XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX">
        </div>
    </div>

    <div id="supabaseFields" style="display:none;">
        <div class="mb-3">
            <label for="supabase_url">Supabase URL</label>
            <input type="text" class="form-control" id="supabase_url" placeholder="https://your-project.supabase.co">
        </div>
        <div class="mb-3">
            <label for="supabase_anon_key">Supabase Anon Key</label>
            <input type="password" class="form-control" id="supabase_anon_key" placeholder="your-anon-key">
        </div>
    </div>

    <div id="snowflakeFields" style="display:none;">
        <div class="mb-3">
            <label for="snowflake_account">Snowflake Account</label>
            <input type="text" class="form-control" id="snowflake_account" placeholder="your-account.snowflakecomputing.com">
        </div>
        <div class="mb-3">
            <label for="snowflake_user">Snowflake User</label>
            <input type="text" class="form-control" id="snowflake_user">
        </div>
        <div class="mb-3">
            <label for="snowflake_password">Snowflake Password</label>
            <input type="password" class="form-control" id="snowflake_password">
        </div>
        <div class="mb-3">
            <label for="snowflake_warehouse">Warehouse</label>
            <input type="text" class="form-control" id="snowflake_warehouse">
        </div>
        <div class="mb-3">
            <label for="snowflake_database">Database</label>
            <input type="text" class="form-control" id="snowflake_database">
        </div>
        <div class="mb-3">
            <label for="snowflake_schema">Schema</label>
            <input type="text" class="form-control" id="snowflake_schema">
        </div>
        <div class="mb-3">
            <label for="snowflake_role">Role (optional)</label>
            <input type="text" class="form-control" id="snowflake_role">
        </div>
    </div>

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
            <input type="text" id="user_input" class="form-control" placeholder="Ask about your data...">
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

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_BASE = "<?= $API_BASE ?>";

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
    if (dataSource === 'google_sheets') {
        googleFields.style.display = 'block';
        dbFields.style.display = 'none';
        mssqlFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        oracleFields.style.display = 'none';
        airtableFields.style.display = 'none';
        supabaseFields.style.display = 'none';
        snowflakeFields.style.display = 'none';
    } else if (dataSource === 'mssql') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        mssqlFields.style.display = 'block';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        oracleFields.style.display = 'none';
        airtableFields.style.display = 'none';
        supabaseFields.style.display = 'none';
        snowflakeFields.style.display = 'none';
    } else if (dataSource === 'neo4j') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        mssqlFields.style.display = 'none';
        neo4jFields.style.display = 'block';
        mongodbFields.style.display = 'none';
        oracleFields.style.display = 'none';
        airtableFields.style.display = 'none';
        supabaseFields.style.display = 'none';
        snowflakeFields.style.display = 'none';
    } else if (dataSource === 'mongodb') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        mssqlFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'block';
        oracleFields.style.display = 'none';
        airtableFields.style.display = 'none';
        supabaseFields.style.display = 'none';
        snowflakeFields.style.display = 'none';
    } else if (dataSource === 'oracle') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        mssqlFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        oracleFields.style.display = 'block';
        airtableFields.style.display = 'none';
        supabaseFields.style.display = 'none';
        snowflakeFields.style.display = 'none';
    } else if (dataSource === 'airtable') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        mssqlFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        oracleFields.style.display = 'none';
        airtableFields.style.display = 'block';
        supabaseFields.style.display = 'none';
        snowflakeFields.style.display = 'none';
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
    } else {
        googleFields.style.display = 'none';
        dbFields.style.display = 'block';
        mssqlFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        oracleFields.style.display = 'none';
        airtableFields.style.display = 'none';
        supabaseFields.style.display = 'none';
        snowflakeFields.style.display = 'none';
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
    chatDiv.innerHTML += `<p class="bot"><b>Bot:</b> ${data.response}</p>`;
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

// Fill form with saved chatbot
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
    } else {
        document.getElementById('db_host').value = cb.db_host;
        document.getElementById('db_port').value = cb.db_port;
        document.getElementById('db_name').value = cb.db_name;
        document.getElementById('db_username').value = cb.db_username;
        document.getElementById('db_password').value = cb.db_password;
    }

    const selectedItems = JSON.parse(cb.selected_items || "[]");
    const itemName = cb.data_source === 'google_sheets' ? 'sheet_names' : 'table_names';
    const container = document.getElementById('sheetSelection');
    container.querySelectorAll(`input[name="${itemName}"]`).forEach(input=>{
        input.checked = selectedItems.includes(input.value);
    });
}
</script>
</body>
</html>




