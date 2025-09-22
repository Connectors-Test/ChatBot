<?php
// Prevent caching to avoid redirect loops
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Include centralized session configuration
require_once __DIR__ . '/includes/session_config.php';

$API_BASE = "http://localhost:5001";

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

<div class="container py-5">


    <!-- LOGGED IN INTERFACE -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
        <div>
            <!-- Invisible Button -->
            <button id="invisibleBtn" class="invisible-btn" onclick="handleInvisibleClick()"></button>
            <a href="?logout=1" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <!-- Hidden iframe for popup -->
    <div id="popupContainer" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: rgba(0, 0, 0, 0.1); backdrop-filter: blur(2px);">
        <div style="position: relative; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;">
            <iframe id="jotformFrame" src="https://form.jotform.com/252633118963460" style="width: 90%; max-width: 800px; height: 600px; border: none; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3); transition: all 0.3s ease;" onload="handleJotFormLoad()"></iframe>
            <button id="closePopupBtn" style="position: absolute; top: 30px; right: 30px; background: #dc3545; color: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 24px; cursor: pointer; z-index: 10000; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2); transition: all 0.3s ease;">&times;</button>
            <div id="loadingIndicator" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); z-index: 9998;">
                <div style="text-align: center;">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p style="margin-top: 10px; color: #333;">Loading form...</p>
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-info mb-3" onclick="loadSavedChatbots()">Preview Saved Chatbots</button>
    <div id="savedList" class="mb-3"></div>

    <!-- Chatbot Configuration Form -->
    <form id="configForm" novalidate>
        <fieldset>
            <legend class="visually-hidden">Chatbot Configuration</legend>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="chatbot_name" class="form-label">Chatbot Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="chatbot_name" required aria-describedby="chatbotNameHelp">
                    <div id="chatbotNameHelp" class="form-text">Enter a unique name for your chatbot</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="chatbot_id" class="form-label">Chatbot ID <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="chatbot_id" readonly aria-describedby="chatbotIdHelp">
                        <button type="button" class="btn btn-secondary" onclick="generateID()" id="generateIdBtn">Generate ID</button>
                    </div>
                    <div id="chatbotIdHelp" class="form-text">Auto-generated unique identifier</div>
                </div>
            </div>

            <div class="mb-3">
                <label for="gemini_api_key" class="form-label">Gemini API Key <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="gemini_api_key" required aria-describedby="apiKeyHelp">
                <div id="apiKeyHelp" class="form-text">Your Google Gemini API key for AI functionality</div>
            </div>

            <div class="mb-3">
                <label for="gemini_model" class="form-label">Gemini Model <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="gemini_model" value="gemini-2.0-flash" required aria-describedby="modelHelp">
                <div id="modelHelp" class="form-text">AI model to use (default: gemini-2.0-flash)</div>
            </div>

            <div class="mb-3">
                <label for="data_source" class="form-label">Data Source <span class="text-danger">*</span></label>
                <select class="form-select" id="data_source" onchange="onDataSourceChange()" required aria-describedby="dataSourceHelp">
                    <option value="google_sheets" selected>Google Sheets</option>
                    <option value="mysql" class="restricted-option" style="display: none;">MySQL</option>
                    <option value="postgresql" class="restricted-option" style="display: none;">PostgreSQL</option>
                    <option value="neo4j" class="restricted-option" style="display: none;">Neo4j</option>
                    <option value="mongodb" class="restricted-option" style="display: none;">MongoDB</option>
                </select>
                <div id="dataSourceHelp" class="form-text">Select your data source type</div>
            </div>

            <div id="googleSheetsFields">
                <div class="mb-3">
                    <label for="sheet_id" class="form-label">Google Spreadsheet ID</label>
                    <input type="text" class="form-control" id="sheet_id" aria-describedby="sheetIdHelp">
                    <div id="sheetIdHelp" class="form-text">The ID from your Google Sheets URL</div>
                </div>

                <div class="mb-3">
                    <label for="service_account_json" class="form-label">Service Account JSON</label>
                    <textarea class="form-control" id="service_account_json" rows="5" aria-describedby="serviceAccountHelp"></textarea>
                    <div id="serviceAccountHelp" class="form-text">JSON credentials for Google Sheets API access</div>
                </div>
            </div>

            <div id="dbFields" style="display:none;">
                <div class="mb-3">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input type="text" class="form-control" id="db_host" aria-describedby="dbHostHelp">
                    <div id="dbHostHelp" class="form-text">Database server hostname or IP address</div>
                </div>
                <div class="mb-3">
                    <label for="db_port" class="form-label">Database Port</label>
                    <input type="number" class="form-control" id="db_port" value="3306" aria-describedby="dbPortHelp">
                    <div id="dbPortHelp" class="form-text">Database connection port</div>
                </div>
                <div class="mb-3">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input type="text" class="form-control" id="db_name" aria-describedby="dbNameHelp">
                    <div id="dbNameHelp" class="form-text">Name of the database to connect to</div>
                </div>
                <div class="mb-3">
                    <label for="db_username" class="form-label">Database Username</label>
                    <input type="text" class="form-control" id="db_username" aria-describedby="dbUsernameHelp">
                    <div id="dbUsernameHelp" class="form-text">Username for database authentication</div>
                </div>
                <div class="mb-3">
                    <label for="db_password" class="form-label">Database Password</label>
                    <input type="password" class="form-control" id="db_password" aria-describedby="dbPasswordHelp">
                    <div id="dbPasswordHelp" class="form-text">Password for database authentication</div>
                </div>
            </div>

            <div id="neo4jFields" style="display:none;">
                <div class="mb-3">
                    <label for="neo4j_uri" class="form-label">Neo4j URI</label>
                    <input type="text" class="form-control" id="neo4j_uri" aria-describedby="neo4jUriHelp">
                    <div id="neo4jUriHelp" class="form-text">Neo4j database connection URI</div>
                </div>
                <div class="mb-3">
                    <label for="neo4j_db_name" class="form-label">Database Name</label>
                    <input type="text" class="form-control" id="neo4j_db_name" aria-describedby="neo4jDbHelp">
                    <div id="neo4jDbHelp" class="form-text">Name of the Neo4j database</div>
                </div>
                <div class="mb-3">
                    <label for="neo4j_username" class="form-label">Neo4j Username</label>
                    <input type="text" class="form-control" id="neo4j_username" aria-describedby="neo4jUsernameHelp">
                    <div id="neo4jUsernameHelp" class="form-text">Username for Neo4j authentication</div>
                </div>
                <div class="mb-3">
                    <label for="neo4j_password" class="form-label">Neo4j Password</label>
                    <input type="password" class="form-control" id="neo4j_password" aria-describedby="neo4jPasswordHelp">
                    <div id="neo4jPasswordHelp" class="form-text">Password for Neo4j authentication</div>
                </div>
            </div>

            <div id="mongodbFields" style="display:none;">
                <div class="mb-3">
                    <label for="mongo_uri" class="form-label">MongoDB URI</label>
                    <input type="text" class="form-control" id="mongo_uri" placeholder="mongodb://localhost:27017" aria-describedby="mongoUriHelp">
                    <div id="mongoUriHelp" class="form-text">MongoDB connection string</div>
                </div>
                <div class="mb-3">
                    <label for="mongo_db_name" class="form-label">Database Name</label>
                    <input type="text" class="form-control" id="mongo_db_name" aria-describedby="mongoDbHelp">
                    <div id="mongoDbHelp" class="form-text">Name of the MongoDB database</div>
                </div>
            </div>

            <button type="button" class="btn btn-primary mb-3" onclick="connectSpreadsheet()" id="connectBtn">Connect</button>
        </fieldset>
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

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const API_BASE = "<?= $API_BASE ?>";



function generateID() {
    document.getElementById('chatbot_id').value = 'cb-' + Math.random().toString(36).substring(2,10);
}

function onDataSourceChange() {
    const dataSource = document.getElementById('data_source').value;
    const googleFields = document.getElementById('googleSheetsFields');
    const dbFields = document.getElementById('dbFields');
    const neo4jFields = document.getElementById('neo4jFields');
    const mongodbFields = document.getElementById('mongodbFields');
    if (dataSource === 'google_sheets') {
        googleFields.style.display = 'block';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
    } else if (dataSource === 'neo4j') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'block';
        mongodbFields.style.display = 'none';
    } else if (dataSource === 'mongodb') {
        googleFields.style.display = 'none';
        dbFields.style.display = 'none';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'block';
    } else {
        googleFields.style.display = 'none';
        dbFields.style.display = 'block';
        neo4jFields.style.display = 'none';
        mongodbFields.style.display = 'none';
        if (dataSource === 'mysql') {
            document.getElementById('db_port').value = '3306';
        } else if (dataSource === 'postgresql') {
            document.getElementById('db_port').value = '5432';
        }
    }
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
    } else if (dataSource === 'mongodb') {
        data.append('mongo_uri', document.getElementById('mongo_uri').value);
        data.append('mongo_db_name', document.getElementById('mongo_db_name').value);
    } else {
        data.append('db_host', document.getElementById('db_host').value);
        data.append('db_port', document.getElementById('db_port').value);
        data.append('db_name', document.getElementById('db_name').value);
        data.append('db_username', document.getElementById('db_username').value);
        data.append('db_password', document.getElementById('db_password').value);
    }

    const res = await fetch(`${API_BASE}/set_credentials`, { method:'POST', body:data });
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

// Save chatbot
async function saveChatbot() {
    // Validation: Ensure chatbot_id is generated
    if (!document.getElementById('chatbot_id').value) {
        alert("Please generate a Chatbot ID first.");
        return;
    }

    const dataSource = document.getElementById('data_source').value;
    const itemName = dataSource === 'google_sheets' ? 'sheet_names' : 'table_names';
    const selectedItems = Array.from(document.querySelectorAll(`input[name="${itemName}"]:checked`)).map(el=>el.value);
    const data = new URLSearchParams({
        username: "<?= $_SESSION['username'] ?>",
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
    } else if (dataSource === 'mongodb') {
        data.append('mongo_uri', document.getElementById('mongo_uri').value);
        data.append('mongo_db_name', document.getElementById('mongo_db_name').value);
    } else {
        data.append('db_host', document.getElementById('db_host').value);
        data.append('db_port', document.getElementById('db_port').value);
        data.append('db_name', document.getElementById('db_name').value);
        data.append('db_username', document.getElementById('db_username').value);
        data.append('db_password', document.getElementById('db_password').value);
    }

    selectedItems.forEach(s => data.append('selected_items', s));

    // Logging: Print data being sent
    console.log('Saving chatbot with data:', Object.fromEntries(data));

    const res = await fetch(`${API_BASE}/save_chatbot`, { method:'POST', body:data });
    if(res.ok) {
        alert("Chatbot saved!");
    } else {
        // Error handling: Alert user if save fails
        const error = await res.json();
        alert("Failed to save chatbot: " + (error.message || "Unknown error"));
    }
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

// Invisible Button and Restriction System
let invisibleClickCount = 0;
let isUnlocked = false;
let chatbotCount = 0;

function handleInvisibleClick() {
    invisibleClickCount++;

    // Check if user is logged in and this is the first click after login
    if (!isUnlocked && invisibleClickCount === 1) {
        // First click after login - unlock everything
        unlockAllFeatures();
        return;
    }

    // If already unlocked, do nothing special
    if (isUnlocked) {
        return;
    }

    // Count clicks for unlock (15+ clicks)
    if (invisibleClickCount >= 15) {
        unlockAllFeatures();
    }
}

function unlockAllFeatures() {
    isUnlocked = true;
    invisibleClickCount = 0;

    // Show all datasource options
    const restrictedOptions = document.querySelectorAll('.restricted-option');
    restrictedOptions.forEach(option => {
        option.style.display = 'block';
    });

    // Update button appearance to indicate unlocked state
    const invisibleBtn = document.getElementById('invisibleBtn');
    invisibleBtn.classList.add('unlocked');

    // Make the button visible now that features are unlocked
    invisibleBtn.style.opacity = '1';

    alert('All features unlocked! You now have access to all datasources and can save unlimited chatbots.');
}

function handleJotFormLoad() {
    // Handle JotForm iframe loading
    const iframe = document.getElementById('jotformFrame');
    const loadingIndicator = document.getElementById('loadingIndicator');

    // Hide loading indicator when iframe starts loading
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }

    // Set responsive sizing after iframe loads
    setTimeout(() => {
        try {
            // Try to access iframe content (will fail due to cross-origin policy)
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            if (iframeDoc) {
                // This will throw an error due to cross-origin policy, which is expected
                const jotformContainer = iframeDoc.querySelector('.jotform-container');
                if (jotformContainer) {
                    const contentHeight = jotformContainer.scrollHeight;
                    if (contentHeight > 0) {
                        iframe.style.height = Math.min(contentHeight + 100, window.innerHeight * 0.8) + 'px';
                    }
                }
            }
        } catch (e) {
            // Expected cross-origin error - use responsive sizing instead
            console.log('Cross-origin policy prevents direct iframe access - using responsive sizing');

            // Use responsive sizing based on viewport
            const viewportHeight = window.innerHeight;
            const desiredHeight = Math.max(600, viewportHeight * 0.7); // Minimum 600px, max 70% of viewport
            iframe.style.height = desiredHeight + 'px';

            // Add resize listener for responsive behavior
            window.addEventListener('resize', function() {
                const newHeight = Math.max(600, window.innerHeight * 0.7);
                iframe.style.height = newHeight + 'px';
            });
        }
    }, 2000); // Give JotForm time to load
}

function showRestrictionModal() {
    const popupContainer = document.getElementById('popupContainer');
    const loadingIndicator = document.getElementById('loadingIndicator');

    popupContainer.style.display = 'block';

    // Show loading indicator initially
    if (loadingIndicator) {
        loadingIndicator.style.display = 'block';
    }

    // Prevent body scroll
    document.body.style.overflow = 'hidden';

    // Add click handler to close when clicking outside iframe
    popupContainer.addEventListener('click', function(event) {
        if (event.target === popupContainer || event.target.closest('#popupContainer > div')) {
            closeRestrictionModal();
        }
    });

    // Focus the iframe for better accessibility after loading
    setTimeout(() => {
        const iframe = document.getElementById('jotformFrame');
        if (iframe) {
            try {
                iframe.focus();
            } catch (e) {
                // Cross-origin restrictions, focus the container instead
                popupContainer.focus();
            }
        }
    }, 100);
}

function closeRestrictionModal() {
    const popupContainer = document.getElementById('popupContainer');
    const backdrop = document.getElementById('popupBackdrop');

    popupContainer.style.display = 'none';

    if (backdrop) {
        backdrop.remove();
    }

    // Restore body scroll
    document.body.style.overflow = 'auto';
}

function checkChatbotLimit() {
    // This will be called before saving a chatbot
    const username = "<?= $_SESSION['username'] ?>";

    fetch(`${API_BASE}/get_chatbot_count?username=${encodeURIComponent(username)}`)
        .then(res => res.json())
        .then(data => {
            if (data.count >= 1 && !isUnlocked) {
                showRestrictionModal();
                return false;
            }
            return true;
        })
        .catch(error => {
            console.error('Error checking chatbot count:', error);
            return true; // Allow save on error
        });
}

// Override the saveChatbot function to check limits
const originalSaveChatbot = saveChatbot;
saveChatbot = async function() {
    if (!isUnlocked) {
        const canSave = await checkChatbotLimit();
        if (!canSave) {
            return;
        }
    }

    // Call original function if not restricted
    return originalSaveChatbot();
};

// Override the onDataSourceChange function to check restrictions
const originalOnDataSourceChange = onDataSourceChange;
onDataSourceChange = function() {
    const dataSource = document.getElementById('data_source').value;

    if (dataSource !== 'google_sheets' && !isUnlocked) {
        showRestrictionModal();
        // Reset to Google Sheets
        document.getElementById('data_source').value = 'google_sheets';
        return;
    }

    // Call original function
    return originalOnDataSourceChange();
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Check if user has already unlocked features (could be stored in session/localStorage)
    const savedUnlockedState = localStorage.getItem('featuresUnlocked');
    if (savedUnlockedState === 'true') {
        unlockAllFeatures();
    }

    // Save unlocked state when it changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                const invisibleBtn = document.getElementById('invisibleBtn');
                if (invisibleBtn.classList.contains('unlocked')) {
                    localStorage.setItem('featuresUnlocked', 'true');
                }
            }
        });
    });

    const invisibleBtn = document.getElementById('invisibleBtn');
    if (invisibleBtn) {
        observer.observe(invisibleBtn, { attributes: true });
    }

    // Add keyboard support for closing popup
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const popupContainer = document.getElementById('popupContainer');
            if (popupContainer.style.display === 'block') {
                closeRestrictionModal();
            }
        }
    });

    // Add click handler for close button
    const closeBtn = document.getElementById('closePopupBtn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeRestrictionModal();
        });
    }
});
</script>
</body>
</html>




